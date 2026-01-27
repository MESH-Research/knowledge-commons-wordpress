<?php
/**
 * User Tier Management
 *
 * Allows administrators to assign tier levels (Bronze, Silver, Gold, Platinum, Diamond)
 * to users and provides functions to retrieve and display tier status.
 *
 * @package Commons
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class User_Tier
 *
 * Handles user tier management functionality.
 */
class User_Tier {

    /**
     * Meta key for storing user tier.
     *
     * @var string
     */
    const META_KEY = 'user_tier';

    /**
     * Available tier levels ordered by rank (lowest to highest).
     *
     * @var array
     */
    const TIERS = array(
        'bronze'   => array(
            'label' => 'Bronze',
            'color' => '#cd7f32',
        ),
        'silver'   => array(
            'label' => 'Silver',
            'color' => '#c0c0c0',
        ),
        'gold'     => array(
            'label' => 'Gold',
            'color' => '#ffd700',
        ),
        'platinum' => array(
            'label' => 'Platinum',
            'color' => '#e5e4e2',
        ),
        'diamond'  => array(
            'label' => 'Diamond',
            'color' => '#b9f2ff',
        ),
    );

    /**
     * Initialize the class.
     */
    public static function init() {
        // Admin user profile fields.
        add_action( 'show_user_profile', array( __CLASS__, 'render_tier_field' ) );
        add_action( 'edit_user_profile', array( __CLASS__, 'render_tier_field' ) );
        add_action( 'user_new_form', array( __CLASS__, 'render_tier_field' ) );

        // Save user profile fields.
        add_action( 'personal_options_update', array( __CLASS__, 'save_tier_field' ) );
        add_action( 'edit_user_profile_update', array( __CLASS__, 'save_tier_field' ) );
        add_action( 'user_register', array( __CLASS__, 'save_tier_field' ) );

        // Add tier column to users list.
        add_filter( 'manage_users_columns', array( __CLASS__, 'add_tier_column' ) );
        add_filter( 'manage_users_custom_column', array( __CLASS__, 'render_tier_column' ), 10, 3 );
        add_filter( 'manage_users_sortable_columns', array( __CLASS__, 'make_tier_column_sortable' ) );
        add_action( 'pre_get_users', array( __CLASS__, 'sort_by_tier' ) );

        // Add bulk edit capability.
        add_filter( 'bulk_actions-users', array( __CLASS__, 'add_bulk_actions' ) );
        add_filter( 'handle_bulk_actions-users', array( __CLASS__, 'handle_bulk_actions' ), 10, 3 );
        add_action( 'admin_notices', array( __CLASS__, 'bulk_action_notices' ) );

        // Enqueue admin styles.
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_styles' ) );
    }

    /**
     * Get the tier for a user.
     *
     * @param int|null $user_id User ID. Defaults to current user.
     * @return string|null Tier slug or null if no tier set.
     */
    public static function get_user_tier( $user_id = null ) {
        if ( null === $user_id ) {
            $user_id = get_current_user_id();
        }

        $tier = get_user_meta( $user_id, self::META_KEY, true );

        if ( empty( $tier ) || ! array_key_exists( $tier, self::TIERS ) ) {
            return null;
        }

        return $tier;
    }

    /**
     * Get the tier label for a user.
     *
     * @param int|null $user_id User ID. Defaults to current user.
     * @return string Tier label or empty string if no tier set.
     */
    public static function get_user_tier_label( $user_id = null ) {
        $tier = self::get_user_tier( $user_id );

        if ( null === $tier ) {
            return '';
        }

        return self::TIERS[ $tier ]['label'];
    }

    /**
     * Get the tier color for a user.
     *
     * @param int|null $user_id User ID. Defaults to current user.
     * @return string Tier color or empty string if no tier set.
     */
    public static function get_user_tier_color( $user_id = null ) {
        $tier = self::get_user_tier( $user_id );

        if ( null === $tier ) {
            return '';
        }

        return self::TIERS[ $tier ]['color'];
    }

    /**
     * Get tier data for a user.
     *
     * @param int|null $user_id User ID. Defaults to current user.
     * @return array|null Tier data array with 'slug', 'label', and 'color' keys, or null if no tier.
     */
    public static function get_user_tier_data( $user_id = null ) {
        $tier = self::get_user_tier( $user_id );

        if ( null === $tier ) {
            return null;
        }

        return array(
            'slug'  => $tier,
            'label' => self::TIERS[ $tier ]['label'],
            'color' => self::TIERS[ $tier ]['color'],
        );
    }

    /**
     * Set the tier for a user.
     *
     * @param int    $user_id User ID.
     * @param string $tier    Tier slug (bronze, silver, gold, platinum, diamond) or empty to remove.
     * @return bool True on success, false on failure.
     */
    public static function set_user_tier( $user_id, $tier ) {
        if ( empty( $tier ) ) {
            return delete_user_meta( $user_id, self::META_KEY );
        }

        if ( ! array_key_exists( $tier, self::TIERS ) ) {
            return false;
        }

        return update_user_meta( $user_id, self::META_KEY, $tier );
    }

    /**
     * Check if a user has a specific tier or higher.
     *
     * @param string   $required_tier Required tier slug.
     * @param int|null $user_id       User ID. Defaults to current user.
     * @return bool True if user has required tier or higher.
     */
    public static function user_has_tier( $required_tier, $user_id = null ) {
        $user_tier = self::get_user_tier( $user_id );

        if ( null === $user_tier ) {
            return false;
        }

        $tier_order = array_keys( self::TIERS );
        $user_rank  = array_search( $user_tier, $tier_order, true );
        $req_rank   = array_search( $required_tier, $tier_order, true );

        if ( false === $user_rank || false === $req_rank ) {
            return false;
        }

        return $user_rank >= $req_rank;
    }

    /**
     * Render the tier badge HTML for a user.
     *
     * @param int|null $user_id User ID. Defaults to current user.
     * @param array    $args    Optional. Arguments for badge display.
     * @return string HTML for the tier badge or empty string if no tier.
     */
    public static function get_tier_badge_html( $user_id = null, $args = array() ) {
        $tier_data = self::get_user_tier_data( $user_id );

        if ( null === $tier_data ) {
            return '';
        }

        $defaults = array(
            'class' => 'user-tier-badge',
            'style' => '',
        );

        $args = wp_parse_args( $args, $defaults );

        $style = sprintf(
            'background-color: %s; padding: 2px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; color: #333; display: inline-block; %s',
            esc_attr( $tier_data['color'] ),
            esc_attr( $args['style'] )
        );

        return sprintf(
            '<span class="%s" style="%s">%s</span>',
            esc_attr( $args['class'] ),
            $style,
            esc_html( $tier_data['label'] )
        );
    }

    /**
     * Display the tier badge for a user.
     *
     * @param int|null $user_id User ID. Defaults to current user.
     * @param array    $args    Optional. Arguments for badge display.
     */
    public static function display_tier_badge( $user_id = null, $args = array() ) {
        echo self::get_tier_badge_html( $user_id, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Render the tier field in user profile.
     *
     * @param WP_User|string $user User object or form context.
     */
    public static function render_tier_field( $user ) {
        if ( ! current_user_can( 'edit_users' ) ) {
            return;
        }

        $current_tier = '';
        if ( $user instanceof WP_User ) {
            $current_tier = self::get_user_tier( $user->ID );
        }
        ?>
        <h3><?php esc_html_e( 'User Tier', 'commons' ); ?></h3>
        <table class="form-table" role="presentation">
            <tr>
                <th>
                    <label for="user_tier"><?php esc_html_e( 'Membership Tier', 'commons' ); ?></label>
                </th>
                <td>
                    <select name="user_tier" id="user_tier">
                        <option value=""><?php esc_html_e( '— No Tier —', 'commons' ); ?></option>
                        <?php foreach ( self::TIERS as $slug => $tier ) : ?>
                            <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $current_tier, $slug ); ?>>
                                <?php echo esc_html( $tier['label'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e( 'Select a membership tier for this user.', 'commons' ); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save the tier field from user profile.
     *
     * @param int $user_id User ID being saved.
     */
    public static function save_tier_field( $user_id ) {
        if ( ! current_user_can( 'edit_users' ) ) {
            return;
        }

        if ( ! isset( $_POST['user_tier'] ) ) {
            return;
        }

        $tier = sanitize_text_field( wp_unslash( $_POST['user_tier'] ) );
        self::set_user_tier( $user_id, $tier );
    }

    /**
     * Add tier column to users list.
     *
     * @param array $columns Existing columns.
     * @return array Modified columns.
     */
    public static function add_tier_column( $columns ) {
        $columns['user_tier'] = __( 'Tier', 'commons' );
        return $columns;
    }

    /**
     * Render tier column content.
     *
     * @param string $value       Column value.
     * @param string $column_name Column name.
     * @param int    $user_id     User ID.
     * @return string Column content.
     */
    public static function render_tier_column( $value, $column_name, $user_id ) {
        if ( 'user_tier' !== $column_name ) {
            return $value;
        }

        $badge = self::get_tier_badge_html( $user_id );
        return $badge ? $badge : '—';
    }

    /**
     * Make tier column sortable.
     *
     * @param array $columns Sortable columns.
     * @return array Modified sortable columns.
     */
    public static function make_tier_column_sortable( $columns ) {
        $columns['user_tier'] = 'user_tier';
        return $columns;
    }

    /**
     * Handle sorting by tier.
     *
     * @param WP_User_Query $query User query.
     */
    public static function sort_by_tier( $query ) {
        if ( ! is_admin() ) {
            return;
        }

        $orderby = $query->get( 'orderby' );

        if ( 'user_tier' === $orderby ) {
            $query->set( 'meta_key', self::META_KEY );
            $query->set( 'orderby', 'meta_value' );
        }
    }

    /**
     * Add bulk actions for tier management.
     *
     * @param array $actions Existing bulk actions.
     * @return array Modified bulk actions.
     */
    public static function add_bulk_actions( $actions ) {
        if ( ! current_user_can( 'edit_users' ) ) {
            return $actions;
        }

        foreach ( self::TIERS as $slug => $tier ) {
            $actions[ 'set_tier_' . $slug ] = sprintf(
            /* translators: %s: Tier label */
                __( 'Set Tier: %s', 'commons' ),
                $tier['label']
            );
        }

        $actions['remove_tier'] = __( 'Remove Tier', 'commons' );

        return $actions;
    }

    /**
     * Handle bulk actions for tier management.
     *
     * @param string $redirect_url Redirect URL.
     * @param string $action       Action being performed.
     * @param array  $user_ids     User IDs.
     * @return string Modified redirect URL.
     */
    public static function handle_bulk_actions( $redirect_url, $action, $user_ids ) {
        if ( ! current_user_can( 'edit_users' ) ) {
            return $redirect_url;
        }

        $tier_to_set = null;
        $remove_tier = false;

        if ( 'remove_tier' === $action ) {
            $remove_tier = true;
        } elseif ( strpos( $action, 'set_tier_' ) === 0 ) {
            $tier_to_set = str_replace( 'set_tier_', '', $action );
            if ( ! array_key_exists( $tier_to_set, self::TIERS ) ) {
                return $redirect_url;
            }
        } else {
            return $redirect_url;
        }

        $count = 0;
        foreach ( $user_ids as $user_id ) {
            if ( $remove_tier ) {
                self::set_user_tier( $user_id, '' );
            } else {
                self::set_user_tier( $user_id, $tier_to_set );
            }
            $count++;
        }

        return add_query_arg( 'tier_updated', $count, $redirect_url );
    }

    /**
     * Display admin notices for bulk actions.
     */
    public static function bulk_action_notices() {
        if ( ! empty( $_REQUEST['tier_updated'] ) ) {
            $count = intval( $_REQUEST['tier_updated'] );
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                esc_html(
                    sprintf(
                    /* translators: %d: Number of users updated */
                        _n(
                            'Tier updated for %d user.',
                            'Tier updated for %d users.',
                            $count,
                            'commons'
                        ),
                        $count
                    )
                )
            );
        }
    }

    /**
     * Enqueue admin styles.
     *
     * @param string $hook Current admin page hook.
     */
    public static function enqueue_admin_styles( $hook ) {
        if ( 'users.php' !== $hook && 'user-edit.php' !== $hook && 'profile.php' !== $hook ) {
            return;
        }

        $css = '
			.column-user_tier {
				width: 80px;
			}
			.user-tier-badge {
				text-shadow: 0 1px 0 rgba(255,255,255,0.5);
			}
		';

        wp_add_inline_style( 'common', $css );
    }

    /**
     * Get all available tiers.
     *
     * @return array Array of tier data.
     */
    public static function get_all_tiers() {
        return self::TIERS;
    }
}

// Initialize the class.
User_Tier::init();

/**
 * Helper function to get a user's tier.
 *
 * @param int|null $user_id User ID. Defaults to current user.
 * @return string|null Tier slug or null if no tier set.
 */
function get_user_tier( $user_id = null ) {
    return User_Tier::get_user_tier( $user_id );
}

/**
 * Helper function to get a user's tier label.
 *
 * @param int|null $user_id User ID. Defaults to current user.
 * @return string Tier label or empty string if no tier set.
 */
function get_user_tier_label( $user_id = null ) {
    return User_Tier::get_user_tier_label( $user_id );
}

/**
 * Helper function to get a user's tier data (slug, label, color).
 *
 * @param int|null $user_id User ID. Defaults to current user.
 * @return array|null Tier data or null if no tier set.
 */
function get_user_tier_data( $user_id = null ) {
    return User_Tier::get_user_tier_data( $user_id );
}

/**
 * Helper function to display a user's tier badge.
 *
 * @param int|null $user_id User ID. Defaults to current user.
 * @param array    $args    Optional. Arguments for badge display.
 */
function display_user_tier_badge( $user_id = null, $args = array() ) {
    User_Tier::display_tier_badge( $user_id, $args );
}

/**
 * Helper function to get a user's tier badge HTML.
 *
 * @param int|null $user_id User ID. Defaults to current user.
 * @param array    $args    Optional. Arguments for badge display.
 * @return string HTML for the tier badge or empty string if no tier.
 */
function get_user_tier_badge_html( $user_id = null, $args = array() ) {
    return User_Tier::get_tier_badge_html( $user_id, $args );
}

/**
 * Helper function to check if a user has a specific tier or higher.
 *
 * @param string   $required_tier Required tier slug.
 * @param int|null $user_id       User ID. Defaults to current user.
 * @return bool True if user has required tier or higher.
 */
function user_has_tier( $required_tier, $user_id = null ) {
    return User_Tier::user_has_tier( $required_tier, $user_id );
}

/**
 * Helper function to set a user's tier.
 *
 * @param int    $user_id User ID.
 * @param string $tier    Tier slug or empty to remove.
 * @return bool True on success, false on failure.
 */
function set_user_tier( $user_id, $tier ) {
    return User_Tier::set_user_tier( $user_id, $tier );
}