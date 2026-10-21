# Feedback collector

`feedback-collector` embeds a configurable feedback form in a Conjin page. Each accepted submission records a UTC timestamp and a self-contained snapshot of the form and its answers in SQLite, then emails that snapshot to one configured recipient. Identified submissions also record the authenticated identity and email address; anonymous submissions omit that metadata. Submissions are immutable; a person may submit again using a fresh form. There is no administration page or email notification status column.

## Setup

Register `T.SharedBareModule.FeedbackCollector {=}` in the deployment's shared modules, then activate `feedback-collector` during target preprocessing. The module activates `doc-extensions` for its form JavaScript and uses Bootstrap form classes, like `issue-tracker`. The deployment needs a writable [store](../../../../../doc/store.md), PDO SQLite, mbstring, and Conjin's global mail configuration. Notification delivery uses PHPMailer and the global `mail.senderEmail`, following issue-tracker's mail transport.

```php
use Conjin\Modules\FeedbackCollector as F;

$collector = new F\FeedbackCollector(new F\Config(
    file_name: 'feedback.sqlite',
    email_address: 'recipient@example.org',
    anonymity: F\Anonymity::Optional,
    form: new F\FormConfig('IT feedback', [
        new F\Section('WLAN and internet', [
            new F\Question('WLAN problems?', new F\SingleChoice([
                new F\Option('No'),
                new F\Option('Yes', [
                    new F\Question('What happens?', new F\Text(), required: true)
                ])
            ]), required: true)
        ]),
        new F\Question('Equipment used or wanted', new F\MultipleChoice([
            new F\Option('PC'),
            new F\Option('Other', [
                new F\Question('Which equipment?', new F\Text())
            ])
        ]))
    ])
));
$result = $collector->handle_submission();
$collector->print_submission_result($result);
$collector->print_form($result);
```

`Config` also accepts an optional `privilege_write` custom privilege and an optional `author_identity_to_label` closure taking the serialized authenticated identity. Without a custom privilege, any logged-in user who can access the containing target may submit. Guests cannot submit, including in anonymous mode. Page authorization remains the containing application's responsibility. For identified submissions, the display label is snapshotted alongside the full serialized identity; without a callback, the serialized identity also serves as the label. Anonymous submission handling does not call the callback; rendering the optional identity choice may use it when no authenticated email address is available.

## Anonymity

`Config::$anonymity` accepts the backed enum `Anonymity`:

| Mode | Behavior |
| --- | --- |
| `Anonymity::Anonymous` (`anonymous`) | Always anonymous; no anonymity choice. |
| `Anonymity::NonAnonymous` (`non-anonymous`) | Always identified; no anonymity choice. This is the default for compatibility. |
| `Anonymity::Optional` (`optional`) | Shows two radio buttons: “Anonym” (selected by default) and “Mit Name (identity)”. The parentheses show the authenticated email address, falling back to the configured identity label or account ID when no email is available. |

The optional radio buttons work with or without JavaScript. Its state is preserved after validation and database errors; each fresh form defaults to anonymous again. Fixed modes are enforced by the server regardless of posted choice values. Optional mode requires an explicit identified choice to include identifying metadata; a missing value defaults to anonymous, and malformed values are rejected.

Anonymous submissions store an empty string in `author_identity`, `author_label: "Anonym"`, and `author_email: null`. Their notifications omit the identity and email lines entirely. Identified submissions snapshot `auth_get_user_email()` and include it in the email body; accounts without an available address retain `null`, and the notification says the address is unavailable. No email address is inferred from a label or supplied by the submitted form. Existing rows are not changed, and no database migration is needed.

Authentication is still used for access control and session tokens. Anonymity controls the sender metadata saved with a submission and included in its notification; answer text is preserved as entered.

## Question model

```text
FormItem = Question | Section
Section = { title, questions: List<FormItem> }
Question = { label, required, type }
QuestionType = Text | SingleChoice | MultipleChoice
Text = { multiline, max_length }
SingleChoice = { options: List<Option> }
MultipleChoice = { options: List<Option> }
Option = { label, follow_up_questions: List<FormItem> }
```

These are immutable PHP data classes. `Question::$type` is a native union of `Text|SingleChoice|MultipleChoice`. Text defaults to a multiline field with a 10,000-character limit. Questions default to optional. Questions and options need no configured IDs. Labels are plain text, must not be empty, and are escaped when rendered. Repeated labels and reused question or option objects are supported; each position in the form represents an independent answer.

`FormConfig::$questions` and `Option::$follow_up_questions` accept lists mixing `Question` and `Section` objects. `new Section('Title', [$question1, $question2])` draws a card with a title and a body containing its questions. Sections are optional layout wrappers: they have no answer, required flag, or selection behavior. They can also be nested. Wrapping questions in sections does not change their field names or validation behavior.

The collector generates internal field names from positions in the question tree, including the parent options for nested questions, while ignoring section boundaries. These keys identify fields only within that form definition and are not stable identifiers across form versions.

Selecting an option reveals its follow-up questions recursively. Single choices use radios; multiple choices use checkboxes. A required multiple choice needs at least one selection. Optional single choices include “Keine Angabe” so a selection can be cleared. Yes/no questions are ordinary single choices. An optional comment is a separate text question at the appropriate nesting level.

Options containing follow-up questions display `<em>(enthält Folgefrage)</em>` for one question or `<em>(enthält Folgefragen)</em>` for several directly after their label, including while the option is unselected. Section wrappers do not count as questions. The notice “Mit * markierte Fragen sind Pflichtfelder.” appears at the top only if at least one question is configured as required anywhere in the form, including inside sections and inactive conditional branches.

Inactive branches are disabled in the browser, ignored by server validation, and never retain submitted answer values in storage or email. Their question wording and option definitions still appear in the snapshot, marked inactive. Without JavaScript, all branches remain visible and the server applies the same conditional validation. Validation failures preserve valid submitted values and display errors next to their questions.

## Persistence and notifications

The constructor creates or reopens the configured SQLite file relative to the store. Parent directories must already exist. It creates exactly one table:

| Column | Contents |
| --- | --- |
| `submission_id` | Integer primary key |
| `author_identity` | Conjin's serialized authenticated user identity, or an empty string for anonymous submissions |
| `created_on` | UTC timestamp in ISO 8601 format |
| `questions_answers` | UTF-8 JSON snapshot |

The version-1 JSON envelope contains `version`, `title`, `anonymous`, `author_label`, `author_email`, and `questions`. Every question retains its generated positional key as `id`, label, type, required flag, and active flag. Text questions also retain their multiline setting, length limit, and answer (`null` for unanswered or inactive text). Choice questions retain all options with their generated keys, labels, selection flags, and recursively snapshotted follow-up questions. No labels or answers need to be looked up in the current configuration. Existing snapshots with explicitly configured IDs or without anonymity and email metadata remain readable and are not rewritten.

Section nodes retain `type: "section"`, `title`, and their nested `questions`, without an ID or answer. This preserves the displayed grouping and context in stored submissions and emails without turning sections into questions. Existing snapshots without sections remain readable.

`FeedbackCollector::questions_answers_to_text($snapshot)` renders a decoded snapshot without a collector instance or form configuration. Emails use this renderer and include the submission ID, timestamp, every question, all options, and all answers. Identified notifications additionally include the sender's identity and email address. Unanswered and inactive questions are explicitly marked.

The insert commits before email is attempted. A missing mail configuration, transport error, or exception leaves the submission saved, logs the failure, and displays a warning that the user should not resubmit the same feedback. There is no automatic retry or persisted notification state.

Forms use Conjin's shared [submission-token mechanism](../../../../../doc/submission-tokens.md). The collector's scope includes the form configuration and anonymity policy, so changing either invalidates previously displayed forms. Answers from invalidated forms are not restored under potentially different questions or privacy settings.

`handle_submission()` returns `null` for unrelated requests, otherwise a `SubmissionResult` with a `kind`, optional `submission_id`, field `errors`, normalized `values`, and an optional `anonymous` choice retained after input or database errors. Result kinds are `Saved`, `SavedMailFailed`, `Invalid`, `Expired`, `Forbidden`, `ErrorDb`, and `Duplicate`. `print_submission_result()` renders its status; `print_form($result)` restores values for correction and issues a fresh token. Only one collector per database file should be embedded on the same page, because the database filename identifies its form.

## Verification

Run `bin/test php feedback_collector` from the repository root with PHP 8.3 or newer, PDO SQLite and mbstring available. Tests use temporary SQLite files and a mail stub; no messages leave the test process. Run `bin/test component-playwright feedback-collector.spec.ts` for browser checks; the shared Playwright fixture renders the PHP form automatically. See [test setup and preparation](../../../../../doc/tests.md) for browser prerequisites.
