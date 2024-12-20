<?php
/**
 * Filters for wp-job-manager, used by jobs.up.hcommons.org
 */

 /**
  * Only UP members should be able to post jobs on jobs.up.hcommons.org.
  *
  * @see wp-job-manager/templates/job-submit.php
  * 
  * @author Mike Thicke
  * 
  * @param bool $can_post Whether the user can post a job.
  *
  * @return bool Returns true if the user can post a job (if they are a UP member)
  */
function hc_custom_wp_jobs_can_post_job_up( $can_post ) {
	$current_site = get_site_url();
	if ( ! $can_post || strpos( $current_site, 'jobs.up.hcommons' ) === false ) {
		return $can_post;
	}
	if ( ! class_exists( 'Humanities_Commons' ) ) {
		return false;
	}
	
	$memberships = Humanities_Commons::hcommons_get_user_memberships();
	if ( is_array($memberships['societies'] ) && in_array( 'up', $memberships['societies'] ) ) {
		return true;
	} else {
		return false;
	} 
}
add_filter( 'job_manager_user_can_post_job', 'hc_custom_wp_jobs_can_post_job_up', 10, 1 );

/**
 * Adds a message in place of the jobs submission form if a user does not have permission
 * to add a job posting.
 * 
 * @see wp-job-manager/templates/job-submit.php
 * 
 * @author Mike Thicke
 */
function hc_job_form_disabled_message() {
	?>
	<p id="hc-job-form-disabled-message">
	The AUPresses Jobs List is integrated with UP Commons, the collaborative online platform built for our community. 
	In order to post to the Jobs List, AUPresses members need to have an active account on UP Commons. If you don’t 
	already have a UP Commons account, please find 
		<a href='https://up.hcommons.org/up-commons-101/up-commons-101-lesson-1-getting-registered/'>
			instructions to register here.
		</a>
	</p>
	<p>
		If you are not a member of the Association, learn 
		<a href='https://jobs.up.hcommons.org/job-posting-service/'>
			more about advertising positions 
		</a>
		via the AUPresses Jobs List.
	</p>
	<?php
}
add_action( 'submit_job_form_disabled', 'hc_job_form_disabled_message', 10, 0 );