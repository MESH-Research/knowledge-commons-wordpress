## v1.1.0 (2026-06-05)

### Feat

- **ci**: add commitizen versioning and /.version.php endpoint

## v1.0.0 (2026-06-05)

### BREAKING CHANGE

- WordPress no longer exchanges authorization codes
directly with CILogon. The Profiles application now acts as an identity
broker, passing pre-decoded encrypted userinfo via a broker_token
parameter. CILOGON_CLIENT_ID, CILOGON_CLIENT_SECRET, and
CILOGON_PROVIDER_URL environment variables are no longer used.

### Feat

- **cilogon**: add resync-all WP-CLI command for paced full-user sync
- **infra**: add /wp-json/idms/service endpoint exposing ECS service name
- **ci**: port build-system refactor from wordpress-cilogon
- **msu**: require login for site-level /docs/ on commons.msu.edu
- **infra**: add /wp-json/idms/service endpoint exposing ECS service name
- **msu**: require login for site-level /docs/ on commons.msu.edu
- **ci**: enable pre-built base image for app builds
- **ci**: add workflow to build and cache PHP base image
- **cilogon**: add extensive CILOGON_DEBUG logging to avatar sync
- **cilogon**: add /idms/update-avatar REST endpoint for avatar sync
- **cilogon**: pass final_redirect through Profiles broker for reliable post-login routing
- **cilogon**: replace CILogon OIDC with Profiles identity broker
- **test**: activate theme and plugins in e2e test environment
- **cilogon**: add secret-key login bypass for automated testing
- **test**: add end-to-end testing pipeline with Playwright
- **auth**: enable sunrise.php for cross-domain cookie sharing
- **config**: add STEMEDPLUS_ROOT_BLOG_ID constant
- **footer**: add new footer requested by Scott to hc-footer.php
- **kcworks-on-wp**: update plugin version
- **plugins**: remove footnotes-made-easy and add kcworks-on-wp 1.0.1
- **champions**: add champion badges to profiles
- **composer**: tainacan update
- **plugins**: add kcworks-on-wp and cool-timeline
- **kcworks-plugin**: added first version of kcworks-plugin to the Commons
- **iteach**: add iteach.msu.edu domain to handler and redirects
- **linked-open-profiles**: upgrade version
- **cilogon**: add REST endpoint to sync user email from Profiles
- **cilogon**: remove logging of sensitive data without debug flag
- **cilogon**: fix default user type
- **logout**: add logout ping to api
- **cilogon**: add sync of user data at login-time and also sync superuser status
- **sync**: synchronize user name across systems
- **sync**: add username sync
- **idms-api**: change cli command
- **sync-endpoint**: add endpoint that will synchronize a user's memberships
- **comanage**: continued exfiltration of comanage
- **cilogon**: start of plugin
- **composer**: updgrade linked-open-profiles to new version
- **redirects**: add redirect for research site
- **redirect**: add ecokritike redirect

### Fix

- **cilogon**: suppress cc-client profile provisioner during login sync
- **hc-custom**: cap Works update-notification webhook timeout at 3s
- **cilogon**: pass raw memberships, not processMemberships output, to BP sync
- **cilogon**: drive BP member-type sync directly from API payload
- **cilogon**: use api/v1/members path for Profiles members endpoint
- **nginx**: remove profile location override from member rewrites
- **comanage**: correct PROFILES_API_BEARER env var name
- **cilogon**: reduce silent-SSO recheck TTL to 30 seconds
- **cilogon**: set silent-SSO recheck TTL to 2 minutes
- **humanities-commons**: restrict members list to current network on page 1
- **build**: create SimpleSAML log and tmp dirs at image build time
- **build**: restore wp-saml-auth and SimpleSAML public dir in cloud image
- **build**: copy simplesamlphp path-repo source before composer install
- **humanities-commons**: restrict members list to current network on page 1
- **build**: remove Composer more-privacy-options to avoid symlink conflict
- **privacy**: add network membership check to More Privacy Options
- **cilogon**: increase silent-SSO recheck TTL to 15 minutes
- **nginx**: add new redirect for membership
- **cilogon**: reduce silent-SSO recheck TTL to 30 seconds
- **cilogon**: set silent-SSO recheck TTL to 2 minutes
- **nginx**: exclude BuddyPress member sub-pages from Profiles redirect
- **cilogon**: source new-account email from broker-token primary_email
- **cilogon**: tolerate missing/empty email on new-user creation
- **cilogon**: handle top-level profile shape from members API
- **universal-login**: set ttl on cookie to 3 minutes to avoid infinite redirect loop
- **universal-login**: extend ttl to 1 minute
- **universal-login**: set sso cookie ttl to 10 seconds
- **build**: remove Composer more-privacy-options to avoid symlink conflict
- **privacy**: add network membership check to More Privacy Options
- **cilogon**: use BuddyPress timestamp filename pattern for avatars
- **ci**: preserve composer-installed plugins when creating symlinks
- **ci**: move theme gulp builds after source code COPY
- **ci**: add --no-scripts to sub-project composer installs
- **ci**: disable BASE_IMAGE build-arg until base image exists in ECR
- **ci**: move BASE_IMAGE ARG to global scope before first FROM
- **cilogon**: update avatar domain whitelist to actual S3 bucket names
- **cilogon**: correct broker verify-nonce endpoint path
- **cilogon**: use direct URI matching instead of rewrite rules for broker callback
- **nginx**: add missing closing brace for server block in hcommons.conf.template
- **cilogon**: use guard clause for null user check in get_user_info
- **sunrise**: harden domain mapping against null HTTP_HOST and missing blogs
- **auth**: persist WordPress cookies during SAML auto-login
- **sunrise**: use domain suffix matching for COOKIE_DOMAIN resolution
- **docker**: disable Redis object cache installation on startup
- **hc-styles**: remove /members suffix from badge URL
- **hc-footer**: fix syntax error in hc-footer
- **composer**: set build requirements correctly
- **composer**: update composer lock
- **plugins**: add awesome-footnotes
- change ToS URL
- **humanities-commons-plugin**: add array check in set_members_dir_permalink
- **cilogon**: create WP user for new CILogon users found in Profiles API
- **cilogon**: change logout endpoint method from GET to POST
- **cilogon**: remove duplicate cou entry for stem
- **cilogon**: handle undefined array access
- **cilogon**: handle json decode errors
- **cilogon**: fix cases where missing null checks caused fatal errors
- **cilogon**: use wordpress transient sessions instead of php sessions, which don't work with load balancers and multiple servers
- **cilogon**: ensure user exists before logout call
- **cilogon**: only allow safe redirects from redirect_to query param
- **logout**: further fixes to logout
- **buddypress**: fix commenting problem
- **cilogon**: more work in progress
- **init**: undertake initial startup work
- **humanities-commons**: update hc to correctly redirect to 404s
- **redirect**: fix redirect
- **buddypress-messages**: fix function check
- **buddypress-messages-spamblocker**: fix bad function check
- **composer**: fix composer
- **nginx**: add redirect for /deposits/ to works
- **nginx**: add redirect for /core/ to works
- **msu-commons-footer**: remove part of the footer
- **chrome**: bugfix for chrome in full site editor
- **highlight**: fix highlighting bug to give white text
- **hc-styles**: fix a bug in Chrome where highlighting in block editor doesn't work
- **css**: add css to fix selection highlighting in block editor
- **style**: fix highlighting text problem on Chrome
- change ToS URL
- Resolve linting errors in markdown file
- **user REST API**: allow _s (underscores) in username

### Refactor

- **deps**: migrate from wpackagist.org to repo.wp-packages.org
- **deps**: migrate from wpackagist to wp-packages.org
- **cilogon**: add proper rest permission checking function and tests
- **cilogon**: refactor for readability

### Perf

- **ci**: expand .dockerignore to reduce build context size
- **ci**: switch to ARM64 runners, parallel builds, and ECR layer caching
- **ci**: restructure Dockerfile for cache efficiency
