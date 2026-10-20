# CodeIXE WordPress SEO Connector

Free, self-hosted WordPress SEO auditing and approval-based editing for compatible AI clients.

**Developer:** [CodeIXE AI Solutions](https://codeixe.com/)  
**License:** GPL-2.0-or-later  
**Version:** 0.1.0, initial MVP  
**Requirements:** WordPress 6.9+, PHP 8.2+, HTTPS. Use maintained WordPress and PHP releases.

No Jetpack, WordPress.com account, tracking, CodeIXE subscription or proprietary cloud service is required. Your AI client may have its own charges.

## What this version actually does

- Authenticated, permission-checked REST API and optional WordPress Abilities/MCP tools.
- Ten-object audit batches for published posts, pages and image attachments.
- Stored-content checks for title/description length, heading jumps, multiple content H1s, missing ALT attributes, short-content candidates, indexing preferences and plain permalinks.
- Optional same-site public HTML inspection for description, canonical, H1, robots and social metadata, with bounded safe HTTP requests.
- Exact proposals for post titles, content, excerpts, image attachment ALT and connector-owned SEO metadata.
- Human administrator review of before/after values, apply, history, and conflict-aware rollback.
- Optional singular-page metadata fallback. It yields to Yoast SEO, Rank Math, All in One SEO and SEOPress. No third-party protected metadata or databases are modified.
- Dashboard, editable-object reader, image list, diagnostics, JSON and CSV audit exports.
- Installable ZIP build, CI, and WordPress integration tests.

**This is not an autonomous “fix every SEO problem” product.** It does not guarantee rankings, revenue, indexing or traffic. It does not invent ALT descriptions or publish AI text without review. Length/word-count findings are guidance, not search-engine penalties.

## Install

1. Download the `codeixe-wp-seo-connector.zip` artifact from a successful GitHub Actions build, or build it using `python3 scripts/build.py`.
2. WordPress → Plugins → Add New Plugin → Upload Plugin → choose the ZIP → activate.
3. Open **CodeIXE SEO**, enable services, and keep read-only mode enabled initially.
4. Run an audit. If no SEO plugin/theme manages metadata, optionally enable the fallback after checking the rendered HTML.
5. Configure a dedicated WordPress editor account and Application Password in your client's secure credential store. Never paste passwords into a chat.
6. For proposals, disable read-only mode. Review and approve each proposal in WordPress Change History.
7. Purge the hosting/page/CDN cache, then re-audit rendered output. The plugin does not claim to purge third-party caches.

Uploading GitHub's generic source archive may create a different folder name. Prefer the generated installable ZIP. No Composer, Node, SSH or CLI is required on the website.

## Can ChatGPT connect directly?

Not from installation alone. This plugin exposes WordPress REST endpoints and optional MCP abilities. The official WordPress MCP Adapter is a separate dependency for MCP. Application Passwords work with compatible Basic-auth clients. ChatGPT remote MCP user authentication requires compatible OAuth infrastructure; this MVP does **not** bundle an OAuth authorization server or a tested ChatGPT bridge. Do not register a privileged endpoint with “no authentication”.

See [connection methods](docs/connections.md), [API](docs/api.md), [security](docs/security.md), [architecture and scope](docs/architecture.md), and [testing/release](docs/releases.md).

## Known gaps and roadmap

- No bundled OAuth server or end-to-end verified ChatGPT connection.
- No writes into Yoast/Rank Math/AIOSEO/SEOPress; integration is detection and deferral, not full provider editing.
- No taxonomy/archive/homepage-specific audits, whole-site duplicate detection, broken-link crawler, robots.txt/sitemap network checks, Search Console import or structured-data validation.
- No automatic internal-link suggestions or keyword generation. A client can read content and propose relevant links in `post_content`.
- No unattended scheduled scans, bulk approval, automatic retention pruning or network-wide multisite support. Audits are bounded request batches and are not persisted; proposal history is retained until explicitly removed/uninstalled.
- Theme-generated tags, JS-generated metadata, templates, redirects and server/CDN problems require separate investigation.
- Attachment ALT changes affect the media library; existing inline image markup may require a separate reviewed content change.
- UI is a single native administration page with sections, not nine independent admin screens. Translation coverage is incomplete in this MVP.
- Apply is not a database-wide transaction. A hook/database failure can leave a partial update; it is recorded as failed for manual recovery.

The initial version needs staging acceptance on your hosting stack before production use. See the validation record in `docs/releases.md` for checks actually executed.
