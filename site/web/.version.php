<?php
/**
 * Build-version endpoint.
 *
 * Reads .version.json, written at Docker build time by Dockerfile.php from
 * build-args set by the AWS ECR build workflow. Exposed for ops checks so a
 * running container can be quickly mapped back to a release.
 */
header('Content-Type: application/json');

$file = __DIR__ . '/.version.json';
if (is_file($file)) {
    echo file_get_contents($file);
    return;
}

echo json_encode([
    'version' => 'unknown',
    'build'   => 'unknown',
    'sha'     => 'unknown',
    'branch'  => 'unknown',
    'error'   => 'version file missing — image not built with version args',
]);
