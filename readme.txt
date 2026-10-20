=== CodeIXE WordPress SEO Connector ===
Contributors: delson93
Tags: seo, rest-api, artificial-intelligence, audit
Requires at least: 6.9
Tested up to: 6.9
Requires PHP: 8.2
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Authenticated SEO audits and administrator-approved changes for compatible AI clients. No Jetpack required.

== Description ==
By CodeIXE AI Solutions, https://codeixe.com/.
Version 0.2.0 adds private Custom GPT Actions with scoped Bearer credentials, administrator-authorized remote application, and Yoast title/description output overrides. Includes bounded audits, history and rollback.
No tracking or mandatory service. Existing SEO providers are detected and respected. Yoast uses documented output filters; protected third-party fields are not written.
Use a private Custom GPT Actions configuration for the key-based connection. Remote MCP still requires additional OAuth infrastructure; installation alone does not connect a conversation. No ranking guarantees.

== Installation ==
See docs/chatgpt-actions.md for the private Custom GPT connection and remote authorization setup.
1. Upload the generated plugin ZIP through Plugins > Add New Plugin.
2. Activate, open CodeIXE SEO, and enable services over HTTPS.
3. Start in read-only mode. Configure a dedicated editor and Application Password securely.
4. Review docs/connections.md before connecting an AI client.

== Frequently Asked Questions ==
= Does it fix every SEO issue automatically? =
No. It audits supported checks and stores exact proposals. Remote application requires an explicitly configured administrator field policy. Other changes require manual review. Advanced crawls and third-party metadata editing are not included.
= Does it require Jetpack or a subscription? =
No. An external AI client may have its own charges.
= Does uninstall undo content edits? =
No. Rollback desired edits first. Optional uninstall cleanup removes connector data but preserves public content and image ALT.

== Changelog ==
= 0.2.0 =
Custom GPT Actions, scoped remote writes, and Yoast output integration.

= 0.1.0 =
Initial MVP. See CHANGELOG.md and docs/releases.md for scope and validation status.
