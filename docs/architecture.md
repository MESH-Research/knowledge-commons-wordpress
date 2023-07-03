# Repository architecture

This repository contains all of the code necessary for building and running the Commons WordPress site, locally and on AWS.

## Directories

- `.lando` : Build files for Lando
- `ancillary-plugins` : Plugins responsible for non-essential functionality on the Commons site.
- `config` : Configurations for various environments, organized by environment & service.
  - `all` : Configurations that apply to all environments.
  - `local`: Configurations for running local dev environment.
  - `production`: Configurations for running on production.
  - `staging`: Configurations for running on staging.
- `core-plugins`: Plugins responsible for core functionalities on the Commons site.
- `docs`: Documentation specific to this repository.
- `forked-plugins`: Externally-developed plugins that have been forked in order to work on the Commons site.
- `mu-plugins`: Commons-developed or forked must-use plugins.
- `scripts`: Helper scripts for developing, managing, and deploying the Commons site.
- `simplesamlphp`: Base directory for running SimpleSAMLPhp as a service provider, used by WP SAML Auth plugin for authenticating users on the Commons.
- `site`: The site filesystem, with appropriate symlinks to Commons themes, plugins, etc.
  - `site/web`: Webroot for the site.
- `themes`: Commons-managed themes.

## Plugins

- Core Plugins
  - `hc-custom`: Functionality and modifications for Commons base sites. Only active on base sites.
  - `hc-member-profiles`: Functionality for Commons profiles.
  - `hc-styles`: Commons badges and related functionality.
  - `humantities-commons`: Essential functionality for Commons network. Network-active on all networks.
  - `humcore`: Interface with Fedora to run CORE repository.
- Ancillary Plugins
  - `bp-attachment-xprofile-field-type`: Allows users to upload a CV to their profiles.
  - `buddypress-docs-minor-edit`: Shows a checkbox that prevents other users from being notified about a group document being edited.
  - `elasticpress-buddypress`: Indexes BuddyPress members, groups, and humcore deposits in ElasticSearch.
  - `hc-notifications`: Custom BuddyPress notifications for the Commons.
  - `hc-suggestions`: Legacy widget for suggesting content related to users' academic interests.
  - `mla-academic-interests`: Implements taxonomy of humanities-centric academic interests.
  - `mla-allowed-tags`: Allow iframe, embed and script tags in post content when enabled on a user site.
  - `sparkpost-bp-mailer`: Integrate SparkPost with the BuddyPress 2.5 Email API
  - `wordpress-sparkpost`: Send all your email from Wordpress through SparkPost (Should be in forked-plugins...)
- Forked Plugins
  - `bp-block-member`: Allow users to block private messages and other notifications from other members.
  - `bp-event-organizer`: Use Event Organizer plugin with BuddyPress groups.
  - `bp-group-documents`: Document management for BuddyPress groups.
  - `buddypress-followers`: Follow other users and be notified of their activities.
  - `buddypress-group-email-subscription`: Allows users to subscribe to updates from the group via email.
  - `buddypress-messages-spam-blocker`: Rate-limits spam messages.
  - `tainacan`: Allows user site to run a digital museum. Used on https://dahd.hcommons.org/ .
- Must-Use Plugins
  - `admin.php`: Some filters to improve admin dashboard QoL.
  - `atom-feed-titles.php`: Provide full HTML in Atom feed post titles.
  - `bedrock-autoloader.php`: Autoloads Composer dependencies.
  - `charityhub.php`: Modifications to Charity Hub theme.
  - `commentpress.php`: Modifications to CommentPress.
  - `dev-allow-internal-urls.php`: Allow "unsafe URLs" (internal DNS resolution) for domains in dev.
  - `disallow-indexing.php`: Disallow indexing on non-production sites.
  - `google-analytics-async.php`: Modify behavior of Google Analytics Asyc plugin.
  - `hc-footer.php`: Add HC footer to user sites.
  - `https-new-sites`: Force new sites to have https URLs.
  - `i18n.php`: Loads text domains for a couple plugins. (WHY??)
  - `logging.php`: MLA logging functionality.
  - `mail.php`: Some modifications to how mail is sent.
  - `more-new-site-defaults.php`: Add options for new sites to have limited comment duration.
  - `ninja-forms-autocomplete.php`: Enable browser autocomplete for ninja forms.
  - `register-theme-directory.php`: Register Theme Directory (Bedrock)
  - `rips-unlink-hotfix.php`: ????


