<?php
/**
 * Tests for the IDMS update-avatar REST API endpoint.
 *
 * @package MeshResearch\CILogon\Tests
 */

use MeshResearch\CILogon\Plugin;
use PHPUnit\Framework\TestCase;

class UpdateAvatarTest extends TestCase
{
    private $originalEnv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalEnv = getenv('PROFILES_API_BEARER_TOKEN');
        putenv('PROFILES_API_BEARER_TOKEN=test-token-123');

        // Reset all mock globals
        unset($GLOBALS['_mock_get_user_by_callback']);
        unset($GLOBALS['_mock_download_url_callback']);
        unset($GLOBALS['_mock_wp_check_filetype_callback']);
        unset($GLOBALS['_mock_bp_core_avatar_upload_path']);
        unset($GLOBALS['_mock_bp_core_avatar_url']);
    }

    protected function tearDown(): void
    {
        if ($this->originalEnv !== false) {
            putenv('PROFILES_API_BEARER_TOKEN=' . $this->originalEnv);
        } else {
            putenv('PROFILES_API_BEARER_TOKEN');
        }

        unset($GLOBALS['_mock_get_user_by_callback']);
        unset($GLOBALS['_mock_download_url_callback']);
        unset($GLOBALS['_mock_wp_check_filetype_callback']);
        unset($GLOBALS['_mock_bp_core_avatar_upload_path']);
        unset($GLOBALS['_mock_bp_core_avatar_url']);

        parent::tearDown();
    }

    // -------------------------------------------------------
    // Input validation tests
    // -------------------------------------------------------

    public function testMissingUsernameReturns400(): void
    {
        $request = new \WP_REST_Request('POST', '/idms/update-avatar');
        $request->set_param('image_url', 'https://cdn.hcommons.org/media/profile_images/abc123.jpg');

        $response = Plugin::update_avatar($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertEquals(400, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('username', strtolower($data['error']));
    }

    public function testMissingImageUrlReturns400(): void
    {
        $request = new \WP_REST_Request('POST', '/idms/update-avatar');
        $request->set_param('username', 'jsmith');

        $response = Plugin::update_avatar($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertEquals(400, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('image_url', strtolower($data['error']));
    }

    public function testEmptyUsernameReturns400(): void
    {
        $request = new \WP_REST_Request('POST', '/idms/update-avatar');
        $request->set_param('username', '');
        $request->set_param('image_url', 'https://cdn.hcommons.org/media/profile_images/abc123.jpg');

        $response = Plugin::update_avatar($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertEquals(400, $response->get_status());
    }

    public function testEmptyImageUrlReturns400(): void
    {
        $request = new \WP_REST_Request('POST', '/idms/update-avatar');
        $request->set_param('username', 'jsmith');
        $request->set_param('image_url', '');

        $response = Plugin::update_avatar($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertEquals(400, $response->get_status());
    }

    // -------------------------------------------------------
    // Domain whitelist tests
    // -------------------------------------------------------

    public function testDisallowedDomainReturns400(): void
    {
        $request = new \WP_REST_Request('POST', '/idms/update-avatar');
        $request->set_param('username', 'jsmith');
        $request->set_param('image_url', 'https://evil.example.com/malware.jpg');

        $response = Plugin::update_avatar($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertEquals(400, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('domain', strtolower($data['error']));
    }

    public function testAllowedDomainIsAccepted(): void
    {
        $this->assertTrue(
            Plugin::is_allowed_image_domain('https://cdn.hcommons.org/media/profile_images/abc.jpg')
        );
    }

    public function testS3DomainIsAccepted(): void
    {
        $this->assertTrue(
            Plugin::is_allowed_image_domain('https://knowledge-commons-profiles.s3.amazonaws.com/media/profile_images/abc.jpg')
        );
    }

    public function testSubdomainOfAllowedDomainIsAccepted(): void
    {
        $this->assertTrue(
            Plugin::is_allowed_image_domain('https://sub.cdn.hcommons.org/media/profile_images/abc.jpg')
        );
    }

    public function testArbitraryDomainIsRejected(): void
    {
        $this->assertFalse(
            Plugin::is_allowed_image_domain('https://attacker.com/evil.jpg')
        );
    }

    public function testInvalidUrlIsRejected(): void
    {
        $this->assertFalse(
            Plugin::is_allowed_image_domain('not-a-url')
        );
    }

    // -------------------------------------------------------
    // User lookup tests
    // -------------------------------------------------------

    public function testNonExistentUserReturns404(): void
    {
        $GLOBALS['_mock_get_user_by_callback'] = function ($field, $value) {
            return false;
        };

        $request = new \WP_REST_Request('POST', '/idms/update-avatar');
        $request->set_param('username', 'nonexistent');
        $request->set_param('image_url', 'https://cdn.hcommons.org/media/profile_images/abc123.jpg');

        $response = Plugin::update_avatar($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertEquals(404, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('not found', strtolower($data['error']));
    }

    // -------------------------------------------------------
    // Image download tests
    // -------------------------------------------------------

    public function testDownloadFailureReturns422(): void
    {
        $user = new \WP_User(42);
        $user->user_login = 'jsmith';

        $GLOBALS['_mock_get_user_by_callback'] = function ($field, $value) use ($user) {
            return ($value === 'jsmith') ? $user : false;
        };

        $GLOBALS['_mock_download_url_callback'] = function ($url, $timeout) {
            return new \WP_Error('http_request_failed', 'Connection timed out');
        };

        $request = new \WP_REST_Request('POST', '/idms/update-avatar');
        $request->set_param('username', 'jsmith');
        $request->set_param('image_url', 'https://cdn.hcommons.org/media/profile_images/abc123.jpg');

        $response = Plugin::update_avatar($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertEquals(422, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('download', strtolower($data['error']));
    }

    public function testInvalidImageTypeReturns422(): void
    {
        $user = new \WP_User(42);
        $user->user_login = 'jsmith';

        $GLOBALS['_mock_get_user_by_callback'] = function ($field, $value) use ($user) {
            return ($value === 'jsmith') ? $user : false;
        };

        // download_url returns a temp file path
        $tmp = tempnam(sys_get_temp_dir(), 'avatar_test_');
        file_put_contents($tmp, 'not a jpeg');

        $GLOBALS['_mock_download_url_callback'] = function ($url, $timeout) use ($tmp) {
            return $tmp;
        };

        // wp_check_filetype says it's a PNG, not JPEG
        $GLOBALS['_mock_wp_check_filetype_callback'] = function ($filename, $mimes) {
            return ['ext' => 'png', 'type' => 'image/png'];
        };

        $request = new \WP_REST_Request('POST', '/idms/update-avatar');
        $request->set_param('username', 'jsmith');
        $request->set_param('image_url', 'https://cdn.hcommons.org/media/profile_images/abc123.jpg');

        $response = Plugin::update_avatar($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertEquals(422, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('jpeg', strtolower($data['error']));

        // Clean up temp file if still exists
        @unlink($tmp);
    }

    // -------------------------------------------------------
    // Successful avatar update tests
    // -------------------------------------------------------

    public function testSuccessfulAvatarUpdateReturns200(): void
    {
        $user = new \WP_User(42);
        $user->user_login = 'jsmith';

        $GLOBALS['_mock_get_user_by_callback'] = function ($field, $value) use ($user) {
            return ($value === 'jsmith') ? $user : false;
        };

        // Create a temp file simulating a downloaded JPEG
        $tmp = tempnam(sys_get_temp_dir(), 'avatar_test_');
        // Write JPEG magic bytes
        file_put_contents($tmp, "\xFF\xD8\xFF\xE0" . str_repeat("\x00", 100));

        $GLOBALS['_mock_download_url_callback'] = function ($url, $timeout) use ($tmp) {
            return $tmp;
        };

        $GLOBALS['_mock_wp_check_filetype_callback'] = function ($filename, $mimes) {
            return ['ext' => 'jpg', 'type' => 'image/jpeg'];
        };

        // Set up avatar paths to use temp dir
        $avatarDir = sys_get_temp_dir() . '/bp-avatars-test-' . uniqid();
        $GLOBALS['_mock_bp_core_avatar_upload_path'] = $avatarDir;
        $GLOBALS['_mock_bp_core_avatar_url'] = 'https://example.com/wp-content/uploads';

        $request = new \WP_REST_Request('POST', '/idms/update-avatar');
        $request->set_param('username', 'jsmith');
        $request->set_param('image_url', 'https://cdn.hcommons.org/media/profile_images/abc123.jpg');

        $response = Plugin::update_avatar($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());

        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertStringContainsString('jsmith', $data['message']);
        $this->assertArrayHasKey('avatar_url', $data);
        $this->assertStringContainsString('42', $data['avatar_url']);

        // Verify avatar files were created
        $this->assertFileExists($avatarDir . '/avatars/42/bpfull.jpg');
        $this->assertFileExists($avatarDir . '/avatars/42/bpthumb.jpg');

        // Clean up
        @unlink($avatarDir . '/avatars/42/bpfull.jpg');
        @unlink($avatarDir . '/avatars/42/bpthumb.jpg');
        @rmdir($avatarDir . '/avatars/42');
        @rmdir($avatarDir . '/avatars');
        @rmdir($avatarDir);
        @unlink($tmp);
    }

    public function testSuccessfulUpdateDeletesExistingAvatars(): void
    {
        $user = new \WP_User(42);
        $user->user_login = 'jsmith';

        $GLOBALS['_mock_get_user_by_callback'] = function ($field, $value) use ($user) {
            return ($value === 'jsmith') ? $user : false;
        };

        // Set up avatar directory with pre-existing files
        $avatarDir = sys_get_temp_dir() . '/bp-avatars-test-' . uniqid();
        $userAvatarDir = $avatarDir . '/avatars/42';
        mkdir($userAvatarDir, 0755, true);
        file_put_contents($userAvatarDir . '/old-bpfull.jpg', 'old avatar data');
        file_put_contents($userAvatarDir . '/old-bpthumb.jpg', 'old thumb data');

        $tmp = tempnam(sys_get_temp_dir(), 'avatar_test_');
        file_put_contents($tmp, "\xFF\xD8\xFF\xE0" . str_repeat("\x00", 100));

        $GLOBALS['_mock_download_url_callback'] = function ($url, $timeout) use ($tmp) {
            return $tmp;
        };

        $GLOBALS['_mock_wp_check_filetype_callback'] = function ($filename, $mimes) {
            return ['ext' => 'jpg', 'type' => 'image/jpeg'];
        };

        $GLOBALS['_mock_bp_core_avatar_upload_path'] = $avatarDir;
        $GLOBALS['_mock_bp_core_avatar_url'] = 'https://example.com/wp-content/uploads';

        $request = new \WP_REST_Request('POST', '/idms/update-avatar');
        $request->set_param('username', 'jsmith');
        $request->set_param('image_url', 'https://cdn.hcommons.org/media/profile_images/abc123.jpg');

        $response = Plugin::update_avatar($request);

        $this->assertEquals(200, $response->get_status());

        // Old files should be gone
        $this->assertFileDoesNotExist($userAvatarDir . '/old-bpfull.jpg');
        $this->assertFileDoesNotExist($userAvatarDir . '/old-bpthumb.jpg');

        // New files should exist
        $this->assertFileExists($userAvatarDir . '/bpfull.jpg');
        $this->assertFileExists($userAvatarDir . '/bpthumb.jpg');

        // Clean up
        @unlink($userAvatarDir . '/bpfull.jpg');
        @unlink($userAvatarDir . '/bpthumb.jpg');
        @rmdir($userAvatarDir);
        @rmdir($avatarDir . '/avatars');
        @rmdir($avatarDir);
        @unlink($tmp);
    }

    public function testAvatarUrlContainsUserId(): void
    {
        $user = new \WP_User(99);
        $user->user_login = 'testuser';

        $GLOBALS['_mock_get_user_by_callback'] = function ($field, $value) use ($user) {
            return ($value === 'testuser') ? $user : false;
        };

        $tmp = tempnam(sys_get_temp_dir(), 'avatar_test_');
        file_put_contents($tmp, "\xFF\xD8\xFF\xE0" . str_repeat("\x00", 100));

        $GLOBALS['_mock_download_url_callback'] = function ($url, $timeout) use ($tmp) {
            return $tmp;
        };

        $GLOBALS['_mock_wp_check_filetype_callback'] = function ($filename, $mimes) {
            return ['ext' => 'jpg', 'type' => 'image/jpeg'];
        };

        $avatarDir = sys_get_temp_dir() . '/bp-avatars-test-' . uniqid();
        $GLOBALS['_mock_bp_core_avatar_upload_path'] = $avatarDir;
        $GLOBALS['_mock_bp_core_avatar_url'] = 'https://example.com/wp-content/uploads';

        $request = new \WP_REST_Request('POST', '/idms/update-avatar');
        $request->set_param('username', 'testuser');
        $request->set_param('image_url', 'https://cdn.hcommons.org/media/profile_images/abc123.jpg');

        $response = Plugin::update_avatar($request);

        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertStringContainsString('/avatars/99/', $data['avatar_url']);
        $this->assertStringEndsWith('bpfull.jpg', $data['avatar_url']);

        // Clean up
        @unlink($avatarDir . '/avatars/99/bpfull.jpg');
        @unlink($avatarDir . '/avatars/99/bpthumb.jpg');
        @rmdir($avatarDir . '/avatars/99');
        @rmdir($avatarDir . '/avatars');
        @rmdir($avatarDir);
        @unlink($tmp);
    }
}
