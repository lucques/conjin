# Issue tracker

The `issue-tracker` module provides an issue tracker that can be embedded in a
Conjin page.


## Persistence

The tracker stores its data in a server-side SQLite database. The database file
is located in Conjin's [store](../../../doc/store.md), so the deployment must
have a store configured and writable by the web-server user.

The database is opened when `IssueTracker` is constructed. If the configured file does not exist, it is created and initialized with the required tables and configured tags. Configured tag IDs, names, optional URLs, client data, and additional notification addresses are stored as provided. An existing file is reused without changing its tags; the additive `issue_x_additional_email` and `tag_x_additional_email` tables and nullable `tag.url` column are created automatically when missing. Preprocessing and normal deployment uploads do not replace the database.


## Definitions

- **Issue**: A tracked discussion or task with an ID, summary, tags, a status,
  and a list of posts.
- **Post**: An entry in an issue containing its serialized author identity,
  author email (when available), date, text, and selected status.
- **Additional email event**: An immutable authored entry containing its serialized author identity, author email (when available), date, and an additional notification address. Administrators can add and delete these events. They are shown in the public issue timeline but do not change issue status.
- **Status**: The status of the latest post and therefore the current status of
  the issue. The provided forms support `open` and `closed`.
- **Tag**: A label with a stable textual ID that can be assigned when creating an issue. Administrators can add tags and edit their names, optional home-page URLs, optional client data, and additional notification addresses, but tags cannot be removed. Editing a name does not change its ID.

Creating an issue also creates its first post and sets the issue to `open`. Administrators may assign the first post or a later post to an OpenID identity other than their own by selecting a configured provider and entering its provider-specific ID. They may also provide an optional email address for that author. When present, the address is stored with the post and added to the notification recipients; when omitted, no address is inferred or added. The server accepts the override only for provider names present in the global OpenID configuration; an invalid override is ignored and the current user remains the author. Administrators may also add an additional notification address to an existing issue. This creates an authored event using the administrator's current serialized identity and email. Notification recipients combine configured addresses, post-author addresses, additional-email-event author addresses, explicitly added issue addresses, and addresses associated with the issue's current tags, deduplicated case-insensitively.


Administrators can also enter an optional batch of additional notification addresses in the new-issue form, independently of the author override. The Tom Select token input accepts pasted lists separated by commas, semicolons, or whitespace. Server validation deduplicates addresses case-insensitively and rejects the entire submission if any address is invalid, retaining the form values for correction. Each accepted address creates an additional-email event attributed to the acting administrator. The issue, first post, tags, and events are saved in one transaction before the initial notification is collected; failed mail delivery retains the saved issue. Non-administrator submissions cannot add these addresses.

## Notification templates

All four subject and body closures in `MailConfig` receive the issue's current tag titles (the tags' `name` values) as a final `list<string> $tag_names` argument. Names are read from the database when preparing the notification, with one entry per associated tag, ordered by name case-insensitively and then by tag ID. Tags without notification addresses are included, repeated assignments of the same tag produce one entry, and an untagged issue produces `[]`. Notifications for later posts reflect tag renames and changes to the issue's tag assignments.

| Closure | Arguments in order |
| --- | --- |
| `new_issue_template_subject` | `$issue_id, $issue_summary, $tag_names` |
| `new_issue_template_body` | `$issue_id, $view_issue_url, $date, $author, $content, $tag_names` |
| `new_post_template_subject` | `$issue_id, $tag_names` |
| `new_post_template_body` | `$issue_id, $view_issue_url, $date, $author, $content, $new_status, $previous_posts, $tag_names` |

Existing closures can keep their parameter lists and ignore the additional argument. To include tags, accept `array $tag_names` as the final parameter and format them with, for example, `implode(', ', $tag_names)`. The following named arguments illustrate subject and body templates in a `MailConfig` constructor:

```php
new_issue_template_subject: fn(int $issue_id, string $issue_summary, array $tag_names): string =>
    'Issue #' . $issue_id . ': ' . $issue_summary
    . ($tag_names === [] ? '' : ' [' . implode(', ', $tag_names) . ']'),
new_issue_template_body: fn(int $issue_id, string $view_issue_url, string $date, string $author, string $content, array $tag_names): string =>
    $content . "\n\nTags: " . implode(', ', $tag_names) . "\n" . $view_issue_url,
```


## Tag notification addresses

Each tag can have any number of additional notification addresses in `tag_x_additional_email`. The admin tag forms use an unrestricted Tom Select token input; submitted addresses may be separated by commas, semicolons, or whitespace. Addresses are trimmed, validated, and deduplicated case-insensitively before the tag and its complete address list are saved atomically.

Every recipient receives the same notice block after the configured notification body. Recipients are collected as one entry per email address, with an `email_address` and a `reasons` list containing every distinct reason. Each configured `always_notify_addresses` entry uses `reason_always_notify`; post authors, additional-email-event authors, and explicitly added issue addresses use `reason_participates_in_issue`; relevant tag notification addresses use the reason rendered by `reason_tag_additional_email`. The `notification_block` closure and the on-screen success alert use the same recipient list, showing every address with all its reasons. An address associated with several relevant tags or notification sources receives one email and retains all distinct reasons.


## Tag home pages and bulk mail

A tag URL is optional and must be a complete HTTP or HTTPS URL. The add and edit forms expose it, and the admin tag table displays it as a clickable link or a dash when absent; an empty value is stored as `NULL`. Initial tag configuration may supply a `url` value, which is used only when creating a new database. Existing tag URLs can be populated with a separate SQL migration.

`handle_mail_tags()` handles the admin-only `mail-tags` form when `mail` is configured. The form contains required Subject and Content fields. Both accept the case-sensitive `{{tag}}`, `{{url}}`, and `{{client_data}}` placeholders. Each occurrence is replaced for each tag; missing URL or client data becomes `(nicht vorhanden)`, while an assigned empty client data value stays empty. Client data is inserted as its raw stored text. The content is sent as plain text with its line breaks, without the normal issue-notification footer. The rendered subject has no added prefix.

Sending uses one message per tag and addresses only that tag's additional email addresses in `To`. Tags without addresses are skipped. An address assigned to multiple tags receives multiple messages. A failed send does not stop later tags. The handler returns sent and failed tag names with their email addresses, skipped tag names, and error details for immediate display. The confirmation lists addresses beneath each sent or failed tag; it stores no mail history. The request uses the normal POST deduplication guard. When `mail` is absent, the form is disabled and the handler sends nothing.

Pages that show the action call `handle_mail_tags()`, `print_handle_mail_tags_res()`, and `print_mail_tags_modal()`, then pass the returned modal ID as the fourth argument to `print_tag_management()`.

## Client data

Each tag has a nullable `client_data` text value for application-specific metadata. The issue tracker stores and returns this value unchanged and does not validate, serialize, parse, or otherwise interpret it. Clients may use a plain string, JSON, or another text representation; `NULL` means that no client data is assigned. For example, a client that needs structured responsibility information could store:

```json
{
  "responsible": "Alex Example",
  "substitute": "Robin Example"
}
```

The admin tag modals expose the raw value in a multiline textarea. The accompanying checkbox distinguishes `NULL` from a present value, including an empty string.

`ViewConfig::tag_client_data_desc` sets the label used in the tag management table and editor; it defaults to `Client-Daten`. `ViewConfig::tag_client_data_to_text` optionally transforms a non-null stored value for display in the management table. It receives the raw string and returns plain text, which the tracker HTML-escapes. When omitted, the raw string is displayed unchanged. Editing and persistence always use the raw value.

## API

Create an `IssueTracker` with a `Config`. The configuration contains:

- `file_name`: SQLite filename relative to the store.
- `view_target_ids`: Target IDs used in notification links.
- `tags`: Tag arrays used when initializing a new database. Each array provides the stable textual `id`, display `name`, optional nullable `url`, optional nullable `client_data`, and optional `additional_email_addresses` list; omitted client data is stored as `NULL`, and omitted addresses default to an empty list.
- `privilege_admin`: Global custom privilege required to edit or delete issues and posts without a time limit. Unauthorized requests are ignored and admin controls are omitted.
- `privilege_write`: Global custom privilege required to create issues or posts. Unauthorized requests are ignored and creation controls are omitted.
- `may_edit_own_content_within_n_minutes`: Number of minutes during which a user may edit or
  delete their own posts. `0` disables this capability. Deleting the
  only post in an issue also deletes the now-empty issue; users still cannot
  directly edit or delete issues through this capability.
- `view`: Display labels, column widths, and presentation callbacks. `tag_desc` supplies the configurable plural tag noun used by forms and management headings, and `tag_desc_singular` supplies its singular form for actions, ID labels, messages, and accessibility text. `tag_client_data_desc` labels client data in the tag editor and management table. `tag_client_data_to_text` optionally transforms it for display as escaped plain text. `solved_issue_count_desc` and `unsolved_issue_count_desc` provide the two tag-table count headings. Its `author_identity_to_label` callback receives a serialized user identity and returns the author label shown for a post. Its optional `author_openid_id_placeholder` value sets the placeholder for the OpenID-ID field and defaults to an empty string.
- `mail`: Optional mail notification configuration. When supplied, `notification_block`, `reason_always_notify`, `reason_participates_in_issue`, and `reason_tag_additional_email` are required. The `notification_block` closure receives a `list<array{email_address: string, reasons: list<string>}>` and returns the text appended to the configured mail body. The tag reason string supports the `{email_address}` and `{tag_name}` placeholders. Successful new-issue and new-post results expose this same list as `recipients`.

`ViewConfig::table_min_width` is an optional CSS width that defaults to `48rem`. It sets the minimum width of each issue overview rendered by `print_issues_as_acc()`, including the issue headers, expanded timelines, and new-post forms. The overview scrolls horizontally within its own wrapper when the available space is narrower, while post text continues to wrap normally. Headers and expanded content share one scroll area.

The main methods are:

- `fetch_tags()`: Returns the IDs and names of all available tags.
- `fetch_tag_client_data($tag_id)`: Returns the nullable client data for one tag ID, or `NULL` if the tag does not exist or has no client data.
- `fetch_tags_with_issue_counts()`: Returns all tags with their nullable `url` and `client_data` values, additional notification addresses, and distinct `open` and `closed` issue counts.
- `fetch_issues_with_posts_open()`: Returns all open issues and their posts.
- `fetch_issues_with_posts_open_by_tag_id($tag_id)` and `fetch_issues_with_posts_closed_by_tag_id($tag_id, $how_many = 3)`: Return issues assigned to a stable tag ID.
- `fetch_open_issue_counts_by_tag()`: Returns the number of open issues for
  every configured tag, keyed by tag name. Tags without open issues map to `0`.
- `fetch_issues_with_posts_closed($how_many = 3)`: Returns recently updated
  closed issues and their posts.
- `handle_new_issue($tags)`: Handles submission of the new-issue form.
- `handle_new_post()`: Handles submission of a new-post form.
- `handle_new_additional_email()`: Handles the admin-only additional-email form.
- `handle_new_tag()` and `handle_edit_tag()`: Handle the admin-only tag creation and editing forms. Administrators enter the ID when creating a tag; editing a name preserves the existing ID. Optional URLs are checked for a complete HTTP or HTTPS address, optional client data is stored verbatim, and additional notification addresses are validated and replaced as one list.
- `handle_edit_issue($tags)`: Updates an issue's summary and tags from its edit
  form and returns its handling result.
- `handle_edit_post()`: Updates a post's content and status and returns its
  handling result.
- `handle_delete_issue()`: Deletes an issue together with its posts and tag
  assignments and returns its handling result.
- `handle_delete_post()`: Deletes a post, recomputes its issue's first/latest
  post metadata, and returns its handling result. Deleting the final post also
  deletes the empty issue and its tag assignments.
- `handle_delete_additional_email()`: Deletes an additional-email event. Only administrators may perform this action.
- `print_new_issue_form($tags, $error = null, $preselected_tag_names = [], $preselected_tag_ids = [])`: Prints a button that opens the new-issue form in a modal.
- `print_tag_management($tags, $new_tag_modal_id, $edit_tag_modal_id, $mail_tags_modal_id = null)`: Prints the tag table with names, URLs, client data, additional notification addresses, and issue counts for all users. Edit, add, and mail controls are shown only to administrators. Callers provide any heading or accordion around it.
- `print_new_tag_modal($result = null)` and `print_edit_tag_modal($result = null)`: Print matching admin-only tag forms and return their modal IDs.
- `print_edit_issue_modal($tags)`: Prints the page's shared edit form and
  returns its modal ID. Pass that ID to each `print_issues_as_acc()` call.
- `print_edit_post_modal($result = null)`: Prints the page's shared post-edit
  form and returns its modal ID. Pass the edit result to restore an incomplete
  submission, and pass the returned ID to each `print_issues_as_acc()` call.
- `print_delete_issue_modal()`: Prints the page's shared deletion-confirmation
  form and returns its modal ID. Pass that ID to each `print_issues_as_acc()`
  call.
- `print_delete_post_modal()`: Prints the page's shared post-deletion
  confirmation form and returns its modal ID. Pass that ID to each
  `print_issues_as_acc()` call.
- `print_delete_additional_email_modal()`: Prints the admin-only shared confirmation form for deleting additional-email events and returns its modal ID.
- `print_issue_as_acc_item($issue, $error = null)`: Prints an issue, its posts, and
  its new-post form.
- `print_handle_new_issue_res($result)`, `print_handle_new_post_res($result)`, `print_handle_new_additional_email_res($result)`, `print_handle_new_tag_res($result)`, `print_handle_edit_tag_res($result)`, `print_handle_edit_issue_res($result)`, `print_handle_edit_post_res($result)`, `print_handle_delete_issue_res($result)`, `print_handle_delete_post_res($result)`, and `print_handle_delete_additional_email_res($result)`: Print submission feedback.

The request handlers return `null` when the current request does not apply; otherwise they return a result describing success or a database error. New-issue, new-post, new-tag, edit-tag, and edit-post results can additionally describe incomplete input. Result class suffixes `NIR`, `NPR`, `NTR`, `ETR`, `EIR`, `EPR`, `DIR`, and `DPR` mean **New Issue Result**, **New Post Result**, **New Tag Result**, **Edit Tag Result**, **Edit Issue Result**, **Edit Post Result**, **Delete Issue Result**, and **Delete Post Result**.

Post ownership is determined by comparing the stored serialized user identity
with the current resolved user's serialized identity. Static identities contain
the configured user name; OpenID identities contain both provider name and
provider-specific user ID. Runtime data such as email addresses and OpenID
markers is not part of the identity comparison. Guests are represented by the
configured static guest identity and are therefore treated as the same author.


## Structure

`processing.php` is the module entry point and contains the `IssueTracker` class, including authorization, database access, request handling, and notifications. It loads `datatypes.php` for configuration classes and request-result types, and `processing_view.php` for the internal `View` trait containing rendering methods and their helpers. The trait is used by `IssueTracker`, so existing method calls and configuration remain unchanged.

## CSRF protection and duplicate submissions

State-changing forms use Conjin's shared [submission-token mechanism](../../../../../doc/submission-tokens.md).
