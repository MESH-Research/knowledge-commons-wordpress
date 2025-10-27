<?php
/**
 * COmanage API
 *
 * A limited set of functions to access the COmanage REST API from Humanities Commons
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
		$req = wp_remote_get( $this->url . '/users/' . $username, $this->api_args );
		if ( is_wp_error( $req ) ) {
			return false;
		}

		$data = json_decode( $req['body'] );

		return $data;

	}

	/**
	 * Gets role from co_person by passing in person_id
	 * 
	 * @param  int    $co_person_id  CO user id
	 * 
	 * @return object $req			  object from api if request is successful               
	 */
	public function get_co_person_role( $co_person_id ) {
		
		//GET /co_person_roles.<format>?copersonid=
		$req = wp_remote_get( $this->url . '/co_person_roles.' . $this->format . '?copersonid=' . $co_person_id,  $this->api_args );

		$data = json_decode( $req['body'] );
		
		return $data;

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

		//lets get the ID in comanage for the current logged in user
		$co_person = $this->get_co_person( $wordpress_username );
		
		if ( false === $co_person ) {
			return false;
		}
		//multiple records - find first active
		foreach( $co_person as $person_record ) {
			if ( $person_record[0]->CoId == "2" && $person_record[0]->Status == 'Active' ) {
				$co_person_id = $person_record[0]->Id;
				break 1;
			}
		}
		//gets all of the roles the person currently has
		$co_person_roles = $this->get_co_person_role( $co_person_id );

		$roles = $co_person_roles->CoPersonRoles;

		//retrieve current society COU from API or retrieve all
		$cous = $this->get_cous( $society_id );
		$roles_found = array();

		foreach( $cous as $cou ) {

			//loop through each role
			foreach( $roles as $role ) {
				//check if each role matches the cou id of the society and provide a case for each status
				if( $role->CouId == $cou['id'] ) {

					$roles_found[$cou['name']] = [
						'status' => $role->Status,
						'affiliation' => $role->Affiliation,
						'title' => $role->Title,
						'o' => $role->O,
						'valid_from' => substr( $role->ValidFrom, 0, 10 ),
						'valid_through' => substr( $role->ValidThrough, 0, 10 ),
					];

				}

			}

		}

		ksort( $roles_found );
		return $roles_found;

	}

}

$comanage_api = new comanageApi;
