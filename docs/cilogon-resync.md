# CILogon `resync-all` WP-CLI command

`wp cilogon resync-all` performs a one-shot synchronisation of every WordPress
user against the remote Profiles API, using the same code path as the
per-login sync (`Plugin::sync_user()`). It is intended for occasional
operational use — for example after a bulk change to membership data on the
Profiles side, or after recovering a site that has drifted out of sync.

The command iterates the `wp_users` table in `ID` order, keyset-paginated, and
calls the Profiles API once per user with a configurable delay between
requests. Progress is persisted in a WordPress option so that an interrupted
run can be resumed exactly where it stopped.

## Usage

```
wp cilogon resync-all [--sleep=<ms>] [--start-at=<user_id>] [--limit=<n>] \
                      [--batch-size=<n>] [--reset-cursor] [--dry-run]
```

### Options

`--sleep=<ms>`

Milliseconds to pause between consecutive user syncs. Default: `300`. With
the default a full pass over ~50,000 users takes roughly 4 hours and 10
minutes. Set `--sleep=0` to remove the pause entirely (not recommended
against a shared Profiles API).

`--start-at=<user_id>`

Start syncing from users with `ID > <user_id>`. Overrides the persisted
cursor for this run only. Useful for replaying a specific range.

`--limit=<n>`

Stop after `<n>` successful syncs in this run. Default: no limit. Skipped
users (those without a `user_login`) and errored users do not count
towards the limit.

`--batch-size=<n>`

Number of user IDs to fetch from the database in each query. Default:
`200`. Larger batches reduce DB round-trips; smaller batches keep memory
flat on very large sites. The batch size has no effect on the pacing of
API requests — sleeping happens per user, not per batch.

`--reset-cursor`

Delete the persisted cursor (option `cilogon_resync_cursor`) before
starting. Equivalent to `--start-at=0`, but also clears the cursor so that
subsequent runs without `--start-at` start from the beginning rather than
resuming.

`--dry-run`

Iterate users and log what would be done, but do not call the Profiles
API. The cursor still advances during a dry run.

## Operational recipes

Run a full sync in the background, capturing output to a log file:

```
nohup wp cilogon resync-all --sleep=300 > /var/log/cilogon-resync.log 2>&1 &
```

Resume an interrupted run — same command:

```
wp cilogon resync-all --sleep=300
```

Re-run only the first ten users, without API calls, to verify iteration:

```
wp cilogon resync-all --dry-run --limit=10 --reset-cursor
```

Start over from scratch:

```
wp cilogon resync-all --reset-cursor
```

## Behaviour

- **Resume by ID.** The cursor in option `cilogon_resync_cursor` records the
  ID of the last user processed (success, skip, or error). On the next run,
  iteration starts from `ID > cursor`.
- **Per-user error isolation.** Any exception thrown by `Plugin::sync_user()`
  is caught and logged as a warning; the command continues with the next
  user. The error count is reported in the final summary.
- **Skipped users.** Users with an empty `user_login` are skipped with a
  warning and counted separately from errors.
- **Final summary.** When the run finishes (either by reaching the end of
  the users table or hitting `--limit`), the command logs a line of the form
  `Done. synced=<n> skipped=<n> errors=<n> last_user_id=<n>`.

## Logging

The command writes progress through `WP_CLI::log()` and `WP_CLI::warning()`,
so output respects the standard `--quiet` / `--debug` flags. When invoked
outside of WP-CLI the same messages are written to `error_log()` for
test/internal use.

## Related code

- Command implementation: `mu-plugins/ci-logon/ResyncCommand.php`
- WP-CLI registration: `mu-plugins/cilogon.php`
- Underlying single-user sync: `Plugin::sync_user()` in
  `mu-plugins/ci-logon/Plugin.php`
- Tests: `mu-plugins/ci-logon/tests/ResyncCommandTest.php`
