# CodeIXE WordPress SEO Connector

Connect a private Custom GPT to a self-hosted WordPress site to audit content and apply authorized SEO fixes.

**Version:** 0.2.0. **Developer:** [CodeIXE AI Solutions](https://codeixe.com/). **License:** GPL-2.0-or-later.

WordPress 6.9+, PHP 8.2+, HTTPS. No Jetpack, WordPress.com account, CodeIXE subscription or proprietary cloud is required. Your AI client has its own availability and pricing.

## Download and connect

[Download the installable 0.2.0 ZIP](packages/codeixe-wp-seo-connector-0.2.0.zip?raw=true).

1. Upload through WordPress Plugins > Add New > Upload. Replace the previous version if installed.
2. Open CodeIXE SEO, enable services, and create a dedicated WordPress Editor user.
3. For Yoast, enable the Yoast override setting and leave fallback metadata off.
4. Generate an Actions key for that Editor in the plugin. Store it only in your private GPT's authentication settings.
5. In a Custom GPT's Actions editor, import `https://YOUR-SITE/wp-json/codeixe-seo/v1/openapi`. Choose API Key > Bearer, enter the key, and keep the GPT **Only me**.
6. To authorize remote application, turn read-only off, enable remote application and select exactly which fields may change. Start with SEO title/description, ALT and excerpt. Article rewriting requires separate `post_content` authorization.
7. Follow [the complete setup and GPT instructions](docs/chatgpt-actions.md). Test the connection and a staging edit before applying changes to production.

ChatGPT can then propose and apply supported fixes in the configured GPT, subject to the website's field policy and ChatGPT's confirmations. Installation does not attach tools to an arbitrary existing conversation. No end-to-end connection to your ChatGPT account is claimed until you complete configuration and test it.

## Supported features

- Authenticated REST API, private Custom GPT Actions and optional WordPress MCP abilities.
- Per-object permissions, dedicated route-scoped hashed Actions key, revocation and disabled-by-default remote writes.
- Ten-object post/page/image audit batches, stored-content checks and optional safe public HTML inspection.
- Title/description length guidance, heading jumps, multiple H1s, missing ALT, short-content review, canonical/social/robots inspection and site indexing/permalink flags.
- Exact proposed replacements for SEO title, description, image ALT, excerpt, post title and content. Heading/internal-link/inline-ALT fixes use reviewed content changes.
- Administrator-controlled field authorization, exact proposal digest, before/after history, stale-edit refusal and rollback.
- Yoast title/description and social output integration through documented filters. No protected Yoast database writes; no duplicate tag generator.
- Opt-in singular-page metadata fallback when no SEO provider is active.
- JSON/CSV exports, diagnostics, build automation and tests.

## Limits

This is still an early version, not a guarantee to fix every SEO issue or improve rankings/revenue. No automatic keyword/image-description generation occurs inside WordPress; the configured GPT proposes accurate edits from the content it reads. No OpenAI API key is required by this plugin.

Yoast integration changes rendered overrides, not Yoast editor fields, scores, global homepage settings or schema. Rank Math/AIOSEO/SEOPress are detected and deferred; their metadata writes are unsupported. There is no full-site duplicate/broken-link crawler, taxonomy/archive audit, Search Console import, schema validator, unattended scheduler or complete translation coverage. PHP/WPCS production-hardening work remains.

Custom GPT Actions and remote MCP are different integrations. MCP uses the optional official adapter. OAuth for ChatGPT remote MCP is not bundled. The Actions key works only with the REST namespace, not MCP or other WordPress APIs.

Use a private GPT. Sharing an API-key GPT shares its website access. Do not paste keys into chats. Purge external page/CDN caches after edits and verify rendered output. Failed/interrupted updates may need manual recovery; the write sequence is verified but not a database-wide transaction.

See [API](docs/api.md), [connections](docs/connections.md), [security](docs/security.md), [architecture](docs/architecture.md), [release validation](docs/releases.md), and [changelog](CHANGELOG.md).
