# Architecture and MVP scope

The entry point loads namespaced PHP classes without a runtime dependency manager. Plugin owns hooks/settings/permission gates; Metadata owns opt-in fallback output; Audit owns bounded local/public analysis; Changes owns validated immutable proposals, snapshots and apply/rollback; API and Abilities share service methods; Admin owns the interactive human approval boundary and configuration of the optional remote field policy.

Audits return evidence, severity, scope and recommendations. Stored content is not treated as final page HTML. Returned links are an inventory for client review, not a claim that links are broken or suggested. HTML findings and site configuration cannot establish actual Google index status. No ranking, traffic or revenue predictions are generated.

Writes are explicit field replacements. The Actions client can generate proposed edits, then apply them only within the administrator-selected policy. A client may generate proposed titles, meaningful ALT descriptions or improved content, but the exact replacement is sanitized and shown before application. The proposal cannot alter authors, post status, plugins, users, server settings or arbitrary metadata.

Version 0.2.0 adds Yoast title/description output overrides through documented presenter filters. Other third-party SEO integrations remain detection/deferral only. Add provider adapters only after verifying public APIs/hooks and testing installed versions. Do not rely on undocumented protected meta/database formats. No duplicate canonical/schema/sitemap generators are added. Fallback metadata only targets singular content; taxonomy and general archive metadata are not implemented.

Release roadmap: staging verification and provider adapters; secure tested OAuth bridge with an established provider; taxonomy/homepage crawls; rate-limited link validation; duplicate-content signals; sitemap/robots inspection; schema checks; background job queues; scoped bulk approval and retention controls; complete translations and separate administration sections. These are not advertised as working features.

Actions owns the public OpenAPI schema, dedicated hashed credential, isolated REST identity and remote field/digest authorization. A private Custom GPT may use this REST interface without an OAuth server. It is separate from the optional MCP interface.
