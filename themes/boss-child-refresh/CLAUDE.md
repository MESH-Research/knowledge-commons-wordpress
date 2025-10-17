# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

This is a WordPress child theme (`boss-child-refresh`) for the Boss parent theme (by BuddyBoss), customized for the Knowledge Commons WordPress multisite network. The theme serves multiple scholarly societies (MLA, HASTAC, AJS, ASEEES, CAA, SAH, UP, MSU Commons, HC, and STEMEd+) with society-specific styling and features.

## Build System

### CSS Compilation

Compile SCSS to CSS using Gulp:

```bash
npm install
gulp sass
```

Watch for changes during development:

```bash
gulp sass:watch
```

The build process (gulpfile.js:1):
- Compiles SCSS from `./sass/**/*.scss`
- Generates compressed CSS with sourcemaps
- Outputs to `./css/` directory

### Society-Specific Stylesheets

Each society has its own SCSS entry point in the `sass/` directory (e.g., `mla.scss`, `hc.scss`, `hastac.scss`). These files:
1. Import `_variables.scss` (override color/button variables as needed)
2. Import `_global.scss` (includes all shared partials and pages)

The appropriate CSS file is dynamically loaded based on `Humanities_Commons::$society_id` (see functions.php:48-55).

### Stylesheet Architecture

```
sass/
├── _variables.scss       # Color variables, button mixins
├── _global.scss          # Imports all shared styles
├── partials/             # Reusable components
│   ├── _base.scss
│   ├── _header.scss
│   ├── _menu.scss
│   ├── _widgets.scss
│   ├── _buttons.scss
│   └── _inputs.scss
├── pages/                # Page-specific styles
│   ├── _homepage.scss
│   ├── _directories.scss
│   ├── _group.scss
│   └── _profile.scss
├── responsive/
│   ├── _desktop.scss
│   └── _mobile.scss
└── [society].scss        # Society entry points (mla, hc, etc.)
```

## Key Architectural Patterns

### Multi-Society Support

The theme dynamically loads society-specific assets:

- **CSS**: Selected via `Humanities_Commons::$society_id` (functions.php:48-55)
- **Footer Templates**: Uses `is_msu_boss()` helper and society-specific template parts in `template-parts/` (e.g., `footer-copyright-hc.php`, `footer-widgets-msu.php`)
- **Colors/Branding**: Override `$button-background-color`, `$menu-panel-color-hover`, etc. in society SCSS files

### BuddyPress/BuddyBoss Customization

This theme heavily customizes BuddyPress social networking features:

- **Template Overrides**: Located in `buddypress/` and `bbpress/` directories
- **Custom User Mentions**: `MLA_Name_Suggestions` class (functions.php:208-302) provides name-based autocomplete for @mentions, querying by display name and filtering by society member type
- **Script Overrides**: Dequeues parent theme scripts and loads child versions (functions.php:74-142)
  - `buddyboss.js` → `js/buddyboss.js`
  - `social-learner.js` → `js/social-learner.js`
  - `mentions.js` → custom implementation

### Redux Framework Integration

The parent theme uses Redux for theme options. This child theme:
- Removes dynamic CSS output entirely (functions.php:37-42) to prevent conflicts
- Disables Redux AJAX save (functions.php:199-203)
- Fixes script paths for deployment environment (functions.php:191-197)
- Filters Redux URLs to remove `/content/` path prefix (functions.php:644-668)

### Custom Page Templates

Located at root level:
- `page-sites.php` - Network sites directory with filtering (see functions.php:623-642)
- `page-clear-session.php` - Session management
- `page-content-only.php` - Stripped-down layout
- `page-redirect.php` - Redirect handler
- `buddypress-group-single.php` - Group pages
- Special event templates: `front-slug-ach-2021.php`, `front-slug-dh2020.php`, etc.

### BuddyPress Avatar Workaround

Due to historical thumbnail generation issues, the theme uses full avatars instead of thumbnails for group invites (functions.php:162-166). This is marked as TODO for removal once legacy avatars are replaced.

## Important Functions and Hooks

### Asset Enqueuing (functions.php:47-151)

- `boss_child_theme_enqueue_style()` - Loads society CSS with cache-busting timestamps
- `boss_child_theme_enqueue_script()` - Loads custom JS, localizes scripts, overrides parent scripts
- Uses file modification times (`filemtime()`) for cache busting

### User Mention System (functions.php:208-397)

Custom implementation replacing default BuddyPress mentions:
- Searches by display name (not just username)
- Filters by society membership using term taxonomy
- Returns 200 results (vs. default lower limit)
- Works in activity streams, groups, and private messages

### Forum Search (functions.php:511-583)

Custom bbPress search filtering:
- Scoped to specific forums when `bbp_search_forum_id` parameter present
- Respects group membership permissions
- Custom pagination for group forum context

### Admin Bar Customization (functions.php:171-189)

Changes mobile menu behavior to link user avatar/name to profile view (not edit) and removes redundant edit link.

## Development Notes

### Parent Theme Integration

- Parent theme location: `/boss/` (Boss by BuddyBoss)
- Parent functions: `/boss/buddyboss-inc/theme-functions.php`
- Parent CSS load order: main-global.css → main-desktop.css → main-mobile.css → child theme CSS

### File Modification Time Tracking

All enqueued styles and scripts use `filemtime()` for version parameters to ensure cache invalidation on updates.

### Special Considerations

1. **Script Localization**: Child theme re-implements parent theme script localization for `buddyboss-main-override` (functions.php:96-123)
2. **Typekit Integration**: Adobe Typekit loaded for Atkinson Hyperlegible font (functions.php:147-151)
3. **Micromodal**: Used for search modal in header navigation (functions.php:136-142)
4. **Search Results**: Custom avatar display using BuddyPress avatars for groups/members in search (functions.php:437-509)

### Testing Multi-Society Features

When testing society-specific changes:
1. Verify `Humanities_Commons::$society_id` returns expected value
2. Check that correct CSS file loads from `/css/[society].css`
3. Test footer template selection (HC, MSU, and default variants)
4. Confirm color overrides apply correctly

## WordPress Multisite Context

This theme runs on a WordPress multisite network. Some functions query across the network:
- Site visibility filtering (functions.php:623-642)
- Society membership queries use network-wide term taxonomy
- Network options accessed via `get_network_option()`
