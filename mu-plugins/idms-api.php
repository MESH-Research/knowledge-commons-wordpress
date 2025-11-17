<?php
/**
 * Plugin Name: IDMS Sync CLI (MU)
 * Description: MU plugin adding a WP-CLI command to sync and parse external memberships for a user.
 * Author: Martin Paul Eve / MESH Research
 * Version: 0.1.0
 */

namespace IDMS\CLI;

use WP_CLI;
use WP_CLI\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register command only within WP-CLI context.
 */
if ( defined( 'WP_CLI' ) && class_exists( 'WP_CLI' ) ) {

    /**
     * Sync external memberships for a given user and print/optionally save results.
     *
     * ## SYNOPSIS
     *
     *     wp idms sync-memberships <username>
     *
     *
     * @when after_wp_load
     */
    class Sync_Memberships_Command {
        protected $shared_bearer_key;
        public $url;
        public $api_args;


        /**
         * Main subcommand.
         *
         * @param array $args       Positional args.
         * @param array $assoc_args Assoc args.
         */
        public function __invoke( array $args, array $assoc_args ) : void {
            [$username] = $args;

            $user = get_user_by( 'login', $username );
            if ( ! $user ) {
                WP_CLI::error( sprintf( 'User with username "%s" not found.', $username ) );
            }

            $this->url = getenv( 'PROFILES_API_URL' );
            $this->shared_bearer_key = getenv( 'PROFILES_API_BEARER' );

            $timeout  = (int) ( $assoc_args['timeout'] ?? 15 );

            $request_args = [
                'method'      => 'GET',
                'headers'     => $this->build_headers($this->shared_bearer_key),
                'timeout'     => $timeout,
                'user-agent'  => $this->user_agent(),
            ];

            $endpoint = trailingslashit($this->url . 'members') . $username;

            /**
             * Filters to modify endpoint and request args before dispatch.
             */
            $endpoint     = apply_filters( 'IDMS/sync_endpoint', $endpoint, $user, $assoc_args );
            $request_args = apply_filters( 'IDMS/sync_request_args', $request_args, $user, $assoc_args );

            WP_CLI::log( sprintf( 'GET %s (timeout=%ds) …', $endpoint, $timeout ) );

            $res = wp_remote_get( $endpoint, $request_args );

            if ( is_wp_error( $res ) ) {
                WP_CLI::error( sprintf( 'HTTP request failed: %s', $res->get_error_message() ) );
            }

            $code = (int) wp_remote_retrieve_response_code( $res );
            $body = (string) wp_remote_retrieve_body( $res );

            if ( $code < 200 || $code >= 300 ) {
                WP_CLI::error( sprintf( 'HTTP %d — Body: %s', $code, $this->truncate($body) ) );
            }

            $json = json_decode( $body, true );
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                WP_CLI::error( sprintf( 'JSON decode error: %s', json_last_error_msg() ) );
            }

            WP_CLI::print_value( $json["results"]["external_sync_memberships"], [ 'json' => true ] );

            if ( ! isset( $json["results"]["external_sync_memberships"] ) ) {
                WP_CLI::warning( 'Response did not include a valid "external_sync_memberships" array.' );
                $memberships = [];
                return;
            } else {
                $memberships = $json["results"]["external_sync_memberships"];
            }

            // Output.
            $this->render_output( $memberships, "table" );

            $roles = $json["results"]["external_sync_memberships"];

            // retrieve current society COU from API or retrieve all
            $cous = $this->get_cous( "" );
            $roles_found = array();

            // loop over COUs
            foreach( $cous as $cou ) {
                // loop over external_sync_memberships
                foreach ( $roles as $key => $value ) {
                    if ($key == strtoupper($cou['name']) && $value) {
                        $roles_found[$cou['name']] = [
                            'status' => "ACTIVE",
                            'affiliation' => $key,
                            'o' => $key,
                        ];
                    } else if ($key == strtoupper($cou['name']) && !$value) {
                        $roles_found[$cou['name']] = [
                            'status' => "INACTIVE",
                            'affiliation' => $key,
                            'o' => $key,
                        ];
                    }
                }
            }

            ksort( $roles_found );
            WP_CLI::print_value( $roles_found, [ 'json' => true ] );

            kc_sync_bp_member_types_for_username($username, $memberships);
        }

        private function build_headers( ?string $token ) : array {
            $headers = [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ];
            if ( $token ) {
                $headers['Authorization'] = 'Bearer ' . $token;
            }
            return $headers;
        }

        private function user_agent() : string {
            global $wp_version;
            return sprintf( 'IDMSSyncCLI/0.1 (WordPress/%s; WP-CLI/%s; %s)', $wp_version, \WP_CLI_VERSION, home_url() );
        }

        private function truncate( string $s, int $len = 400 ) : string {
            $s = trim( $s );
            return ( strlen( $s ) > $len ) ? substr( $s, 0, $len ) . '…' : $s;
        }

        public function get_cous( $society_id = '' ) {


            $req = wp_cache_get( 'comanage_cous', 'hcommons_settings' );

            if ( ! $req ) {

                //Hard code COU values becasue REST API call gets a memory error on COmanage - PMO bug #329
                $temp_cous = array();
                $temp_cous['Cous'][] = [ 'Id' => '1', 'Name' => 'MLA',
                    'Description' => 'Modern Language Association' ];
                $temp_cous['Cous'][] = [ 'Id' => '2', 'Name' => 'CAA',
                    'Description' => 'College Art Association' ];
                $temp_cous['Cous'][] = [ 'Id' => '3', 'Name' => 'ASEEES',
                    'Description' => 'Association for Slavic, Eastern European, and Eurasian Studies' ];
                $temp_cous['Cous'][] = [ 'Id' => '4', 'Name' => 'AJS',
                    'Description' => 'Association for Jewish Studies' ];
                $temp_cous['Cous'][] = [ 'Id' => '5', 'Name' => 'HC',
                    'Description' => 'Humanities Commons' ];
                $temp_cous['Cous'][] = [ 'Id' => '6', 'Name' => 'UP',
                    'Description' => 'Association of American University Presses' ];
                $temp_cous['Cous'][] = [ 'Id' => '7', 'Name' => 'MSU',
                    'Description' => 'Michigan State University' ];
                $temp_cous['Cous'][] = [ 'Id' => '8', 'Name' => 'ARLISNA',
                    'Description' => 'ARLIS/NA' ];
                $temp_cous['Cous'][] = [ 'Id' => '10', 'Name' => 'SAH',
                    'Description' => 'SAH' ];
                $temp_cous['Cous'][] = [ 'Id' => '11', 'Name' => 'HUB',
                    'Description' => 'HUB' ];
                $temp_cous['Cous'][] = [ 'Id' => '12', 'Name' => 'SOCSCI',
                    'Description' => 'SOCSCI' ];
                $temp_cous['Cous'][] = [ 'Id' => '13', 'Name' => 'STEM',
                    'Description' => 'STEM' ];
                $temp_cous['Cous'][] = [
                    'Id'          => '13',
                    'Name'        => 'STEM',
                    'Description' => 'STEM'
                ];
                $temp_cous['Cous'][] = [
                    'Id'          => '14',
                    'Name'        => 'HASTAC',
                    'Description' => 'HASTAC'
                ];
                $temp_cous['Cous'][] = [
                    'Id'          => '15',
                    'Name'        => 'STEMEd+',
                    'Description' => 'STEM Ed+'
                ];
                $req['body'] = json_encode( $temp_cous );

                //$req = wp_remote_get( $this->url . '/cous.' . $this->format . '?coid=2', $this->api_args );
                wp_cache_set( 'comanage_cous', $req, 'hcommons_settings', 24 * HOUR_IN_SECONDS );
            }

            //json_decode the data from the request
            $data = json_decode( $req['body'], true );
            $cous = array();

            //loops through cou data to find the one matching the string in param
            foreach( $data['Cous'] as $item ) {

                if ( empty( $society_id ) || $item['Name'] == strtoupper( $society_id ) ) {

                    $cous[] = [
                        'id' => $item['Id'],
                        'name' => $item['Name'],
                        'description' => $item['Description']
                    ];

                }
            }

            return $cous;

        }

        /**
         * Normalize memberships array and render as table or JSON.
         *
         * The API may return either a list of scalars or a list of objects.
         * We try to coerce into rows with stable columns.
         */
        private function render_output( array $memberships, string $format ) : void {
            // Expect structure like:
            // [ 'MLA' => false, 'MSU' => true, 'ARLISNA' => false, 'UP' => false ]

            if ( empty( $memberships ) ) {
                WP_CLI::warning( 'No external memberships found.' );
                return;
            }

            // Normalise to a list of associative arrays for WP_CLI formatter
            $rows = [];
            foreach ( $memberships as $org => $is_member ) {
                $rows[] = [
                    'organisation' => (string) $org,
                    'is_member'    => $is_member ? 'true' : 'false',
                ];
            }

            if ( $format === 'json' ) {
                // Output clean JSON
                WP_CLI::print_value( $rows, [ 'json' => true ] );
                return;
            }

            // Default: table output
            WP_CLI\Utils\format_items( 'table', $rows, [ 'organisation', 'is_member' ] );

            WP_CLI::success( sprintf( 'Rendered %d external membership(s).', count( $rows ) ) );
        }
    }

    function kc_sync_bp_member_types_for_username( string $username, array $memberships ): void {
        if ( ! function_exists( 'bp_set_member_type' ) ) {
            // BuddyPress not loaded.
            WP_CLI::error( sprintf( 'No BuddyPress' ) );
            return;
        }

        $user = get_user_by( 'login', $username );
        if ( !$user ) {
            WP_CLI::error( sprintf( 'No User: %s', $username ) );
            return;
        }

        $user_id = $user->ID;

        // All the member-type slugs you’ve registered in hcommons_register_member_types().
        $all_known_types = [
            'arlisna',
            'aseees',
            'hc',
            'hub',
            'mla',
            'msu',
            'sah',
            'socsci',
            'stem',
            'up',
            'hastac',
            'dhri',
        ];

        WP_CLI::log( sprintf( 'Getting memberships...' ) );

        // What the API says this user should have.
        $desired_types = array_map('strtolower', array_keys(array_filter($memberships)));
        $desired_types = array_intersect( array_map('strtolower', $desired_types), $all_known_types ); // safety

        WP_CLI::print_value( $desired_types, [ 'json' => true ] );

        // What they currently have in BuddyPress.
        // Passing false gives all types; can be string, array, or false.
        $current_types = bp_get_member_type( $user_id, false );
        if ( ! is_array( $current_types ) ) {
            $current_types = $current_types ? [ $current_types ] : [];
        }

        // always set hc
        bp_set_member_type( $user_id, "hc", true );

        // Add missing types.
        foreach ( $desired_types as $type ) {
            WP_CLI::log( sprintf( 'Checking: %s for addition', $type ) );
            if ( ! in_array( $type, $current_types, true ) ) {
                // append = true means "add, don't overwrite existing types"
                bp_set_member_type( $user_id, $type, true );
                WP_CLI::log( sprintf( 'Added: %s', $type ) );

            }
        }

        // Remove types that are no longer valid.
        foreach ( $all_known_types as $type ) {
            WP_CLI::log( sprintf( 'Checking: %s for removal', $type ) );
            if ( in_array( $type, $current_types, true ) && ! in_array( $type, $desired_types, true ) && !$type == "hc") {
                bp_remove_member_type( $user_id, $type );
                // Alternatively: bp_set_member_type( $user_id, '' ) to clear ALL, but here we just remove one.
                WP_CLI::log( sprintf( 'Removed: %s', $type ) );
            }
        }
    }

    // Register as: wp idms sync-memberships <username> ...
    WP_CLI::add_command( 'idms sync-memberships', Sync_Memberships_Command::class );
}

