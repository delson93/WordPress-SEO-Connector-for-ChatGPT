# REST API v1

Except for the public `/openapi` schema, all routes require HTTPS, enabled services, an authenticated user with `edit_posts`, and per-object `edit_post` permissions. Unknown object types, trash and auto-drafts are rejected. Unpublished content is accessible only by explicit ID to a user allowed to edit it. Lists expose published, non-password-protected posts/pages only. Responses are private/no-store.

| Method | Relative route | Purpose |
| --- | --- | --- |
| GET | `/openapi` | Public OpenAPI 3.1 schema only |
| POST | `/proposals/123/apply` | Apply exact proposal with `{ "digest": "..." }` |
| POST | `/proposals/123/rollback` | Restore exact proposal with its digest |
| GET | `/status` | Capability and diagnostic flags, no credentials |
| GET | `/posts?page=1&type=post` | Ten posts/pages/attachments; `has_more` pagination |
| GET | `/posts/123` | Raw editable content and connector metadata |
| GET | `/posts/123/audit?rendered=true` | Stored analysis, optionally public rendered HTML |
| GET | `/audit?page=1&type=post` | Ten-object stored-content audit batch |
| POST | `/proposals` | Create a pending before/after proposal |
| GET | `/history?page=1` | Twenty accessible change records |

Proposal body:

```json
{"id":123,"changes":{"seo_title":"An accurate title for the article","description":"An accurate and useful summary of this article for readers deciding whether to visit it."}}
```

Supported fields: `post_title`, `post_content`, `post_excerpt`, `alt`, `seo_title`, `description`, `canonical`, `noindex`, `nofollow`. Values are strings. Robots flags accept `"1"` or `""`; empty removes this connector's directive, never a directive from another provider. Canonicals must use HTTPS and the site's hostname. ALT accepts image attachments only; attachments accept no other field. Content is limited to 200,000 bytes, other values to 2,000 bytes, and HTML is sanitized through WordPress KSES.

SEO fields require active fallback or an enabled supported provider integration. Yoast supports `seo_title` and `description` through connector-owned output overrides. No protected Yoast fields are written. Other metadata providers are deferred. `effective_metadata` in object reads contains the current provider's title/description when available; `metadata` contains connector-owned values.

Actions credentials are accepted as `Authorization: Bearer KEY` only in this namespace. Keys are associated with a dedicated non-administrator editor, stored hashed, shown once and revocable. `/openapi` exposes no secrets or private content. Native Application Passwords remain valid for existing read/proposal operations.

Remote apply and rollback require the valid Actions key, read-only off, an administrator-enabled remote policy, selected allowed fields, a proposal created by the associated user and the exact SHA-256 `digest` returned with the proposal. Canonical and robots fields cannot be remotely applied. The digest binds the exact snapshot; administrator field policy and the user's request provide authorization, not the digest alone. Responses identify approval_method. ChatGPT actions mark writes consequential.

Administrator forms continue to support manual approval/rollback with a browser session and nonce. States: `pending → applying → applied → rolling_back → rolled_back`; failed verification becomes `failed`. Later edits cause conflicts rather than overwrites.

Error responses use WP_Error codes and appropriate 400/403/409/429 statuses. Operational database failures may use WordPress-native statuses. Limits are per authenticated user, approximate transient counters: 30 audits, 6 batches, 20 proposals and 10 public fetches per minute. These are workload guards, not a distributed WAF.
