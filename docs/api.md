# REST API v1

All routes require HTTPS, enabled services, an authenticated user with `edit_posts`, and per-object `edit_post` permissions. Unknown object types, trash and auto-drafts are rejected. Unpublished content is accessible only by explicit ID to a user allowed to edit it. Lists expose published, non-password-protected posts/pages only. Responses are private/no-store.

| Method | Relative route | Purpose |
| --- | --- | --- |
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

SEO fields require active fallback mode and no detected external SEO provider. This is deliberate: saving unused values or overwriting third-party protected fields would mislead users. Existing third-party SEO values are not returned as if they were connector-owned values.

No approval, application or rollback REST route exists. A logged-in administrator uses a nonce-protected WordPress form after reviewing exact before/after values. `pending → applying → applied → rolling_back → rolled_back`; verification failures become `failed`. Stale snapshots return conflict errors without overwriting later edits.

Error responses use WP_Error codes and appropriate 400/403/409/429 statuses. Operational database failures may use WordPress-native statuses. Limits are per authenticated user, approximate transient counters: 30 audits, 6 batches, 20 proposals and 10 public fetches per minute. These are workload guards, not a distributed WAF.
