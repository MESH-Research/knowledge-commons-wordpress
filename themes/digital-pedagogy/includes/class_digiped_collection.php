<?php

/**
 * Class DigiPed_Collection
 * Description: The DigiPed_Collection class is a utility class that facilitates WP based CRUD actions regarding
 * DigiPed Specific mapping and operations between Artifacts and Collections.
 *
 * Collection records are created by the end user and can be renamed or deleted. Collections are unique to user
 * accounts. Artifacts records are pre-loaded, never change and are always read only to the end user. Artifacts are
 * shared resources.
 *
 * A collection can consist of an unlimited number of unique artifacts that other collections can contain as well.
 * Collection cpt track the many to one relationships of their Artifact cpt via the Collection's Comments table
 * using the content field to track the artifact ID's allowing the collection to refer to Artifact post without
 * modifying the original artifact record.
 *
 * Created By: Joseff Betancourt
 * Created: 2019-03-19
 * License: GPLv3
 */
class DigiPed_Collection {
	/**
	 * Each call of this class is instanced and can be recalled.
	 */
	private static $instance = null;
	public $id, $name, $artifacts, $collection;

	/**
	 * DigiPed_Collection constructor.
	 *
	 * @param int $collection_id
	 */
	public function __construct( $collection_id = 0 ) {
		if ( $collection_id !== $this->id ) {
			$this->set_collectionByID( $collection_id );
		}
	}

	/**
	 * Description: Method to get the current instance of any DigiPed_Collection object.
	 *
	 * @return DigiPed_Collection|null
	 */
	public static function getInstance() {
		if ( self::$instance == null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Description: Method to lookup an artifact object by it's title.
	 *
	 * @param string $name
	 *
	 * @return object|WP_Post|null
	 */
	public function artifact_getIDByName( string $name ) {
		return get_page_by_title( $name, OBJECT, 'artifact' );
	}

	/**
	 * Description: Used by construct to set the current collection data.
	 * Is public because a static function calls this as well.
	 *
	 * @param $collection_id
	 * #throws USER_NOTICE if no collection is specified.
	 */
	public function set_collectionByID( $collection_id ) {
		try {
			$this->collection = $this->collection_readByID( $collection_id );
			$this->id         = $this->collection['id'];
			$this->name       = $this->collection['name'];
			$this->artifacts  = $this->collection['artifacts'];
		} catch ( Exception $e ) {
			error_log( print_r( "Notice: New collection class with no id.", true ) );

		}
	}

	/**
	 * Description: Method to recover active collection ID
	 *
	 * @return int|null
	 */
	public function get_collectionID() {
		return $this->id;
	}

	/**
	 * Description: Method to recover all the artifact ids beloging to a collection.
	 * @param null $collection_id
	 *
	 * @return array|null
	 */
	public static function get_artifacts( $collection_id = null ) {
		$that = self::getInstance();
		if ( $collection_id ) {
			if ( $that && $that->id != $collection_id ) {
				$that->set_collectionByID( $collection_id );
				//new ID
			}
			//same ID so pass the existing artifacts
		}

		//return existing artifacts in instance
		return $that->artifacts;
	}


	/**
	 * Description: Method to recover the collection object from it's title.
	 * @param string $name
	 *
	 * @return object|WP_Post|null
	 */
	public function collection_getIDByName( string $name ) {
		return get_page_by_title( $name, OBJECT, 'collection' );
	}

	/**
	 * Description: Creates an RFC 4122 compliant v4 uuid;
	 * Referenced from https://stackoverflow.com/questions/2040240/php-function-to-generate-v4-uuid
	 * @return string
	 */
	public function gen_uuid($data = null) {
		if(!$data) {
			//empty data so we generate some our selves.
			$data = openssl_random_pseudo_bytes(16);
		}
		assert(strlen($data) == 16);

		$data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}

	/**
	 * Description: Creates a new empty collection cpt.
	 * @param string $name of collection to be created. Duplicate names will be accepted.
	 *
	 * @return int|WP_Error
	 * @throws Exception if post is no name is created or a post cannot be created for another reason. Duplicate names will not cause the error.
	 */
	public function collection_createByName( string $name ) {
		try {
			if (!$name) {
				throw new Exception();
			}
			$this->id         = wp_insert_post( array(
				'post_status' => 'publish',
				'post_type'   => 'collection',
				'post_title'  => $name,
				'post_name' => $this->gen_uuid() // creates a global unique ID for slug i.e. 12345-12331-12312-12313
			), false );
			$this->name       = $name;
			$this->collection = array( "id" => $this->id, "name" => $name, "artifacts" => [] );

			return $this->id;
		} catch ( Exception $e ) {
			error_log( print_r( "Error: " . $e->getMessage(), true ) );
			throw new Exception( "Collection '$name' could not be created." );
		}
	}

	/**
	 * Description: pulls the collection and artifacts associated to it. Returns array containing it's collection id, title and artifact ids.
	 * @param int $collection_id
	 *
	 * @return array
	 * @throws Exception
	 */
	public function collection_readByID( int $collection_id = 0 )
    {
		if ( $this->id === $collection_id ) {

			return $this->collection;
		}

		if ( $collection = get_post( $collection_id ) ) {

			$comments_query = new WP_Comment_Query();
			$comments       = $comments_query->query( array(
				'orderby' => 'comment_karma',
				'order'   => 'ASC',
				'post_id' => $collection_id,
				'status'  => ['all','spam']

            ) );

			$artifacts      = array();
			foreach ( $comments as $artifact ) {
				$artifacts[] = (int) strip_tags( $artifact->comment_content );
			}
//            error_log(print_r($collection_id,true));
//            error_log(print_r($artifacts,true));
			return array( "id" => $collection_id, "name" => $collection->post_title, "artifacts" => $artifacts );
		} else {
			error_log( print_r( "Error: collection_readByID: Collection '$collection_id' not found.", true ) );
			throw new Exception( "Collection '$collection_id' not found." );
		}
	}

	/**
	 * Description: Update a collection cpt by its db id.
	 * @param string $name
	 * @param int    $collection_id
	 *
	 * @return int|WP_Error
	 * @throws Exception
	 */
	public function collection_updateByID( string $name, int $collection_id = 0 ) {
		if ( $this->id && empty( $collection_id ) ) {
			$collection_id = $this->id;
		}

		$my_post = array(
			'ID'         => $collection_id,
			'post_title' => $name,
		);

		$updated = wp_update_post( $my_post );
		if ( $updated ) {
			return $updated;
		} else {
			$msg = "Collection '$collection_id' not be updated.";
			error_log( print_r( "Error: collection_updateByID: (name = $name,
			 collection_id = $collection_id) \n $msg", true ) );
			throw new Exception( $msg );
		}
	}

	/**
	 * Description: Deleted a collection record by it's db id. Does not delete comments, orphaning them..
	 * @param $collection_id
	 *
	 * @return false|WP_Post|null
	 * @throws Exception
	 */
	public function collection_deleteByID( int $collection_id = 0 ) {
		if ( $this->id && empty( $collection_id ) ) {
			$collection_id = $this->id;
		}
		$deleted = wp_delete_post( $collection_id, true );
		if ( $deleted ) {
			return $deleted;
		} else {
			$msg = "Unable to delete collection '$collection_id'.";
			error_log( print_r( "Error: collection_deleteByID: (collection_id = $collection_id) \n $msg",
				true ) );
			throw new Exception( $msg );
		}

	}

	/**
	 * Description: Creates a comment record representing an artifact
	 * @param int $collection_id
	 * @param int $artifact_id
	 *
	 * @return false|int|WP_Error
	 * @throws Exception
	 */
	public function artifact_createToCollectionByID( int $artifact_id, int $collection_id = 0 ) {
		if ( $this->id && empty( $collection_id ) ) {
			$collection_id = $this->id;
		}
		$commentdata = array(
			'comment_post_ID'  => $collection_id,
			'comment_type'     => 'artifact',
			'comment_approved' => 1,
			'comment_content'  => $artifact_id,
			'user_id'          => get_current_user_id(),
		);

		//Insert new comment and get the comment ID
		$comment_id = wp_new_comment( $commentdata );
		if ( ! $comment_id ) {
			$msg = "Unable to save to collection '$collection_id'.";
			error_log( print_r( "Error: artifact_createToCollectionByID: (artifact_id = $artifact_id,
			 collection_id = $collection_id) \n $msg", true ) );
			throw new Exception( $msg );
		}

		return $comment_id;
	}

	/**
	 * Description: delete a comment record from collection cpt.
	 * @param $comment_id
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function artifact_deleteFromCollectionByID( $comment_id ) {
		$deleted = wp_delete_comment( $comment_id, true );
		if ( $deleted ) {
			return $deleted;
		} else {
			$msg = "Unable to remove comment id: '$comment_id' from collection.";
			error_log( print_r( "Error: artifact_deleteFromCollectionByID: (comment_id = $comment_id) \n $msg",
				true ) );
			throw new Exception( $msg );
		}
	}
}
