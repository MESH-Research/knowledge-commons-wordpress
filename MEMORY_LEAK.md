# Memory Leak Investigation: Production WordPress on ECS

**Issue:** [MESH-Research/knowledge-commons-wordpress#80](https://github.com/MESH-Research/knowledge-commons-wordpress/issues/80)
**Symptom:** `wordpress-prod-3 wp-green` memory utilisation climbs roughly linearly from ~20% to ~50% of the 55 GiB task reservation over the 06/05–06/11 window, never plateauing, surviving daily traffic cycles, and resetting only on deploy (the dip on 06/05 coincides with that day's deploy batch). The `wp-cron` task's sawtooth is normal: each WP-CLI run exits and frees its memory.
**Scope of this audit:** full static analysis of this repository (all 32 in-repo plugins, 23 mu-plugins, 9 in-repo themes, cron scripts, Docker/ECS/php-fpm/nginx configuration) and of the [hcommons-mpe-theme](https://github.com/MESH-Research/hcommons-mpe-theme) repository, plus research into the pinned third-party stack and into how ECS actually measures container memory. Every significant finding was adversarially re-verified against the code before inclusion; several plausible-looking causes were refuted and are documented below so they are not re-investigated.

---

## 1. Executive summary

No single smoking-gun leak exists in the repository code. The web-request PHP paths examined are, with minor exceptions, request-scoped: PHP-FPM frees their memory at request end, so they cannot by themselves produce a week-long linear climb. The evidence instead points to a small set of **runtime/configuration conditions** — none visible in the repo because the relevant values live in Secrets Manager or on EFS — that allow normal workloads to accumulate memory:

1. **PHP-FPM workers may never be recycled.** `pm.max_requests` is a pure `${PM_MAX_REQUESTS}` env substitution with no default ([config/all/php/www.conf:12](config/all/php/www.conf)). Empirically, php-fpm 8.2 **starts cleanly with `pm.max_requests = 0` (never respawn) when the variable is unset** — unlike `pm.max_children`, which refuses to start when missing. Production therefore demonstrably sets the `PM_*` family at least partially, but an absent/zero `PM_MAX_REQUESTS` would be completely silent. Never-recycled workers are the precondition that turns every per-worker drift below into unbounded growth.
2. **Native-extension memory that `memory_limit` cannot see.** `imagick` is installed and is WordPress's preferred image editor; ImageMagick allocates pixel caches via its own allocator, outside Zend MM, so leaked/fragmented native memory survives request shutdown and accumulates in long-lived workers. `intl`, `yaml`, `protobuf`, `redis` and `memcached` are also compiled in (Dockerfile.php:36-46). This class of growth is invisible to PHP-level accounting and is exactly what `pm.max_requests` exists to contain (php.net documents it as the workaround "for memory leaks in 3rd party libraries").
3. **OPcache is provably never enabled** (confirmed empirically by building the image: `php -m` lists no Zend OPcache). Every worker re-compiles WordPress plus ~100 plugins on every request. This does not itself create monotonic growth, but it maximises per-worker allocator churn/fragmentation, inflates the baseline, and amplifies (1) and (2).
4. **A measurement artifact remains possible until one production check is run.** On cgroup v2 hosts (AL2023 ECS AMI), the ECS agent reports `memory.current − inactive_file`: **active** file page cache still counts. Continuously appended or re-read files (logs, `/tmp` session files, EFS page cache — all charged to the task cgroup on first touch) can therefore present as a steady "leak" of reclaimable memory. On cgroup v1 (AL2 AMI) the agent subtracts the *entire* page cache, so the observed creep would be real anonymous memory. **Section 5, check #1 settles this in five minutes and should be run first.**
5. **Nine production plugins/themes exist only on EFS and were never audited** — confirmed by cross-referencing [config/all/wordpress/plugin-theme-activation.yaml](config/all/wordpress/plugin-theme-activation.yaml) against the repo and composer.json: plugins `elementor-pro`, `siteorigin-panels`, `15zine-functionality`, `simplemag-addons`, `footnotes-made-easy`, `cron-logger`; themes `faculty`, `maskitto-light`, `origin`, `vantage-premium`. Any of these could contain the actual leak (Elementor alone has documented high-memory issues), and they are mounted read-write. They must be pulled from `/content` and audited the same way.
6. **Data-growth vectors inflate every request slowly.** With no persistent object cache (the Redis drop-in install is commented out in [scripts/build-scripts/docker-php-entrypoint.sh](scripts/build-scripts/docker-php-entrypoint.sh)), every request re-loads `alloptions` and the serialized `cron` option per site. Jetpack 12.2 Sync has well-documented incidents of unbounded `jpsq_sync-*` rows in `wp_options` (one documented case reached 2.52M rows); the `mla_academic_interests` taxonomy grows monotonically from free-text profile input and is fully hydrated on hot paths; ActivityPub 2.2 predates the outbox-purge routines later versions had to add. These are KB-to-MB-scale per-request inflators, not GB-scale on their own — but they are the only *monotonic* in-repo mechanisms found, and they compound with (1).

**Most probable explanation (to be confirmed by the runbook in §5):** php-fpm workers that are never (or very rarely) recycled, each slowly accumulating native-extension and allocator-fragmentation memory under a heavy no-opcache workload — possibly compounded by a cgroup-v2 file-cache measurement artifact and/or by code in the nine unaudited EFS plugins. The deploy-reset pattern in the graph fits this: a new task means fresh workers and an empty cgroup.

**Highest-value remedies (details in §6):** set `PM_MAX_REQUESTS` explicitly (500–1000) and verify it took effect; enable OPcache; enable the Redis object cache that the infrastructure already half-provisions; add per-container memory limits and FPM status monitoring; audit the EFS plugin set; run the §5 runbook to pin the diagnosis before deeper code changes.

---

## 2. Reading the graph correctly

What the CloudWatch number actually is determines what can and cannot explain it. (Sources: `amazon-ecs-agent` `agent/stats/utils_unix.go`; docker stats documentation; kernel cgroup docs; AWS ECS metrics documentation.)

- Both the service-level `MemoryUtilization` metric and Container Insights `MemoryUtilized` derive from the same ECS-agent stats poll of the Docker API.
- **cgroup v1 host (Amazon Linux 2 ECS AMI):** the agent reports `usage − cache`, subtracting the *entire* page cache (active + inactive file, and shmem/tmpfs). Growing log files, `/tmp` files, and EFS page cache **cannot** appear in the graph. A creep on v1 is real anonymous/kernel memory — i.e. PHP worker heaps, native allocations, sockets, slab.
- **cgroup v2 host (AL2023 ECS AMI):** the agent matches modern `docker stats`: `memory.current − inactive_file`. **Active file cache and tmpfs/shmem remain counted.** Pages of continuously appended or repeatedly read files get promoted to the active LRU and show up as monotonic "memory growth" even though the kernel would reclaim them under pressure (i.e. a benign artifact that would likely never OOM).
- The percentage denominator is the task-level reservation (56,320 MiB; no per-container limits are set in [config/production/task-definition-app.json](config/production/task-definition-app.json)). 50% ≈ 28 GiB. The task-level limit is enforced as a real cgroup; if genuinely exceeded, ECS stops the task with `OutOfMemoryError: Container killed due to memory usage` (exit 137).
- Page cache for **EFS/NFS** reads and writes is charged to the cgroup of the task that first touches the pages (first-touch accounting) — relevant because every request walks plugin/theme code symlinked from `/content` on EFS.

**Implication:** the very first diagnostic step is to determine the host's cgroup version and the anon/file split of the task cgroup (§5, check #1). Everything else in this report branches on that answer.

---

## 3. How this deployment manages memory (relevant architecture facts)

| Fact | Where | Memory consequence |
|---|---|---|
| php-fpm 8.2.26, Alpine (musl), `pm = dynamic`, **all** pool tunables from Secrets Manager env | [config/all/php/www.conf](config/all/php/www.conf), [Dockerfile.php:89-92](Dockerfile.php) | Worker count, per-worker `memory_limit`, and recycling policy are invisible to the repo; `pm.max_requests` silently becomes 0 if unset |
| OPcache never enabled | Dockerfile.php extension list; 8-line php.ini; empirically confirmed | Full recompile of WP + ~100 plugins per request per worker; high allocator churn; no shared bytecode |
| No persistent object cache (`object-cache.php` drop-in install commented out) | docker-php-entrypoint.sh | All `wp_cache_*` is per-request only; transient GC degraded; `alloptions` + `cron` option unserialized from DB every request on every site |
| `error_log = php://stderr`; `WP_DEBUG_LOG = false` | config/all/php/php.ini, site/config/application.php:170 | PHP/WP errors go to awslogs, **not** container files (good) |
| nginx official-image log symlinks intact (`access.log → /dev/stdout`) | Dockerfile.nginx (never touches /var/log/nginx) | nginx logging does not write container files (good) |
| `/app` is a task-scoped local Docker volume (copy-up at task start, destroyed on task stop) | task-definition-app.json | Any runtime writes under /app land on EC2 host disk charged to the task cgroup; reset on deploy — matches the graph's deploy reset |
| Uploads on EFS (`/media`); extra plugins/themes symlinked from EFS `/content` at start | Dockerfile.php:79-82, link-efs-themes-plugins.sh | Plugin file writes mostly land on EFS (doesn't grow the writable layer, but its page cache is charged on first touch) |
| No `session.*` config; stock php.ini deleted | config/all/php/php.ini | Any plugin calling `session_start()` writes `sess_*` files to container `/tmp`. In-repo code has only one admin-only session user (bp-reply-by-email admin), but the EFS plugins (e.g. elementor-pro) are unaudited |
| Separate 1 GiB cron task runs all heavy jobs (BPGES digests, Mailchimp, `wp cron event run` hourly/3-hourly per site via fresh processes) | scripts/cron/commons.crontab | Cron memory is freed at process exit — the orange sawtooth; cron cannot drive the web service's creep directly, only via DB growth |
| `DISABLE_WP_CRON` defaults to **false** when the env var is unset | site/config/application.php:155 | If unset in prod, web workers also run loopback wp-cron alongside the dedicated cron task — worth one check (§5 #3) |
| imagick, redis, memcached, intl, yaml, protobuf extensions compiled in | Dockerfile.php:36-46 | Native allocators outside Zend MM / `memory_limit`; the classic slow-leak class in long-lived workers |

---

## 4. Candidate causes, ranked

### 4.1 Never-recycled FPM workers accumulating native memory — *primary hypothesis*

**Mechanism.** PHP frees request-scoped memory at request end, and Zend MM even returns excess 2 MB chunks to the OS at request shutdown (keeping only an adaptive cache). What it does **not** control: (a) memory allocated by native libraries outside Zend MM — ImageMagick pixel caches and library heap, ICU, libyaml, protobuf, mysqli buffers, persistent-capable redis/memcached client structures; (b) libc-level heap fragmentation, which on musl has its own history (pre-1.2 malloc was notoriously bad; modern mallocng is better but extensions that malloc directly still fragment); (c) anything a worker touches that ratchets its high-water residency under a workload as heavy as "compile all of WordPress every request" (no OPcache). All of these grow per worker over its lifetime and are exactly why `pm.max_requests` exists — php.net documents it verbatim as the workaround "to work around memory leaks in 3rd party libraries." PHP-FPM has **no** memory-based recycling (the `pm.max_memory` feature request, php-src #17661, is still open), so request-count recycling is the only containment.

**Evidence for.** The graph: linear, never plateaus, resets on deploy (new task = new workers). A pure PHP-data ratchet would plateau within hours/days once each worker had served its heaviest request — the adversarial verifiers refuted *every* in-repo "ratchet" finding on exactly this ground. A non-plateauing climb requires either continuously growing state (none found in repo web paths), native leaks in immortal workers, or a file-cache artifact.

**Evidence gaps.** The actual `PM_MAX_REQUESTS`, `PM_MAX_CHILDREN`, `PHP_MEMORY_LIMIT` values (Secrets Manager); worker ages in production. §5 checks #2 and #3 resolve this.

**Aggravators.** imagick as the default WP image editor on a site with continuous community uploads; `MAGICK_THREAD_LIMIT` not set anywhere (OpenMP thread blowup per operation); imagick has documented temp-file and allocation leaks under FPM (e.g. Imagick #681); protobuf ext has documented decode leaks (protocolbuffers #6567). Also note php-src GH-13775 ("Unknown memory leak in PHP 8.2 FPM", reported affecting 8.2.4–8.2.22) — opcache-related and opcache is off here, but it shows the 8.2 line is not leak-free; 8.2.26 is pinned (Dockerfile.php:6).

### 4.2 cgroup-v2 active-file-cache measurement artifact — *equal first until disproven*

**Mechanism.** If the container instances run AL2023 (cgroup v2), everything repeatedly written/read inside the task — any stray log file, `/tmp` session/temp files, the copy-up `/app` volume, and first-touch EFS page cache for the entire plugin tree — counts as active file pages in the reported metric. A multisite serving heavy traffic from EFS-symlinked code could plausibly activate gigabytes of page cache over a week. This memory is reclaimable; the kernel would drop it before OOM-killing, so the "leak" would be cosmetic.

**Evidence for.** Linear shape is characteristic of cumulative file touching; the deploy reset fits (fresh cgroup).
**Evidence against.** The in-repo write surfaces found are small (see refuted findings: PHP/WP/nginx logs all go to stdout/stderr; the Monolog file logger is dead code). The biggest candidate is EFS read page cache plus whatever the EFS-only plugins write.
**Resolution.** §5 check #1 (`memory.stat` anon vs file split + `memory.reclaim` probe) is decisive.

### 4.3 The nine unaudited EFS-only plugins/themes — *unknown unknowns, confirmed real*

`elementor-pro`, `siteorigin-panels`, `15zine-functionality`, `simplemag-addons`, `footnotes-made-easy`, `cron-logger` (plugins); `faculty`, `maskitto-light`, `origin`, `vantage-premium` (themes) are active in production (per plugin-theme-activation.yaml) but exist in neither the repo nor composer.json — they live only on EFS `/content` and were not auditable. Elementor (free) already has documented "crazy high memory" issues (#24604) and its Pro sibling is heavier; a `cron-logger` plugin is by definition a log writer whose destination is unknown. **These must be exfiltrated and audited** (§5 check #5). Until then, the audit's "no smoking gun in code" conclusion only covers ~90% of the running PHP.

### 4.4 DB-side growth that inflates every request (slow, compounding)

With no object cache, every request on every site unserializes `alloptions` and the `cron` option; on multisite, network options come from `wp_sitemeta`. Documented growth vectors in this stack:

- **Jetpack 12.2 Sync queue:** unbounded `jpsq_sync-*` rows in `wp_options` when sync stalls (Jetpack #9791, #7439; a documented third-party case reached 2.52M rows); full-sync OOM on large user tables (#5133); documented bad interaction with ElasticPress full sync (10up/ElasticPress #572).
- **ActivityPub 2.2:** the plugin's own changelog later added "purge Outbox items older than 6 months to avoid performance issues" and scheduled inbox purging — the pinned 2.2 predates all retention logic, and maintainers acknowledge inbound objects "quickly accumulate massive amounts of junk data" in wp_posts. There is also a documented inbox-request memory exhaustion (#522), which is per-request web-worker memory.
- **`cron` option bloat:** core's duplicate protection is weak (trac #6966, #44818) and concurrent schedule/unschedule races corrupt entries (#51747); trac #49693 documents production incidents from a grown cron row. Every web request pays to unserialize it.
- **Expired-transient accumulation:** core's daily `delete_expired_transients` only cleans `wp_sitemeta` site transients when cron runs on the main site of the main network; per-site cleanup depends on per-site cron actually firing. The CILogon `BrokerAuth` transient-per-anonymous-visitor pattern ([mu-plugins/ci-logon/BrokerAuth.php](mu-plugins/ci-logon/BrokerAuth.php):688,733-737) is feature-flag-gated and `autoload=no`, so it is options-table bloat rather than per-request memory — but it feeds this category when silent SSO is enabled.
- **`mla_academic_interests` taxonomy:** every profile save converts unrecognized free-text into permanent global terms with no pruning ([plugins/mla-academic-interests/class-mla-academic-interests.php](plugins/mla-academic-interests/class-mla-academic-interests.php):267-280), and hot paths hydrate the *entire* term set as full `WP_Term` objects on every select2 keystroke and profile-edit render because the `wp_cache_set` guard never persists (REST controller :95-116; list query :104-124). The verifier correctly downgraded this as a *container-leak* cause (request-scoped memory), but it is monotonic DB growth, a per-request CPU/memory tax that rises forever, and the clearest single beneficiary of enabling the object cache.

Individually these are KB–MB per request; collectively, on a network this size, they raise the per-worker baseline continuously — and §5 check #4 measures all of them with five SQL queries.

### 4.5 Long-running process risks (conditional, currently unlikely)

- **bp-reply-by-email IMAP mode** is, by design, an in-worker daemon: `set_time_limit(0)` plus a `sleep(10)` keep-alive loop that re-enters itself forever (`bp_rbe_run_inbox_listener(['force'=>true])`), permanently occupying an FPM worker and defeating `pm.max_requests`. The repo strongly indicates **inbound (SparkPost webhook) mode** is in use instead — the vendored attachment plugin only implements SparkPost, `BP_RBE_SPARKPOST_WEBHOOK_TOKEN` is provisioned, and the `imap` PHP extension is not in the image, which hard-disables IMAP mode. One `wp option get bp-rbe` in production (§5 #3) closes this permanently.
- **CommentPress "live" comment refresh** polls admin-ajax.php every 5 s per open tab when enabled — request volume amplifier, not a leak.

### 4.6 Pure config exposures (not causes, but they shape the failure)

- **No per-container memory limits**: the app container can consume the whole 55 GiB task allocation before anything pushes back; adding a container limit doesn't fix a leak but converts silent creep into observable, bounded recycling and gives per-container attribution.
- **`WP_LOGS_DIR` has no default**: `getenv()` returning `false` would make the Monolog logger (if anything ever instantiates it — today nothing does) write to `'/<slug>.log'` on the container root. Harmless today, a trap tomorrow.

---

## 5. Diagnostic runbook — run these in production, in order

Each check discriminates between the §4 hypotheses. All are read-only except #1's optional reclaim probe.

**#1 — Artifact or real? (decisive, 5 minutes)**
SSM/SSH to the container instance:
```sh
stat -fc %T /sys/fs/cgroup        # cgroup2fs = v2, tmpfs = v1
CG=$(find /sys/fs/cgroup -type d -name "*<task-id>*" | head -1)
grep -E '^(anon|file|shmem|active_file|inactive_file|slab|sock)' $CG/memory.stat   # v2
# v1: cat $CG/memory.stat $CG/memory.usage_in_bytes
```
If `anon` dominates → real memory growth → pursue #2/#3. If `active_file`/`shmem` dominates on v2 → largely a metric artifact → pursue #6 and consider the graph benign. Optional decisive probe on v2: `echo 4G > $CG/memory.reclaim` and watch whether the CloudWatch number steps down (reclaimable = artifact).

**#2 — Which processes hold it?**
```sh
docker stats --no-stream                     # app vs nginx vs anything else on the host
# inside the app container (aws ecs execute-command ... --command "/bin/sh"):
ps -o pid,rss,etime,args | sort -k2 -nr | head -40    # run now and again in 24h, diff
```
Look at: number of php-fpm workers (is `pm=dynamic` slowly raising the floor?), max worker `etime` (do workers EVER recycle?), total worker RSS vs the cgroup figure (a gap = native/file/slab memory), and any non-FPM PHP process (a stuck listener/daemon).

**#3 — Resolved FPM/PHP/WP runtime values**
```sh
php-fpm -tt 2>&1 | grep -E 'max_children|max_requests|spare'
php -r 'echo ini_get("memory_limit")," ",ini_get("max_execution_time"),PHP_EOL;'
wp option get bp-rbe --url=<main-site>        # mode must be "inbound"
wp eval 'var_dump(defined("DISABLE_WP_CRON") ? DISABLE_WP_CRON : null);'
aws secretsmanager get-secret-value --secret-id prod/secrets.env-bX4r04 \
  --query SecretString --output text | jq 'with_entries(select(.key|test("^PM_|^PHP_|WP_LOGS_DIR|DISABLE_WP_CRON")))'
```
`pm.max_requests = 0` here confirms hypothesis 4.1's precondition on the spot.

**#4 — DB growth audit (the §4.4 vectors, five queries)**
```sh
wp db query "SELECT table_name, ROUND(data_length/1048576) AS mb FROM information_schema.tables WHERE table_schema=DATABASE() ORDER BY data_length DESC LIMIT 15"
wp db query "SELECT SUM(LENGTH(option_value)) FROM wp_options WHERE autoload='yes'"   # repeat for busiest wp_N_options
wp db query "SELECT option_name, LENGTH(option_value) l FROM wp_options WHERE autoload='yes' ORDER BY l DESC LIMIT 10"
wp db query "SELECT COUNT(*) FROM wp_options WHERE option_name LIKE 'jpsq_sync%'"
wp db query "SELECT LENGTH(option_value) FROM wp_options WHERE option_name='cron'"
wp db query "SELECT COUNT(*) FROM wp_terms t JOIN wp_term_taxonomy tt ON t.term_id=tt.term_id WHERE tt.taxonomy='mla_academic_interests'"
```
Track these weekly; any that grows in lock-step with the memory graph is implicated.

**#5 — Audit the EFS blind spot**
```sh
ls -la /content/plugins /content/themes
find /content -type f -mmin -120 | head -50      # recent runtime writes = leak clue AND page-cache source
tar czf /tmp/efs-code.tgz -C /content plugins themes && aws s3 cp /tmp/efs-code.tgz s3://<bucket>/   # then static-audit offline
```

**#6 — Container-local write surfaces**
```sh
echo "$WP_LOGS_DIR"; du -xsh "$WP_LOGS_DIR" /tmp /app 2>/dev/null
ls /tmp | grep -c '^sess_'
php -i | grep -E 'session.save_path|gc_probability'
# on the host: du -sh /var/lib/docker/volumes/<task-volume>/_data
```

**#7 — Metric provenance sanity**
```sh
aws ecs describe-tasks --cluster <cluster> --tasks <task> --query 'tasks[].{started:startedAt}'   # task must predate the whole slope
aws ecs list-tasks --cluster <cluster> --desired-status STOPPED   # any OutOfMomoryError/137 history?
```
If tasks restarted mid-week, the "linear" service-level average is an aggregation illusion and must be re-read per task.

---

## 6. Remedies

### Immediate (config only, low risk)

1. **Set `PM_MAX_REQUESTS=500`** (or 1000) in the production secret and verify with `php-fpm -tt`. This is the canonical containment for every native-leak class in §4.1 and costs only a periodic fork. Consider also having the entrypoint fail loudly if the variable is empty, so the silent-zero trap is closed permanently.
2. **Enable OPcache** with `opcache.memory_consumption` ≥ 256 MB, `opcache.max_accelerated_files` ≥ 130k (prime; this codebase is enormous), `opcache.validate_timestamps=0` (immutable containers). One fixed shared segment replaces per-request recompilation in every worker — lower CPU, lower churn, smaller per-worker footprints. Monitor `opcache_get_status()` for `oom_restarts`.
3. **Enable the Redis object cache** that is already half-provisioned (redis ext compiled in, `wp-plugin/redis-cache` in composer, `WP_REDIS_*` constants configured — only the drop-in copy in the entrypoint is commented out). This converts the always-miss `wp_cache_*` guards (academic interests, hc-suggestions, blog-posts profile field, BPGES groupmeta…) into real caches, restores transient GC sanity, and removes the per-request `alloptions`/`cron` DB unserialize. *Caveats:* set `redis.pconnect.pooling_enabled=0` or use non-persistent connections initially (phpredis pconnect has a documented per-worker accumulation history, #1668), and size `maxmemory`+eviction on the Redis side.
4. **Set per-container memory limits** in the task definition (e.g. app: hard limit sized to `pm.max_children × memory_limit` + headroom; nginx: a few hundred MB). Converts invisible creep into bounded, observable behaviour and gives per-container CloudWatch attribution.
5. **Set `MAGICK_THREAD_LIMIT=1`** (and consider `MAGICK_MEMORY_LIMIT`/`MAGICK_MAP_LIMIT`) in the FPM environment to contain ImageMagick's per-operation threads and heap.
6. **Pin `DISABLE_WP_CRON=true`** in the production secret (the dedicated cron task already covers everything) so heavy plugin cron events can never run inside web workers.

### Short term (operational)

7. Run the §5 runbook top to bottom; record results in issue #80. Checks #1 and #3 alone will likely settle the diagnosis.
8. **Audit the nine EFS-only plugins/themes** (§5 #5) with the same leak taxonomy used here; prioritise `elementor-pro` and `cron-logger`.
9. **Add FPM observability:** enable `pm.status_path=/status` (nginx-restricted), scrape worker count/age/requests-served; alert on worker `etime` exceeding a day if recycling is configured.
10. **Database hygiene:** if check #4 shows growth — Jetpack: disable/repair Sync or purge `jpsq_sync-*`; ActivityPub: upgrade past the versions that added outbox/inbox retention; schedule a network-wide expired-transient sweep (`wp transient delete --expired --network`); review the largest autoloaded rows against Pantheon/VIP guidance (autoloaded payload per site under ~1 MB).

### Longer term (code)

11. **mla-academic-interests:** add a `number`/search filter to the term query instead of loading the whole taxonomy per keystroke; dedupe/prune orphan terms; the REST endpoint result will then also fit the (now real) object cache.
12. **BPGES (buddypress-group-email-subscription 3.7.2):** the synchronous whole-group email fan-out inside the posting request (`bp_activity_after_save` → `ass_group_notification_activity`) is a per-request latency/memory spike that grows with group size — upgrade to the ≥3.9 batched-send line or queue the fan-out.
13. **elasticpress-buddypress:** cache the `_cat/indices` cluster scan (`ep_bp_search_user_sites`, filters.php:238-298) in the object cache with a short TTL instead of one live HTTP call per search request.
14. **EPR REST endpoint** ([plugins/elasticpress-buddypress/elasticpress-rest.php](plugins/elasticpress-buddypress)): clamp `numberposts` (e.g. ≤ 50) and add a `permission_callback` — currently any anonymous caller can request thousands of rendered posts per hit (a DoS/memory-spike vector even though it is request-scoped).
15. **WPGraphQL 0.7.0** (vendored, mid-2020): upgrade or retire. It exposes a public batched endpoint with no query-depth/complexity limits; modern releases add depth limiting. (Bounded today only by `memory_limit` fataling the worker.)
16. **Retire dead/trap code:** `mu-plugins/logging.php` (Monolog file logger — currently uninstantiated; if kept, give `WP_LOGS_DIR` a safe default and a `RotatingFileHandler`), and the unused `boss-child-refresh` settings-page log line.
17. **Repo defaults for env-driven tunables:** the entrypoint should validate the full `PM_*`/`PHP_*` family and refuse to start (loudly) on missing values, so production behaviour is never silently defined by an absent secret key.

---

## 7. Investigated and ruled out

Documented so the next investigator doesn't re-tread. Each was raised by a scanner and then refuted by an independent adversarial verifier reading the code.

| Suspect | Why it's not the cause |
|---|---|
| **Monolog file logging to `WP_LOGS_DIR`** (mu-plugins/logging.php) | Dead code. The only apparent caller, cbox-auth, imports its *own* fallback `Logger` (`MLA\Commons\Plugin\CustomAuth\Logger`, includes/class-logger.php) which writes via `error_log()` → stderr → awslogs. Repo-wide grep (both repos) finds zero instantiations of the Monolog class. `hcommons_write_error_log()` (humanities-commons.php:26) is likewise plain `error_log()`. |
| **nginx access/error logs filling the container** | Dockerfile.nginx never touches `/var/log/nginx`, so the official image's symlinks to stdout/stderr remain intact. |
| **Header notifications shortcode loading "all" unread notifications** (hcommons-mpe-theme functions.php:337-352) | In BuddyPress 10.6.0 `bp_notifications_get_notifications_for_user()` resolves to a `GROUP BY component, action` query returning one row per action type (~10–30 rows), regardless of unread count. The theme is clean overall: zero transients, zero remote calls in render paths, zero file writes. |
| **CILogon/OIDC `$_SESSION` files accumulating in /tmp** | No jumbojett vendor code ships in the image's web path; ci-logon's BrokerAuth uses cookie-keyed DB transients, not PHP sessions. (The composer dep exists but nothing in the web path boots it.) |
| **BrokerAuth per-visitor transients as a memory leak** | Feature-flag-gated (silent SSO), 3600 s expiry ⇒ `autoload='no'` ⇒ never enters `alloptions`; options-table bloat only (kept as a §4.4 hygiene item). |
| **BPGES digest queue growing unbounded** | hc-custom deliberately unhooks the WP-cron digest handlers and the real crontab fires `ass_digest_fire` daily/weekly, trimming queues every 24 h (KB-scale per user). |
| **Unbounded `wp_get_sites()` (limit 9999) in humanities-commons** | WP 6.8.1's `wp_get_sites()` returns `array()` outright on large networks (>10k sites); below that, bounded ~10–20 MB peak, request-scoped. |
| **hc-custom Works-webhook unbounded group-member fetch** | The unbounded branch is dead in web requests: three of the hooked actions don't exist in BP 10.6 (`groups_member_invited` etc. are never fired), and all real hooks pass `$user_id`, short-circuiting to the single-user branch. |
| **Academic-interests / hc-suggestions / blog-posts-field / EPR / WPGraphQL "worker RSS ratchet" findings** | All request-scoped; Zend MM returns excess chunks at request shutdown (adaptive cache, huge allocations munmapped immediately), so a heavy request is a transient RSS bump bounded by `memory_limit × max_children` — a plateau, not a week-long line. They remain real *performance* issues (see remedies 11–15) and §4.4 data-growth feeders, but cannot alone explain the graph. |
| **No-per-container-limit / 55 GiB task memory** | An exposure, not a growth mechanism (nothing grows *because* a limit is absent). Remedy 4 still applies. |
| **The cron task itself** | Sawtooth = fresh WP-CLI process per run, memory freed at exit; jobs run per-site in fresh processes via xargs. (The weekly Mailchimp job holds the whole 52-week-active user base plus the full Mailchimp list in one process — a future cron-task OOM risk as the user base grows, but irrelevant to the web service.) |
| **bp-reply-by-email IMAP daemon** | The `imap` extension is not compiled into the image, which hard-disables IMAP mode; all repo evidence (SparkPost webhook plugins, token provisioning) indicates inbound mode. Confirm once via `wp option get bp-rbe` (§5 #3). |

---

## 8. Method note

Analysis was performed by parallel static-analysis passes over every PHP code path in both repositories (10 scan areas), each significant finding then independently adversarially verified against the exact pinned dependency versions (e.g. BuddyPress 10.6.0 source was diffed for the notification and group-hook claims; the Docker image was built to confirm the OPcache and php-fpm env-substitution behaviour empirically), alongside literature research into the ECS agent's metric computation and documented issues across the pinned third-party stack. Confidence levels are reflected in the ranking of §4; the §5 runbook exists because the three highest-ranked hypotheses are distinguishable only with production data that the repositories cannot contain (Secrets Manager values, cgroup statistics, EFS contents, DB row counts).
