<?php
use PHPUnit\Framework\TestCase;
use CodeIXE\SEO\Plugin;
use CodeIXE\SEO\Changes;
use CodeIXE\SEO\Audit;
use CodeIXE\SEO\Metadata;
use CodeIXE\SEO\Actions;

final class ConnectorTest extends TestCase {
    private int $postId;
    private int $adminId;

    protected function setUp(): void {
        $_SERVER['HTTPS'] = 'on';
        $user = get_user_by('login', 'connector-test-admin');
        $this->adminId = $user ? $user->ID : wp_insert_user(['user_login' => 'connector-test-admin', 'user_pass' => wp_generate_password(), 'role' => 'administrator']);
        wp_set_current_user($this->adminId);
        update_option('codeixe_seo_settings', ['enabled' => true, 'readonly' => false, 'fallback' => true]);
        foreach (['propose', 'audit', 'batch', 'fetch', 'apply'] as $action) { delete_transient('codeixe_rate_' . $this->adminId . '_' . $action); }
        $this->postId = wp_insert_post(['post_title' => 'Original title', 'post_content' => '<p>Original content.</p>', 'post_type' => 'post', 'post_status' => 'publish', 'post_author' => $this->adminId]);
    }
    protected function tearDown(): void {
        Actions::restore();
        delete_option('codeixe_seo_action_key');
        wp_set_current_user($this->adminId);
        wp_delete_post($this->postId, true);
        foreach (get_posts(['post_type' => 'codeixe_change', 'post_status' => 'private', 'numberposts' => -1]) as $record) { wp_delete_post($record->ID, true); }
        delete_option('codeixe_lock_' . $this->postId);
        remove_all_filters('codeixe_seo_external_provider');
    }
    public function testAnonymousAccessIsDenied(): void {
        wp_set_current_user(0);
        self::assertWPError(Plugin::permission());
        self::assertWPError(Changes::propose($this->postId, ['post_title' => 'Denied']));
    }
    public function testReadOnlyAndDisabledModes(): void {
        update_option('codeixe_seo_settings', ['enabled' => true, 'readonly' => true]);
        self::assertWPError(Changes::propose($this->postId, ['post_title' => 'Denied']));
        update_option('codeixe_seo_settings', ['enabled' => false]);
        self::assertWPError(Plugin::permission());
    }
    public function testProposalDoesNotWriteAndApplyRollbackWorks(): void {
        $proposal = Changes::propose($this->postId, ['post_title' => 'Useful title', 'description' => 'A useful description.']);
        self::assertIsArray($proposal);
        self::assertSame('Original title', get_post($this->postId)->post_title);
        self::assertSame('pending', $proposal['state']);
        $result = Changes::apply($proposal['id']);
        self::assertSame('applied', $result['state']);
        self::assertSame('Useful title', get_post($this->postId)->post_title);
        self::assertWPError(Changes::apply($proposal['id']));
        $result = Changes::apply($proposal['id'], true);
        self::assertSame('rolled_back', $result['state']);
        self::assertSame('Original title', get_post($this->postId)->post_title);
        self::assertSame('', get_post_meta($this->postId, '_codeixe_description', true));
    }
    public function testStaleProposalAndRollbackAreRejected(): void {
        $proposal = Changes::propose($this->postId, ['post_title' => 'Proposed']);
        wp_update_post(['ID' => $this->postId, 'post_title' => 'Later edit']);
        self::assertWPError(Changes::apply($proposal['id']));
        self::assertSame('Later edit', get_post($this->postId)->post_title);
        $proposal = Changes::propose($this->postId, ['post_title' => 'Approved']);
        self::assertSame('applied', Changes::apply($proposal['id'])['state']);
        wp_update_post(['ID' => $this->postId, 'post_title' => 'Another later edit']);
        self::assertWPError(Changes::apply($proposal['id'], true));
    }
    public function testUnknownFieldsAndUnsafeCanonicalRejected(): void {
        self::assertWPError(Changes::propose($this->postId, ['post_status' => 'draft']));
        self::assertWPError(Changes::propose($this->postId, ['canonical' => 'https://other.example/page']));
        self::assertWPError(Changes::propose($this->postId, ['noindex' => 'false']));
        self::assertWPError(Changes::propose($this->postId, ['alt' => 'Not an attachment']));
    }
    public function testProposalSanitizesExecutableHtml(): void {
        $proposal = Changes::propose($this->postId, ['post_content' => '<p onclick="alert(1)">Text</p><script>alert(1)</script>']);
        self::assertIsArray($proposal);
        self::assertStringNotContainsString('<script', $proposal['after']['post_content']);
        self::assertStringNotContainsString('onclick', $proposal['after']['post_content']);
    }
    public function testExternalProviderPreventsMetadataWrites(): void {
        add_filter('codeixe_seo_external_provider', '__return_true');
        self::assertFalse(Metadata::enabled());
        self::assertWPError(Changes::propose($this->postId, ['description' => 'Do not shadow external provider']));
    }
    public function testEditorCannotApproveOwnProposal(): void {
        $user = get_user_by('login', 'connector-test-editor');
        $id = $user ? $user->ID : wp_insert_user(['user_login' => 'connector-test-editor', 'user_pass' => wp_generate_password(), 'role' => 'editor']);
        wp_set_current_user($id);
        $proposal = Changes::propose($this->postId, ['post_title' => 'Editor proposal']);
        self::assertIsArray($proposal);
        self::assertWPError(Changes::apply($proposal['id']));
    }
    public function testSubscriberCannotReadPrivateTarget(): void {
        $user = get_user_by('login', 'connector-test-reader');
        $id = $user ? $user->ID : wp_insert_user(['user_login' => 'connector-test-reader', 'user_pass' => wp_generate_password(), 'role' => 'subscriber']);
        wp_set_current_user($id);
        self::assertWPError(Plugin::target($this->postId));
    }
    public function testAuditRespectsDecorativeAlt(): void {
        $audit = Audit::analyze('<h2>A</h2><h4>B</h4><img src="meaningful.png"><img src="decorative.png" alt="">', 'Title', '');
        $codes = array_column($audit['issues'], 'code');
        self::assertContains('missing_alt', $codes);
        self::assertContains('heading_skip', $codes);
        foreach ($audit['issues'] as $issue) {
            if ($issue['code'] === 'missing_alt') { self::assertSame(['meaningful.png'], $issue['evidence']); }
        }
    }
    public function testFetchRejectsCrossHostAndPrivatePost(): void {
        self::assertWPError(Audit::fetch('https://example.invalid/'));
        self::assertWPError(Audit::fetch('http://127.0.0.1/'));
        wp_update_post(['ID' => $this->postId, 'post_status' => 'private']);
        self::assertWPError(Audit::post($this->postId, true));
    }
    public function testObjectLockAndThrottle(): void {
        $proposal = Changes::propose($this->postId, ['post_title' => 'Locked']);
        add_option('codeixe_lock_' . $this->postId, time(), '', false);
        self::assertWPError(Changes::apply($proposal['id']));
        set_transient('codeixe_rate_' . $this->adminId . '_propose', 20, 60);
        self::assertWPError(Changes::propose($this->postId, ['post_title' => 'Limited']));
    }
    public function testRestRoutesRequireAuthentication(): void {
        wp_set_current_user(0);
        $server = rest_get_server();
        $response = $server->dispatch(new WP_REST_Request('GET', '/codeixe-seo/v1/status'));
        self::assertSame(403, $response->get_status());
        self::assertArrayNotHasKey('/codeixe-seo/v1/approve', $server->get_routes());
    }
    public function testAbilitiesUsePermissionChecks(): void {
        $ability = wp_get_ability('codeixe-seo/get-site-info');
        self::assertNotNull($ability);
        wp_set_current_user(0);
        self::assertWPError($ability->execute([]));
    }
    private function actionKey(): string {
        $user = get_user_by('login', 'connector-action-editor');
        $id = $user ? $user->ID : wp_insert_user(['user_login' => 'connector-action-editor', 'user_pass' => wp_generate_password(), 'role' => 'editor']);
        delete_transient('codeixe_rate_' . $id . '_propose');
        return Actions::create_key($id);
    }
    private function authenticateAction(string $key): void {
        wp_set_current_user(0);
        $request = new WP_REST_Request('GET', '/codeixe-seo/v1/status');
        $request->set_header('Authorization', 'Bearer ' . $key);
        self::assertSame(true, Actions::permission($request));
    }
    public function testActionKeyScopedAndRevocable(): void {
        $key = $this->actionKey();
        self::assertSame(68, strlen($key));
        self::assertSame(hash('sha256', $key), get_option('codeixe_seo_action_key')['hash']);
        $this->authenticateAction($key);
        Actions::restore();
        self::assertSame(0, get_current_user_id());
        $request = new WP_REST_Request('GET', '/wp/v2/users');
        $request->set_header('Authorization', 'Bearer ' . $key);
        self::assertWPError(Actions::permission($request));
        delete_option('codeixe_seo_action_key');
        $request = new WP_REST_Request('GET', '/codeixe-seo/v1/status');
        $request->set_header('Authorization', 'Bearer ' . $key);
        self::assertWPError(Actions::permission($request));
    }
    public function testRemoteWritesRequirePolicyScopeAndDigest(): void {
        $key = $this->actionKey();
        $this->authenticateAction($key);
        $proposal = Changes::propose($this->postId, ['description' => 'Action description']);
        self::assertIsArray($proposal);
        self::assertWPError(Changes::apply($proposal['id'], false, $proposal['digest']));
        $settings = Plugin::settings();
        $settings['remote_apply'] = true;
        $settings['remote_fields'] = ['seo_title'];
        update_option('codeixe_seo_settings', $settings);
        self::assertWPError(Changes::apply($proposal['id'], false, $proposal['digest']));
        $settings['remote_fields'] = ['description'];
        update_option('codeixe_seo_settings', $settings);
        self::assertWPError(Changes::apply($proposal['id'], false, str_repeat('0',64)));
        self::assertSame('applied', Changes::apply($proposal['id'], false, $proposal['digest'])['state']);
        self::assertSame('Action description', Metadata::get($this->postId)['description']);
        self::assertSame('rolled_back', Changes::apply($proposal['id'], true, $proposal['digest'])['state']);
    }
    public function testActionCannotApplyAnotherUsersProposal(): void {
        $proposal = Changes::propose($this->postId, ['description' => 'Admin proposal']);
        $key = $this->actionKey();
        $settings = Plugin::settings();
        $settings['remote_apply'] = true;
        $settings['remote_fields'] = ['description'];
        update_option('codeixe_seo_settings', $settings);
        $this->authenticateAction($key);
        self::assertWPError(Changes::apply($proposal['id'], false, $proposal['digest']));
    }
    public function testActionsCannotCreateAdministratorCredential(): void {
        self::assertWPError(Actions::create_key($this->adminId));
    }
    public function testBearerRestIdentityDoesNotLeak(): void {
        $key = $this->actionKey();
        wp_set_current_user(0);
        $request = new WP_REST_Request('GET', '/codeixe-seo/v1/status');
        $request->set_header('Authorization', 'Bearer ' . $key);
        $response = rest_get_server()->dispatch($request);
        self::assertSame(200, $response->get_status());
        self::assertSame(0, get_current_user_id());
        $request = new WP_REST_Request('GET', '/wp/v2/users/me');
        $request->set_header('Authorization', 'Bearer ' . $key);
        self::assertSame(401, rest_get_server()->dispatch($request)->get_status());
    }
    public function testOpenApiContainsConsequentialWriteOperations(): void {
        $schema = Actions::schema();
        self::assertSame(true, $schema['paths']['/proposals/{id}/apply']['post']['x-openai-isConsequential']);
        self::assertSame('bearer', $schema['components']['securitySchemes']['connectorKey']['scheme']);
        self::assertSame('0.2.0', $schema['info']['version']);
    }
    private static function assertWPError($result): void { self::assertInstanceOf(WP_Error::class, $result); }
}
