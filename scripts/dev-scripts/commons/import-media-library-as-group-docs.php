<?php
/**
 * Create BP Group Docs (files) out of PDFs in the media library.
 *
 * THIS ONLY WORKS ON THE ROOT BLOG (otherwise BP_Group_Documents does not load correctly).
 * So use the --url of the root blog for the appropriate network and these parameters:
 *
 * YOU HAVE TO EDIT THE PLUGIN CODE AS WELL.
 *   change move_uploaded_file() to copy() in UploadFile() in include/classes.php (is_uploaded_file() is false for these).
 *   comment out the check for bp_group_documents_check_ext() (allowed ext option is not correctly parsed).
 *
 *
 * @param int $blog_id  Blog ID of the site with a media library containing PDFs.
 * @param int $group_id Group ID where the docs should be created.
 *
 * wp --url=mla.hcommons.org eval-file import-media-library-as-group-docs.php 1 2
 */

if ( ! ( is_numeric( $args[0] ) && is_numeric( $args[1] ) ) ) {
	echo 'You did not provide the required arguments.';
	die;
}

$blog_id  = $args[0];
$group_id = $args[1];

//var_dump( get_blog_details( $blog_id ) );
//var_dump( groups_get_group( $group_id ) );
//die;

$args = [
	'post_type' => 'attachment',
	'post_mime_type' =>'application/pdf',
	'post_status' => 'inherit',
	'posts_per_page' => 999,
];

switch_to_blog( $blog_id );

$query_images = new WP_Query( $args );

foreach ( $query_images->posts as $post ) {
	switch_to_blog( $blog_id );

	wp_set_current_user( $post->post_author );

	$path = get_attached_file( $post->ID );

	if ( false !== strpos( $post->guid, 'bp-attachments' ) ) {
		var_dump( "found bp-attachment, moving on" );
		continue;
	}

	if ( ! $path ) {
		echo "could not find attached file";
		var_dump( $post );
		continue;
	}

	// this isn't being filtered correctly, but we can fix it
	$path = str_replace( 'current/web/wp/wp-content', 'shared', $path );

	if ( ! $path ) {
		var_dump( 'bad path!' );
		var_dump( $post );
		continue;
	}

	$raw = file_get_contents( $path );
	$tmp = tempnam( '/tmp', 'php_files' );

	file_put_contents( $tmp, $raw );

	$_FILES['bp_group_documents_file'] = [
		'name' => basename( $path ),
		'type' => $post->post_mime_type,
		'tmp_name' => $tmp,
		'error' => 0,
		'size' => strlen($raw),
	];

	restore_current_blog();

	$document = new BP_Group_Documents();
	$document->user_id = $post->post_author;
	$document->group_id = $group_id;
	$document->name = $post->post_title;
	$document->description = $post->post_excerpt;
	$document->created_ts = strtotime( $post->post_date );
	$document->modified_ts = strtotime( $post->post_date );

	// this requires editing the docs plugin!
	// change move_uploaded_file() to copy() in UploadFile() in include/classes.php (is_uploaded_file() is false for these).
	// comment out the check for bp_group_documents_check_ext() (allowed ext option is not correctly parsed).
	if ($document->save()) {
		var_dump( basename( $path ) . ' success' );
	} else {
		var_dump( basename( $path ) . ' error' );
	}

	//var_dump( $post );
	//var_dump( $document );
	//die;
}
