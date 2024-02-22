<?php

// This script will convert
// all confirmed buddy press friends to mutual followers
// if their friendship is not confirmed then the
// requestor of the friendship becomes a follower
// of the requestee   

$_SERVER['HTTP_HOST'] = 'pollan.vagrant.dev';
ob_start();
include('web/wp/wp-load.php');  //replace with your path to wordpress wp-load.php
ob_end_clean();

global $wpdb;

$sql = "SELECT * FROM wp_bp_friends";  // may need to repalce with your proper table prefix
$rows = $wpdb->get_results($sql, ARRAY_A);


foreach($rows as $row){
  // print_r($row);
  extract($row);

  $follow = new BP_Follow($initiator_user_id, $friend_user_id);
  $follow->save();

  if($is_confirmed == 1){
    $follow = new BP_Follow($friend_user_id, $initiator_user_id);
    $follow->save();
  }

}
