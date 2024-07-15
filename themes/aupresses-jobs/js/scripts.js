jQuery(document).ready(function () {

  jQuery('a.google_map_link').contents().unwrap();

  var url = window.location.href;

  jQuery('#job-manager-job-dashboard .account-sign-in').html('<p style="font-size:18px; text-align:center; font-weight: bold;"><a href="/wp-login.php?action=shibboleth&redirect_to=' + url + '">Sign in</a> with your UP Commons account to manage your job posts.</p>');
  jQuery('.fieldset-login_required').html('<p style="font-size:18px; text-align:center; font-weight: bold;"><a href="/wp-login.php?action=shibboleth&redirect_to=' + url +'">Sign in</a> with your UP Commons account to create a new job post.</p>');
  jQuery('.fieldset-logged_in').hide();

});