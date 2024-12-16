<?php

/**
 * Short description
 *
 * @package blog-avatar
 */

// Exit if access directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BD_Blog_Avatar_Admin
 */
class BD_Blog_Avatar_Admin {
	/**
	 * Holds class object
	 *
	 * @var static $instance holds class object.
	 */
	private static $instance;
	/**
	 * Step information
	 *
	 * @var string step information
	 */
	private $step = 'upload';
	/**
	 * Holds Message information
	 *
	 * @var string
	 */
	private $message = '';
	/**
	 * BD_Blog_Avatar_Admin constructor.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'handler' ) );
	}

	/**
	 * Return class object
	 *
	 * @return BD_Blog_Avatar_Admin
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get step
	 *
	 * @return string
	 */
	function get_step() {
		return $this->step;
	}
	/**
	 * Add Admin Menus
	 */
	public function add_menu() {

		add_options_page( __( 'Blog Avatar', 'blog-avatar' ), __( 'Blog Avatar', 'blog-avatar' ), 'manage_options', 'blog-avatar', array(
			$this,
			'upload_screen',
		) );
	}
	/**
	 * Avatar upload form
	 */
	public function upload_form() {
		?>
        <h3><?php esc_html_e( 'Blog Avatar', 'blog-avatar' ); ?></h3>
        <div class="existing-blog-avatar">
            <?php bd_blog_avatar(); ?>
			<?php if ( get_blog_option( get_current_blog_id(), 'has_avatar' ) ): ?>
                <form action="" method="post" id="blog-avatar-upload-form" class="blog-avatar-upload-form"
                      enctype="multipart/form-data">
                    <input type="submit" class="btn button " value="<?php esc_html_e( 'Delete', 'blog-avatar' ); ?>"/>
                    <input type="hidden" name='blog-avatar-action' value='delete'/>
					<?php wp_nonce_field( 'blog-avatar' ); ?>
                </form>
			<?php endif; ?>
        </div>

        <div class="blog-avatar-upload-form">
            <p><?php esc_html_e( 'Upload an image to use as an avatar for this blog. The image will be shown on the blog directory page, and in search results.', 'blog-avatar' ); ?></p>
            <form action="" method="post" id="blog-avatar-upload-form" class="blog-avatar-upload-form"
                  enctype="multipart/form-data">
                <p>
                    <input type="file" name="file" id="file"/>
                    <input type="submit" name="upload" id="upload"
                           value="<?php esc_html_e( 'Upload Image', 'blog-avatar' ); ?>"/>
                    <input type="hidden" name="action" id="action" value="bp_avatar_upload"/>
                    <input type="hidden" name="blog-avatar-action" id="blog-avatar-action" value="upload-avatar"/>
					<?php wp_nonce_field( 'blog-avatar' ); ?>
                </p>
            </form>
        </div>
		<?php
	}

	/**
	 * Crop form
	 */
	public function crop_form() {
		?>
        <h3><?php esc_html_e( 'Crop Blog Avatar', 'blog-avatar' ); ?></h3>
        <form action="" method="post" id="blog-avatar-upload-form" class="blog-avatar-upload-form"
              enctype="multipart/form-data">
            <img src="<?php bp_avatar_to_crop(); ?>" id="avatar-to-crop" class="avatar"
                 alt="<?php esc_attr_e( 'Avatar to crop', 'blog-avatar' ); ?>"/>

            <div id="avatar-crop-pane">
                <img src="<?php bp_avatar_to_crop(); ?>" id="avatar-crop-preview" class="avatar"
                     alt="<?php echo esc_attr_e( 'Avatar preview', 'blog-avatar' ); ?>"/>
            </div>

            <input type="submit" name="avatar-crop-submit" id="avatar-crop-submit"
                   value="<?php esc_attr_e( 'Crop Image', 'blog-avatar' ); ?>"/>

            <input type="hidden" name="image_src" id="image_src" value="<?php bp_avatar_to_crop_src(); ?>"/>
            <input type="hidden" name="upload" id="upload"/>
            <input type="hidden" id="x" name="x"/>
            <input type="hidden" id="y" name="y"/>
            <input type="hidden" id="w" name="w"/>
            <input type="hidden" id="h" name="h"/>
            <input type="hidden" name="blog-avatar-action" id="blog-avatar-action" value="upload-avatar"/>
			<?php wp_nonce_field( 'blog-avatar' ); ?>
        </form>
	<?php }

	/**
	 * Show upload or crop form
	 */
	public function show_form() {
		?>
        <div class="wrap">
			<?php if ( $this->message ): ?>
                <div class="updated fade">
                    <p><?php echo $this->message; ?></p>
                </div>
			<?php endif; ?>
			<?php
			if ( $this->get_step() == 'crop' ) {
				$this->crop_form();
			} else {
				$this->upload_form();
			}
			?>

        </div>
		<?php
	}

	/**
	 * Handle request
	 */
	public function handler() {
		$bp = buddypress();

		if ( ! empty( $_POST['blog-avatar-action'] ) ) {

			if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'blog-avatar' ) ) {
				wp_die( "Sorry, you don't have permission!" );
			}

			//check if action is set to delete, then delete existing avatar
			$blog_id = get_current_blog_id();
			if ( $_POST['blog-avatar-action'] == 'delete' ) {
				bp_core_delete_existing_avatar( array(
					'item_id' => $blog_id,
					'object'  => 'blog'


				) );
				delete_blog_option( $blog_id, 'has_avatar' );
				$this->message = __( 'Avatar Deleted successfully!', 'blog-abvatar' );

				return;

			}


			//make sure there was aform submitted
			if ( ! isset( $bp->avatar_admin ) ) {
				$bp->avatar_admin = new stdClass();
			}


			if ( ! empty( $_FILES ) && isset( $_POST['upload'] ) ) {
				// Normally we would check a nonce here, but the group save nonce is used instead

				// Pass the file to the avatar upload handler
				if ( bp_core_avatar_handle_upload( $_FILES, 'bd_blog_avatar_upload_dir' ) ) {
					$bp->avatar_admin->step = 'crop-image';
					$this->step             = 'crop';
					// Make sure we include the jQuery jCrop file for image cropping
					add_action( 'admin_print_scripts-settings_page_blog-avatar', array( $this, 'add_jquery_cropper' ) );

				}
			}

			// If the image cropping is done, crop the image and save a full/thumb version
			if ( isset( $_POST['avatar-crop-submit'] ) && isset( $_POST['upload'] ) ) {
				// Normally we would check a nonce here, but the group save nonce is used instead

				if ( ! bp_core_avatar_handle_crop( array(
					'object'        => 'blog',
					'avatar_dir'    => 'blog-avatars',
					'item_id'       => get_current_blog_id(),
					'original_file' => $_POST['image_src'],
					'crop_x'        => $_POST['x'],
					'crop_y'        => $_POST['y'],
					'crop_w'        => $_POST['w'],
					'crop_h'        => $_POST['h']
				) )
				) {
					$this->message = __( 'There was an error saving the blog avatar, please try uploading again.', 'blog-avatar' );
				} else {
					$this->message = __( 'The Blog avatar was uploaded successfully!', 'blog-avatar' );

					update_blog_option( get_current_blog_id(), 'has_avatar', 1 );
				}
			}
		}
	}

	// the blog avatar screen
	public function upload_screen() {
        $this->show_form();
	}

	/**
	 * Add necessery javascript libraries
	 */
	public function add_jquery_cropper() {
		wp_enqueue_style( 'jcrop' );
		wp_enqueue_script( 'jcrop', array( 'jquery' ) );
		add_action( 'admin_head', 'bp_core_add_cropper_inline_js' );
		add_action( 'admin_head', 'bp_core_add_cropper_inline_css' );
	}
}

BD_Blog_Avatar_Admin::get_instance();
    
