# Architecture and MVP scope

The entry point loads namespaced PHP classes without a runtime dependency manager. Plugin owns hooks/settings/permission gates; Metadata owns opt-in fallback output; Audit owns bounded local/public analysis; Changes owns validated immutable proposals, snapshots and apply/rollback; API and Abilities share service methods; Admin owns the interactive human approval boundary.

Audits return evidence, severity, scope and recommendations. Stored content is not treated as final page HTML. Returned links are an inventory for client review, not a claim that links are broken or suggested. HTML findings and site configuration cannot establish actual Google index status. No ranking, traffic or revenue predictions are generated.

Writes are explicit field replacements, not autonomous generation. A client may generate proposed titles, meaningful ALT descriptions or improved content, but the exact replacement is sanitized and shown before application. The proposal cannot alter authors, post status, plugins, users, server settings or arbitrary metadata.

Third-party SEO integrations are detection/deferral only in 0.1.0. Add provider adapters only after verifying public APIs/hooks and testing installed versions. Do not rely on undocumented protected meta/database formats. No duplicate canonical/schema/sitemap generators are added. Fallback metadata only targets singular content; taxonomy and general archive metadata are not implemented.

Release roadmap: staging verification and provider adapters; secure tested OAuth bridge with an established provider; taxonomy/homepage crawls; rate-limited link validation; duplicate-content signals; sitemap/robots inspection; schema checks; background job queues; scoped bulk approval and retention controls; complete translations and separate administration sections. These are not advertised as working features.
