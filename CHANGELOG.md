## v1.6.0 (2026-10-21)

### Feat

- **mu-plugins**: add idms/opcache REST endpoint for cache observation
- **docker**: enable OPcache in the php-fpm image
- **accessibility**: add wp-accessibility plugin
- **wordpress**: regenerate composer lock file
- **wordpress**: version upgrade
- **php**: change listener to all ports
- **php**: change build version
- **php**: further php upgrade work
- **php**: upgrade to php 8.5
- **php**: upgrade to php 8.5
- **nuke**: remove insecure plugins (graphql) and associated theme builds
- **security**: mass plugin upgrade
- **docker**: refuse to start when static AWS credentials are present
- **docker**: refuse to start when static AWS credentials are present
- **buddypress**: upgrade to 14.5.2
- **wordpress**: upgrade core
- **hc-custom**: show members their private groups' events sitewide
- **auth**: bypass CILogon broker flow when basic-login key present
- **auth**: add key-gated native login fallback for admins
- **hc-custom**: disable group cover image uploads
- **wordpress**: upgrade wordpress to 7.0.3
- **bp-rbe-inbound-forum-attachments**: replace local fork with MESH-tracked upstream
- **bp-reply-by-email**: replace local fork with MESH-tracked upstream via composer
- **buddypress-followers**: replace local fork with MESH-tracked upstream via composer
- **buddypress-group-email-subscription**: replace local fork with upstream 4.2.4
- **bp-groupblog**: replace local fork with upstream 1.9.4
- **bp-group-documents**: replace local fork with upstream 2.1
- **buddypress**: upgrade commons-in-a-box and buddypress-docs for BP 14
- **buddypress**: upgrade buddypress to 14.5.0
- **wordpress**: upgrade wordpress core to 7.0.2
- **wordpress**: upgrade wordpress core to 7.0.1
- **cc-client**: upgrade cc-client to avoid lengthy timeouts
- **wordpress**: upgrade wordpress core to wordpress 7.0.6
- **wordpress**: upgrade wordpress core to 7.0.2
- **wordpress**: upgrade to wordpress 7.0

### Fix

- **docker**: install PECL extensions from pinned sources, not pecl.php.net
- **footnotes**: upgrade awesome footnotes plugin
- **bbPress**: upgrade to latest version (security)
- **groups**: stop percent-encoded group slugs breaking the groups directory
- **php**: correct image name
- **hc-custom**: switch to activity network when regenerating bbPress actions
- **hc-custom**: guard multinetwork forum permalinks outside activity loops
- **nginx**: restore stemedplus.org network alias for members redirects
- **nginx**: restore stemedplus.org network alias for members redirects
- **repo**: separate .phpunit.cache and INCIDENT.md gitignore entries
- **hc-custom**: keep private-post checks for non-members on public groups
- **hc-custom**: keep full private-event access for roles that have it
- **hc-custom**: stop granting read_private_events to everyone
- **hc-custom**: check group access before scoping the calendar
- **hc-custom**: link group calendar events to the group's event page
- **hc-custom**: scope group events calendar to the current group
- **groups**: point group Site tab at the group site and allow its redirect host
- **groups**: stop fatal on join/invite-accept when BPGES is inactive
- **auth**: sync STEM Ed+ memberships to the stemedplus member type
- **docs**: enforce group privacy for Docs regardless of per-Doc settings
- **docs**: strengthen Docs access-control enforcement
- **activity**: prevent PHP 8 fatal in group activity streams for logged-in users
- **hc-custom**: gate group event creation on the group connection setting
- **hc-custom**: restore group nav show/hide and landing page settings
- **bp-event-organiser**: declutter group event timepicker grid
- **hc-custom**: stop group doc creation demanding the placeholder's edit nonce
- **hc-custom**: stop create-doc saves demanding a per-doc edit nonce
- **hc-custom**: restore send invites tab for existing groups on BuddyPress 12+
- **hc-custom**: link group docs tabs to the current group and add single-doc back-nav
- **hc-custom**: restore group events subnav and admin event management
- **hc-custom**: restore group admin forum moderation caps and links
- **bp-event-organiser**: stop date picker jumping page to top
- **lock**: update cc-client
- **sparkpost-bp-mailer**: remove debug logging that dumped mailer credentials
- **hc-custom**: skip no-op kcworks visibility changes lacking permission
- **hc-custom**: use trailing-slash kcworks endpoint and parse real create response
- **hc-custom**: resolve kcworks collection lookup warnings in group nav
- **cron**: run wp-cron solely from the cron container, hourly, for all sites
- **hc-notifications**: use {$var} instead of deprecated ${var} interpolation
- **hc-member-profiles**: silence PHP 8.x param and setcookie deprecations
- **hc-custom**: silence PHP 8.0 optional-before-required param deprecations
- **buddypress-followers**: use slug functions instead of removed BP_*_SLUG constants
- **bp-group-email-subscription**: use slug functions instead of removed BP_*_SLUG constants
- **bp-group-documents**: name the correct network in upload mail subjects
- **test**: correct nginx conf mount paths in e2e compose file
- **nginx**: map stemedplus.org to its network for members redirects
- **auth**: sync STEM Ed+ memberships to the stemedplus member type
- **groups**: scope group directory counts and listings to current network
- **docs**: enforce group privacy for Docs regardless of per-Doc settings
- **docs**: strengthen Docs access-control enforcement
- **activity**: prevent PHP 8 fatal in group activity streams for logged-in users
- **cron**: run wp-cron solely from the cron container, hourly, for all sites

### Refactor

- **mu-plugins**: centralize multi-network mail-subject fixes and add GES handler

### Perf

- **hc-custom**: restrict members' private events in SQL, not by ID list

## v1.5.1 (2026-08-10)

### Fix

- **cron**: adopt production's cron implementation on main

## v1.5.0 (2026-08-10)

### Feat

- **cron**: externalise per-site wp-cron and disable loopback cron in prod

## v1.4.0 (2026-07-20)

## v1.3.0 (2026-07-09)

### Feat

- **wordpress**: upgrade wordpress core to 7.0.2
- **wordpress**: update to wordpress 7.0

## v1.2.1 (2026-06-18)

### Fix

- **nginx**: allow members settings/notifications sub-pages in buddypress

## v1.2.0 (2026-06-16)

### Feat

- **kcworks-on-wp**: upgrade kc-works-on-wp to latest version
- **nginx**: alias MLA custom domains to the mla network
- **nginx**: map custom domains to network subdomains for Profiles redirects
- **nginx**: redirect network /members/ to network subdomain of Profiles
- **nginx**: scope /members/ redirect to network via /network/{subdomain} path
- **nginx**: add new routing structure
- **wordpress-core**: initial upgrade to wordpress 7
- **nginx**: renumber template includes and glob env overlays

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
