<?php
	global $ezid_api;
        $eStatus = $ezid_api->server_status();
	echo "ezid status",var_export( $eStatus, true );

