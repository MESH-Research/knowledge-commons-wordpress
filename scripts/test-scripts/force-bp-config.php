<?php
/**
 * Force BuddyPress component and page configuration for test environment.
 *
 * BuddyPress auto-initialization (bp_version_updater) overwrites CLI-configured
 * settings on admin page visits. This mu-plugin takes a two-pronged approach:
 * 1. Prevents bp_setup_updater from running (stops the damage)
 * 2. After bp_admin_init, restores correct values to the database (repairs damage)
 * 3. Filters runtime reads to ensure correct values even mid-request
 *
 * Only activates when the bp_test_config option is set.
 */

// Log fatal errors to a file that tests can read, without breaking HTTP redirects.
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        file_put_contents('/tmp/php-fatal.log', date('c') . ' ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line'] . "\n", FILE_APPEND);
    }
});

// Prevent BP version updater from running at all.
// bp_setup_updater is added to bp_admin_init at priority 1000.
// We remove it at priority 0 (before it can run).
add_action('bp_admin_init', function () {
    $config = get_option('bp_test_config');
    if (empty($config)) {
        return;
    }
    // Remove the setup updater to prevent it from overwriting config
    remove_action('bp_admin_init', 'bp_setup_updater', 1000);
}, 0);

// After bp_admin_init completes, restore correct DB values in case the
// updater already ran (e.g., on first request before this hook could fire).
add_action('bp_admin_init', function () {
    $config = get_option('bp_test_config');
    if (empty($config)) {
        return;
    }

    if (!empty($config['components'])) {
        $current = bp_get_option('bp-active-components', array());
        // Check if groups is missing (our canary for BP having reset things)
        if (empty($current['groups'])) {
            bp_update_option('bp-active-components', $config['components']);
            // Also update the in-memory BP object
            if (function_exists('buddypress')) {
                buddypress()->active_components = $config['components'];
            }
        }
    }

    if (!empty($config['pages'])) {
        $current_pages = bp_get_option('bp-pages', array());
        if (empty($current_pages['groups'])) {
            bp_update_option('bp-pages', $config['pages']);
        }
    }
}, 9999);

// Force active components via the filter BP uses when loading them into memory.
// This ensures even if the DB has wrong values, runtime checks see the right ones.
add_filter('bp_active_components', function ($components) {
    $config = get_option('bp_test_config');
    if (!empty($config['components'])) {
        return $config['components'];
    }
    return $components;
}, 999);

// Force page mappings via the filter BP uses when reading them.
add_filter('bp_core_get_directory_page_ids', function ($page_ids) {
    $config = get_option('bp_test_config');
    if (empty($config['pages'])) {
        return $page_ids;
    }

    // Forcibly merge all our pages into the result
    foreach ($config['pages'] as $component => $page_id) {
        $page_ids[$component] = $page_id;
    }

    return $page_ids;
}, 999);

// Also restore on regular (non-admin) requests via bp_init, so frontend
// requests also see the correct components and pages.
add_action('bp_init', function () {
    $config = get_option('bp_test_config');
    if (empty($config)) {
        return;
    }

    if (!empty($config['components']) && function_exists('buddypress')) {
        buddypress()->active_components = $config['components'];
    }
}, 0);
