<?php
/**
 * COmanage API
 *
 * A limited set of functions to access the IDMS REST API from Humanities Commons
 *
 * @package Humanities Commons
 * @subpackage Configuration
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class comanageApi {

	protected $shared_bearer_key;
	public $url;
	public $api_args;

	public function __construct() {

		try {

            // set variables and create Bearer token API args
			
			$this->url = getenv( 'PROFILES_API_URL' );
			$this->shared_bearer_key = getenv( 'PROFILES_API_BEARER' );

			$this->api_args = [ 
				'headers' => [ 
					'Authorization' => 'Bearer ' . $this->shared_bearer_key
				]
			];

		} catch( Exception $e ) {
			//echo 'Caught Exception: ' . $e->getMessage() . '<br />';
			return;
		}
	} 



	/**
	 * Gets a user object from the Profiles API
	 * 
	 * @param  string       $username  wordpress user object
	 * @return array|object $req   json decoded array of objects from the request to comanage api        
	 */
	public function get_co_person( $username ) {
		$req = wp_remote_get( $this->url . 'users/' . $username, $this->api_args );
		if ( is_wp_error( $req ) ) {
			return false;
		}

        $body = (string) wp_remote_retrieve_body( $req );

		$data = json_decode( $body, true );

        return $data["results"];

	}


	/**
	 * Gets COU for output into global class variable, returns all cous by default
	 *
	 * @param  string  $society_id
	 * @return array   $cous    array of items retrieved from the comanage api
	 */
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
	 * Checks if the user's society role is still active
	 *
	 * @param  string     $wordpress_username  wordpress username of logged in user
	 * @param  string     $society_id  society to check
	 * @return array
	 */
	public function get_person_roles( $wordpress_username, $society_id = '' ) {

		// get the ID in IDMS for the current logged-in user
		$co_person = $this->get_co_person( $wordpress_username );

        // get the roles from the external_sync_memberships key
		$roles = $co_person["external_sync_memberships"];

		// retrieve current society COU from API or retrieve all
		$cous = $this->get_cous( $society_id );
		$roles_found = array();

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
		return $roles_found;

	}

}

$comanage_api = new comanageApi;
