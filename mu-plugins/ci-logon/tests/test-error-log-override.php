<?php
/**
 * Override error_log in the MeshResearch\CILogon namespace to suppress
 * log output during tests.
 *
 * Since the plugin code is namespaced, PHP will look for error_log in the
 * namespace first before falling back to the global function. This allows
 * us to suppress noisy log output during unit tests.
 *
 * @package MeshResearch\CILogon\Tests
 */

namespace MeshResearch\CILogon;

/**
 * Mock error_log function for the CILogon namespace
 *
 * @param string $message The error message
 * @param int $message_type Where to send the error
 * @param string|null $destination The destination
 * @param string|null $headers Additional headers
 * @return bool Always returns true
 */
function error_log($message, $message_type = 0, $destination = null, $headers = null) {
    // Suppress error logs during tests
    // Uncomment below to see logs during test debugging:
    // echo "[TEST LOG] $message\n";
    return true;
}
