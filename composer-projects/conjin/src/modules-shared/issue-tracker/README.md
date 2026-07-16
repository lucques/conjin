# Issue tracker

The `issue-tracker` module provides an issue tracker that can be embedded in a
Conjin page.


## Persistence

The tracker stores its data in a server-side SQLite database. The database file
is located in Conjin's [store](../../../doc/store.md), so the deployment must
have a store configured and writable by the web-server user.

The database is opened when `IssueTracker` is constructed. If the configured
file does not exist, it is created and initialized with the required tables and
configured tags. An existing file is reused without changing its tags. The
legacy issue `description` column is renamed to `summary` automatically.
Preprocessing and normal deployment uploads do not replace the database.


## Definitions

- **Issue**: A tracked discussion or task with an ID, summary, tags, a status,
  and a list of posts.
- **Post**: An entry in an issue containing its serialized author identity,
  author email (when available), date, text, and selected status.
- **Status**: The status of the latest post and therefore the current status of
  the issue. The provided forms support `open` and `closed`.
- **Tag**: A configured label that can be assigned when creating an issue.

Creating an issue also creates its first post and sets the issue to `open`.
Administrators may assign that first post to an OpenID identity other than their
own by selecting a configured provider and entering its provider-specific ID.
They may also provide an optional email address for that author. When present,
the address is stored with the post and added to the notification recipients;
when omitted, no address is inferred or added.
The server accepts the override only for provider names present in the global
OpenID configuration; an invalid override is ignored and the current user
remains the author.


## API

Create an `IssueTracker` with a `Config`. The configuration contains:

- `file_name`: SQLite filename relative to the store.
- `view_target_ids`: Target IDs used in notification links.
- `tag_names`: Tags used when initializing a new database.
- `privilege_admin`: Global custom privilege required to edit or delete issues and posts without a time limit. Unauthorized requests are ignored and admin controls are omitted.
- `privilege_write`: Global custom privilege required to create issues or posts. Unauthorized requests are ignored and creation controls are omitted.
- `may_edit_own_content_within_n_minutes`: Number of minutes during which a user may edit or
  delete their own posts. `0` disables this capability. Deleting the
  only post in an issue also deletes the now-empty issue; users still cannot
  directly edit or delete issues through this capability.
- `view`: Display labels, column widths, and presentation callbacks. Its
  `author_identity_to_label` callback receives a serialized user identity and
  returns the author label shown for a post.
- `mail`: Optional mail notification configuration.

The main methods are:

- `fetch_tags()`: Returns all available tags.
- `fetch_issues_with_posts_open()`: Returns all open issues and their posts.
- `fetch_open_issue_counts_by_tag()`: Returns the number of open issues for
  every configured tag, keyed by tag name. Tags without open issues map to `0`.
- `fetch_issues_with_posts_closed($how_many = 3)`: Returns recently updated
  closed issues and their posts.
- `handle_new_issue($tags)`: Handles submission of the new-issue form.
- `handle_new_post()`: Handles submission of a new-post form.
- `handle_edit_issue($tags)`: Updates an issue's summary and tags from its edit
  form and returns its handling result.
- `handle_edit_post()`: Updates a post's content and status and returns its
  handling result.
- `handle_delete_issue()`: Deletes an issue together with its posts and tag
  assignments and returns its handling result.
- `handle_delete_post()`: Deletes a post, recomputes its issue's first/latest
  post metadata, and returns its handling result. Deleting the final post also
  deletes the empty issue and its tag assignments.
- `print_new_issue_form($tags, $error = null)`: Prints a button that opens the
  new-issue form in a modal.
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
- `print_issue_as_acc_item($issue, $error = null)`: Prints an issue, its posts, and
  its new-post form.
- `print_handle_new_issue_res($result)`, `print_handle_new_post_res($result)`,
  `print_handle_edit_issue_res($result)`, `print_handle_edit_post_res($result)`,
  `print_handle_delete_issue_res($result)`, and
  `print_handle_delete_post_res($result)`: Print submission feedback.

The request handlers return `null` when the current request does not apply;
otherwise they return a result describing success or a database error. New
issue, new-post, and edit-post results can additionally describe incomplete
input. Result class suffixes `NIR`, `NPR`, `EIR`, `EPR`, `DIR`, and `DPR` mean
**New Issue Result**, **New Post Result**, **Edit Issue Result**, **Edit Post
Result**, **Delete Issue Result**, and **Delete Post Result**.

Post ownership is determined by comparing the stored serialized user identity
with the current resolved user's serialized identity. Static identities contain
the configured user name; OpenID identities contain both provider name and
provider-specific user ID. Runtime data such as email addresses and OpenID
markers is not part of the identity comparison. Guests are represented by the
configured static guest identity and are therefore treated as the same author.
