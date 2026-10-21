# Document readiness and pagination

The optional page-prep module coordinates one initial browser document. Print-mode activates it for the standalone print document, then awaits readyForPagination before starting Paged.js or marking the native print document ready. The wrapper and iframe have separate lifecycles. Ordinary pages remain visible while tasks run.

| Barrier | Completed work |
| --- | --- |
| initialContentReady | Initial DOM and registered SQL, widget and page content |
| renderedContentReady | Final MathJax, Prism and other render passes |
| layoutResourcesReady | Normal stylesheets, non-lazy images, used fonts and explicit resource tasks |
| readyForPagination | Final measurements such as sync-dims |

Modules register root tasks synchronously during page setup. The template closes root registration after its after-DOM snippets and body-bottom elements. A task already started uses pagePrep.track with its promise; later stage work uses pagePrep.register with a run function. Each ID is unique in its document. A pending task can use the run context's track method to add a child in its stage. The promise must include the final DOM write. A stage failure carries its task ID, barrier and cause to callers and prevents later stage functions from running. See the [page-prep module](../composer-projects/conjin/src/modules-shared/page-prep/README.md) for its API.

External consumers use `pagePrep.wait()`. Tasks declare prerequisites through `after`/`before` and use their supplied `context.wait()` for explicit waits. Context waits reject own-stage and later-stage dependencies even after an `await`. The global wait only has a synchronous task guard; using it inside tasks is unsupported and can deadlock. Already-running promises passed to `track()` must also avoid depending on their own or later barriers.

When page-prep is inactive, modules still start their ordinary asynchronous rendering but do not register readiness tasks. Consumers that need ordered initial preparation activate page-prep. Content readiness alone does not mean resources or measurements are ready.

Initial SQL result, schema, history and tagged views register their final insertion. MathJax and Prism disable their automatic initial passes when page-prep is active and register one render pass after initial content. Sync-dims registers its final measurement after resources. Page-owned dependent SQL queries can register one promise chain including insertion. Later clicks, resize, fragment navigation and recurring updates are outside initial preparation.

The resource scan includes original and inserted images unless loading="lazy" is explicit. It does not change image loading mode. Print-relevant images must be non-lazy. Modules must register asynchronous stylesheet insertion and load completion before renderedContentReady. Background images, canvas, media and iframe content need explicit tasks when their output matters for printing.

printModePromise resolves after Paged.js completes or native print preparation finishes. printModeReady and print-mode-ready indicate the document is ready to print; print-mode-error and printModeError report preparation or pagination failure. Modules register their own task at the appropriate page-prep stage.
