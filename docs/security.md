# Security and operations

Services default OFF, read-only defaults ON, fallback output defaults OFF. HTTPS is required; configure trusted proxy HTTPS detection in WordPress rather than accepting arbitrary forwarded headers. Native REST cookie authentication requires the normal REST nonce. Application Passwords are handled by WordPress and never stored by this plugin.

Every privileged REST route and ability checks authentication/capability. The public OpenAPI schema contains no private data. Every object read/proposal checks `edit_post`. Human approval requires `manage_options`, a browser login session, HTTPS and a valid admin nonce. Remote apply is available only through the scoped Actions policy described below; MCP has no apply tool. Disable services to block this connector, or revoke the specific Application Password in the WordPress user profile. Other WordPress APIs are not disabled by this setting.

Proposals retain content snapshots as private non-public custom posts. These may contain unpublished content and should be protected in database backups. JSON/CSV exports may also contain sensitive page information. No credentials or authorization header contents are returned. Diagnostics report presence only. There is no telemetry or default external HTTP request.

Optional HTML fetches use WordPress's safe HTTP API, HTTPS, same-host checks, no redirects, no cookies, a five-second timeout and 1 MiB response cap. WordPress's private-address validation remains in force; no filters are installed to bypass it. A failed fetch is evidence of a failed check, not a broken-page conclusion. Administrators must not override WordPress HTTP safety validation for these requests.

A unique option locks each target during connector writes. A process crash can leave the lock in place intentionally. After confirming no operation is running, review the change record, target content and backup, then remove the exact `codeixe_lock_OBJECT_ID` option with WP-CLI or your database administrator. Do not remove locks automatically by age. Locks coordinate connector operations, not unrelated editors/plugins; use staging and avoid concurrent editing during approval.

Apply updates are verified afterward. Hook/database failures can partially apply changes. A failed or interrupted record must be manually reconciled against its before/after/actual snapshots. Do not retry it blindly. Normal rollback only applies to a successfully applied record whose current values still match the applied snapshot. Restore original content carefully if other plugins transform it on save.

Uninstall defaults to retaining data. Opt into cleanup in Settings before deleting the plugin to remove its metadata/settings/history on a single site. Public post content and image ALT changes remain. Take backups and rollback any desired edits before uninstalling. Multisite cleanup is manual. Rate transients expire naturally. History pruning is not implemented in this MVP.

Metadata fallback adds no public branding. Known SEO plugins automatically disable it; themes/other plugins must be checked manually. Developers can return true on `codeixe_seo_external_provider` to disable fallback for another provider. Core handles title/canonical tags; the theme must support normal WordPress title and wp_head hooks.

## Hosting diagnostics

Test `/status` using your dedicated account. If authentication fails, check that Nginx, Varnish, Apache and PHP preserve Authorization and bypass authenticated REST/MCP caching. Do not log the header. Check WordPress HTTPS detection, Application Password availability, user capabilities and security-plugin/WAF exclusions. Do not globally disable the WAF. The optional public HTML audit is a limited loopback test, not a complete hosting diagnostic suite.

## Actions write policy, version 0.2.0

A nonce-protected administrator form generates a 256-bit random route-scoped key for a dedicated non-administrator editor. Only a SHA-256 hash, user ID and creation time are stored. The raw key is displayed once on a no-store response. Key rotation/revocation is immediate; services/HTTPS and current user capability are checked on every request. The key cannot log in to WordPress, authenticate MCP, or access other REST namespaces. REST callback user context is restored after processing to avoid leaking identity into other batched operations.

Remote writes default off, including on upgrade. The administrator must explicitly authorize remote application and select allowed fields. Only proposals created by the associated editor can be remotely applied, with a digest binding the exact target/before/after values. The same snapshot conflict guard protects apply/rollback. Field authorization is broad across objects the editor may edit, not a one-off approval per article: select it deliberately. The GPT must follow the user's instruction and ChatGPT's consequential-action confirmations. A digest is integrity checking, not evidence of human approval.

Keep the API-key GPT private. Anyone able to invoke that shared credential could use the approved scope. The key is not an OAuth token and must never be pasted into a chat. The old Application Password exposed in a conversation should be revoked independently.

Yoast owns tag generation. Overrides use documented output filters and are retained only in CodeIXE-owned metadata. Deactivating this integration restores Yoast's own output. Its editor fields, scores and schema are not synchronized. Audit effective/rendered output rather than assuming plugin-editor previews changed.
