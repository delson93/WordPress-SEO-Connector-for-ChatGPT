# Build, validation and release

Build locally with `python3 scripts/build.py`. The ZIP contains a stable `codeixe-wp-seo-connector/` root and runtime files, license and documentation. Development dependencies, CI, tests and Git data are excluded.

GitHub Actions validates PHP/JS syntax and runs PHPUnit integration tests against disposable WordPress/MySQL installations (WordPress 6.9/PHP 8.2 and current stable/PHP 8.4). Packaging depends on passing integration tests. Static analysis and WordPress Coding Standards are a separate advisory quality job while MVP hardening is outstanding. A green integration/package job does not mean a clean WPCS audit or WordPress.org approval.

## Validation record

Executed on 2026-10-20:

- All plugin/test PHP files passed syntax checks under PHP WebAssembly 8.5.10.
- WordPress 6.9 activated the plugin successfully using the official SQLite integration in a disposable local installation.
- The dependency-free integration runner executed the PHPUnit test methods under PHP WebAssembly 8.2: **14 tests, 41 assertions, zero failures**. It covered permissions, read-only/disabled gates, proposal/apply/rollback, stale snapshots, sanitization, external-provider deferral, per-object permissions, safe-fetch rejection, locks/rate limits, REST authentication and ability permissions.
- JavaScript syntax passed `node --check`.
- The runtime ZIP was built and its structure/development exclusions checked.

This local run used a lightweight assertion harness, not the PHPUnit binary, and SQLite rather than MySQL. The GitHub matrix uses PHPUnit with MySQL. See Actions for actual remote results; no unobserved CI result is claimed. WPCS, PHPStan, Plugin Check, live hosting, OAuth/ChatGPT and MCP transport interoperability have not been verified locally.

To repeat the local integration methods without PHPUnit, run `php scripts/smoke-tests.php /disposable/wordpress/wp-load.php` against an installed disposable WordPress with this plugin activated. The runner creates/deletes test content and history: never run it on a real site.

## Required staging acceptance before production

- Install/activate/deactivate on a disposable clone and confirm no frontend errors.
- Verify unauthorized REST/MCP requests fail, dedicated-editor audits succeed, and the client cannot approve.
- Create and review a proposal; apply, purge host/CDN cache, inspect public HTML, and rollback.
- Edit the target after proposal/application and verify stale apply/rollback refusal.
- Test the installed SEO plugin and theme; confirm exactly one canonical/title/description provider.
- Verify Authorization header propagation and HTTPS detection behind your hosting proxy.
- Test MCP discovery/execution with the exact adapter/client versions; authenticated ChatGPT OAuth integration is not yet supplied or verified.
- Run the official WordPress Plugin Check, WPCS, PHPStan and full translation review. Resolve their findings before submitting to WordPress.org.
- Review recovery/uninstall paths and back up the site.

No public release is automatically published. To prepare a release, review a successful CI ZIP and these acceptance checks, then obtain maintainer approval before creating the public tag/release. This MVP is not yet certified production-ready.
