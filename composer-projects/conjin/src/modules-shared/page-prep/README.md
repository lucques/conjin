# page-prep

Coordinates one initial browser document through four readiness barriers. Activate it on a target that needs a prepared source document; print-mode activates it automatically. Preparation does not hide ordinary pages. When this module is inactive, participating modules still run their usual asynchronous work without registering readiness tasks.

## API

- **Register work:**
    - `pagePrep.track({ id, before, after?, promise })` tracks work already started.
    - `pagePrep.register({ id, before, after?, run })` starts a function after its preceding barrier. IDs must be unique per document.
- **Advance through barriers:** The stages run from start to `initialContentReady`, then `renderedContentReady`, `layoutResourcesReady`, and `readyForPagination`.
    - `pagePrep.wait(barrier)` returns a stable promise for a barrier to external consumers, such as print-mode. Inside registered tasks, use the supplied `context.wait()` instead.
- **Manage work inside `run(context)`:** Use `context.wait(barrier)` for an earlier barrier, or `context.track({ id, promise })` for a child in the same stage while `run` remains pending. A rejected task rejects its barrier and later barriers with an error containing `taskId`, `barrier`, and `cause`.
- **Close registration:** Templates call `pagePrep.closeRegistration()` after their after-DOM snippets and body-bottom elements. Later root registrations throw. Empty stages resolve only after registration closes and the preceding barrier resolves. Repeated closing calls are safe.

## Waiting inside tasks

Declare task ordering through `after` and `before`. If a task needs an explicit barrier wait, use `context.wait(barrier)`. The context retains the task identity across asynchronous continuations and rejects waits for its own or a later barrier, including after an `await`. An uncaught rejection fails preparation with the task ID, barrier and original cause.

The global `pagePrep.wait()` is for consumers outside registered tasks. Its synchronous guard catches some accidental task calls, but cannot identify the caller after an `await` or in an already-running tracked promise. Global barrier waits inside tasks are unsupported and can deadlock preparation. Promises passed to `track()` must not depend on their own or a later barrier either; use `register()` with stage ordering when work needs a preceding barrier.

## Resources

The built-in resource task runs after `renderedContentReady`. It waits for normal stylesheets, decodes images except those explicitly marked `loading="lazy"`, and awaits `document.fonts.ready`. It sees images inserted by registered content tasks even after `window.load`. Stylesheet links without a nonempty `href` are ignored. Failed included resources reject readiness. Print-relevant images must be non-lazy. Canvas, iframe, media and CSS background resources require explicit tasks when their output matters for printing.
