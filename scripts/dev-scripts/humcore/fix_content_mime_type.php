<?php

// Fix incorrect mimeType on a fedora resource object.
// Caused by deposit front end error.

global $fedora_api;

$rContent = $fedora_api->modify_datastream( array(
        'pid' => 'mla:3148',
        'dsID' => 'CONTENT',
        'mimeType' => 'application/pdf',
) );

