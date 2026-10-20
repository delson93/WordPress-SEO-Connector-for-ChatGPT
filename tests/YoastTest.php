<?php
use PHPUnit\Framework\TestCase;
use CodeIXE\SEO\Metadata;
use CodeIXE\SEO\Changes;

final class YoastTest extends TestCase {
    private int $postId;
    protected function setUp(): void {
        $_SERVER['HTTPS'] = 'on';
        add_theme_support('title-tag');
        $user = get_user_by('login', 'connector-test-admin');
        $id = $user ? $user->ID : wp_insert_user(['user_login' => 'connector-test-admin', 'user_pass' => wp_generate_password(), 'role' => 'administrator']);
        wp_set_current_user($id);
        delete_transient('codeixe_rate_' . $id . '_propose');
        update_option('codeixe_seo_settings', ['enabled' => true, 'readonly' => false, 'fallback' => false, 'yoast_overrides' => true]);
        $this->postId = wp_insert_post(['post_title' => 'Original Yoast title', 'post_content' => '<p>Article text.</p>', 'post_type' => 'post', 'post_status' => 'publish', 'post_author' => $id]);
    }
    protected function tearDown(): void {
        wp_delete_post($this->postId, true);
        foreach (get_posts(['post_type' => 'codeixe_change', 'post_status' => 'private', 'numberposts' => -1]) as $record) { wp_delete_post($record->ID, true); }
    }
    public function testYoastOutputUsesApprovedOverridesWithoutProtectedWrites(): void {
        self::assertSame(true, defined('WPSEO_VERSION'));
        self::assertSame(true, Metadata::yoast_enabled());
        self::assertFalse(Metadata::enabled());
        $before = get_post_meta($this->postId, '_yoast_wpseo_metadesc', true);
        $proposal = Changes::propose($this->postId, ['seo_title' => 'Reviewed SEO title', 'description' => 'Reviewed accurate description of the article.']);
        self::assertIsArray($proposal);
        self::assertSame('applied', Changes::apply($proposal['id'])['state']);
        $surface = YoastSEO()->meta->for_post($this->postId);
        $html = $surface->get_head()->html;
        self::assertSame(true, str_contains($html, '<title>Reviewed SEO title</title>'));
        self::assertSame(true, str_contains($html, 'name="description" content="Reviewed accurate description of the article."'));
        self::assertSame(1, substr_count($html, 'name="description"'));
        self::assertSame($before, get_post_meta($this->postId, '_yoast_wpseo_metadesc', true));
        self::assertSame('Reviewed SEO title', Metadata::effective($this->postId)['seo_title']);
        self::assertSame('rolled_back', Changes::apply($proposal['id'], true)['state']);
        self::assertSame('', Metadata::get($this->postId)['seo_title']);
    }
    public function testYoastDefersUnsupportedRobotsAndCanonicalWrites(): void {
        self::assertInstanceOf(WP_Error::class, Changes::propose($this->postId, ['noindex' => '1']));
        self::assertInstanceOf(WP_Error::class, Changes::propose($this->postId, ['canonical' => home_url('/example/')]));
    }
    public function testDisablingOverridesRestoresYoastOwnership(): void {
        update_post_meta($this->postId, '_codeixe_description', 'Connector value');
        update_option('codeixe_seo_settings', ['enabled' => true, 'readonly' => false, 'yoast_overrides' => false]);
        $presentation = (object) ['model' => (object) ['object_type' => 'post', 'object_id' => $this->postId]];
        self::assertSame('Yoast value', Metadata::yoast_value('Yoast value', 'description', $presentation));
        self::assertInstanceOf(WP_Error::class, Changes::propose($this->postId, ['description' => 'Not enabled']));
    }
}
