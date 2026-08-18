<?php
/**
 * Corrected copy of the buddypress-docs legacy tabs template.
 *
 * Two changes from the upstream template:
 *
 * 1. The "[group]'s Docs" tab URL is echoed properly (upstream calls
 *    esc_url() without echoing, producing a relative href="docs" that can
 *    resolve to another group's doc).
 * 2. When viewing a single doc outside the group context, a tab linking back
 *    to the owning group's docs list is added.
 *
 * @package Hc_Custom
 */

?>
<ul id="bp-docs-all-docs">
	<li<?php if ( bp_docs_is_global_directory() ) : ?> class="current"<?php endif; ?>><a href="<?php bp_docs_archive_link(); ?>"><?php _e( 'All Docs', 'buddypress-docs' ); ?></a></li>

	<?php if ( function_exists( 'bp_is_group' ) && bp_is_group() ) : ?>
		<li<?php if ( bp_is_current_action( BP_DOCS_SLUG ) ) : ?> class="current"<?php endif; ?>><a href="<?php echo esc_url( hc_custom_get_group_docs_url( groups_get_current_group() ) ); ?>"><?php echo esc_html( sprintf( __( "%s's Docs", 'buddypress-docs' ), bp_get_current_group_name() ) ); ?></a></li>
	<?php else : ?>
		<?php
		// On a single doc, link back to the owning group's docs list.
		$hc_custom_doc_group_tab = null;

		if ( function_exists( 'bp_docs_is_existing_doc' ) && bp_docs_is_existing_doc() && function_exists( 'bp_docs_get_current_doc' ) ) {
			$hc_custom_current_doc = bp_docs_get_current_doc();

			if ( ! empty( $hc_custom_current_doc->ID ) ) {
				$hc_custom_doc_group_tab = hc_custom_get_doc_group_docs_tab( $hc_custom_current_doc->ID );
			}
		}
		?>
		<?php if ( $hc_custom_doc_group_tab ) : ?>
			<li><a href="<?php echo esc_url( $hc_custom_doc_group_tab['url'] ); ?>"><?php echo esc_html( $hc_custom_doc_group_tab['label'] ); ?></a></li>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( is_user_logged_in() ) : ?>
		<?php if ( ! function_exists( 'bp_is_group' ) || ! bp_is_group() ) : ?>
			<li><a href="<?php bp_docs_mydocs_started_link(); ?>"><?php _e( 'Started By Me', 'buddypress-docs' ); ?></a></li>
			<li><a href="<?php bp_docs_mydocs_edited_link(); ?>"><?php _e( 'Edited By Me', 'buddypress-docs' ); ?></a></li>

			<?php if ( bp_is_active( 'groups' ) ) : ?>
				<li<?php if ( bp_docs_is_mygroups_docs() ) : ?> class="current"<?php endif; ?>><a href="<?php bp_docs_mygroups_link(); ?>"><?php _e( 'My Groups', 'buddypress-docs' ); ?></a></li>
			<?php endif; ?>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( $show_create_button ) : ?>
		<?php bp_docs_create_button(); ?>
	<?php endif; ?>

</ul>
