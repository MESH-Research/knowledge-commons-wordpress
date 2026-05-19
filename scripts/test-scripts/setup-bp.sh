#!/bin/bash
set -e

# This script must run INSIDE the app container (via docker compose exec app)
# AFTER the first web request has triggered BuddyPress auto-initialization.
# Running it from a separate container won't work because BP's PHP-FPM init
# overwrites CLI-set options on first web request.

WP="wp --allow-root --path=/app/site/web/wp"

echo "==> Activating BuddyPress components..."
$WP bp component activate groups || true
$WP bp component activate activity || true
$WP bp component activate blogs || true
$WP bp component activate members || true
$WP bp component activate settings || true
$WP bp component activate xprofile || true

echo "==> Activating additional plugins (after BP components)..."
$WP plugin activate humanities-commons --network || true
$WP plugin activate hc-custom || true

echo "==> Activating theme..."
$WP theme activate hcommons-mpe-theme || true

echo "==> Verifying components:"
$WP eval '
$components = bp_get_option("bp-active-components", array());
echo "  Active: " . implode(", ", array_keys($components)) . PHP_EOL;
'

echo "==> Creating BuddyPress pages..."
create_or_ensure_bp_page() {
    local slug="$1"
    local title="$2"
    PAGE_ID=$($WP post list --post_type=page --name="$slug" --field=ID 2>/dev/null || echo "")
    if [ -z "$PAGE_ID" ]; then
        echo "    Creating page: $slug"
        $WP post create --post_type=page --post_title="$title" \
            --post_name="$slug" --post_status=publish || true
    else
        echo "    Page '$slug' exists (ID: $PAGE_ID)"
    fi
}

create_or_ensure_bp_page "members" "Members"
create_or_ensure_bp_page "groups" "Groups"
create_or_ensure_bp_page "activity" "Activity"
create_or_ensure_bp_page "sites" "Sites"

# Set BuddyPress page assignments using CURRENT page IDs
MEMBERS_ID=$($WP post list --post_type=page --name=members --field=ID 2>/dev/null || echo "")
GROUPS_ID=$($WP post list --post_type=page --name=groups --field=ID 2>/dev/null || echo "")
ACTIVITY_ID=$($WP post list --post_type=page --name=activity --field=ID 2>/dev/null || echo "")
BLOGS_ID=$($WP post list --post_type=page --name=sites --field=ID 2>/dev/null || echo "")

echo "    Page IDs: members=$MEMBERS_ID groups=$GROUPS_ID activity=$ACTIVITY_ID blogs=$BLOGS_ID"

if [ -n "$MEMBERS_ID" ] && [ -n "$GROUPS_ID" ] && [ -n "$ACTIVITY_ID" ]; then
    BP_PAGES="{\"members\":\"$MEMBERS_ID\",\"groups\":\"$GROUPS_ID\",\"activity\":\"$ACTIVITY_ID\""
    if [ -n "$BLOGS_ID" ]; then
        BP_PAGES="$BP_PAGES,\"blogs\":\"$BLOGS_ID\""
    fi
    BP_PAGES="$BP_PAGES}"
    $WP option update bp-pages "$BP_PAGES" --format=json || true
fi

echo "==> Configuring bbPress group forums..."
$WP option update _bbp_enable_group_forums 1 || true

# Create root forum for group forums
ROOT_FORUM_ID=$($WP eval '
$forums = get_posts(array("post_type" => "forum", "numberposts" => 1, "post_status" => "publish"));
if (!empty($forums)) { echo $forums[0]->ID; }
' 2>/dev/null || echo "")
if [ -z "$ROOT_FORUM_ID" ]; then
    ROOT_FORUM_ID=$($WP eval '
        $id = bbp_insert_forum(array("post_title" => "Group Forums", "post_status" => "publish"));
        echo $id;
    ' 2>/dev/null || echo "")
fi
if [ -n "$ROOT_FORUM_ID" ]; then
    $WP option update _bbp_group_forums_root_id "$ROOT_FORUM_ID" || true
    echo "    Root forum ID: $ROOT_FORUM_ID"
fi

echo "==> Flushing rewrite rules..."
$WP rewrite flush || true

echo "==> Saving BP test config (force-bp-config.php will restore on every request)..."
$WP eval '
$components = bp_get_option("bp-active-components", array());
$pages = bp_get_option("bp-pages", array());
$config = array("components" => $components, "pages" => $pages);
update_option("bp_test_config", $config);
echo "  Active components: " . implode(", ", array_keys($components)) . PHP_EOL;
echo "  BP pages: " . json_encode($pages) . PHP_EOL;
echo "  Config saved to bp_test_config option" . PHP_EOL;
'

echo "==> BP setup complete."
