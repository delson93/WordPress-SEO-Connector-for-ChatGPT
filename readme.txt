=== CodeIXE WordPress SEO Connector ===
Contributors: delson93
Tags: seo, rest-api, artificial-intelligence, audit
Requires at least: 6.9
Tested up to: 6.9
Requires PHP: 8.2
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Authenticated SEO audits and administrator-approved changes for compatible AI clients. No Jetpack required.

== Description ==
By CodeIXE AI Solutions, https://codeixe.com/.
Initial MVP with bounded post/page audits, native admin, authenticated REST endpoints, optional WordPress MCP Adapter abilities, metadata fallback, change snapshots and rollback.
No tracking or mandatory service. Existing SEO providers are detected and respected. Metadata writes into third-party SEO plugins are not implemented.
ChatGPT connection requires compatible client authentication and additional OAuth infrastructure for remote MCP; installation alone does not connect a conversation. No ranking guarantees.

== Installation ==
1. Upload the generated plugin ZIP through Plugins > Add New Plugin.
2. Activate, open CodeIXE SEO, and enable services over HTTPS.
3. Start in read-only mode. Configure a dedicated editor and Application Password securely.
4. Review docs/connections.md before connecting an AI client.

== Frequently Asked Questions ==
= Does it fix every SEO issue automatically? =
No. It audits supported checks and stores exact proposals. An administrator must review and approve changes. Advanced crawls and third-party metadata editing are not included.
= Does it require Jetpack or a subscription? =
No. An external AI client may have its own charges.
= Does uninstall undo content edits? =
No. Rollback desired edits first. Optional uninstall cleanup removes connector data but preserves public content and image ALT.

== Changelog ==
= 0.1.0 =
Initial MVP. See CHANGELOG.md and docs/releases.md for scope and validation status.
