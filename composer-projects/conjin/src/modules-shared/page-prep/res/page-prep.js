window.pagePrep = (() => {
    const barriers = ['initialContentReady', 'renderedContentReady', 'layoutResourcesReady', 'readyForPagination'];
    const stages = barriers.map(() => []);
    const ids = new Set();
    const stageFailures = barriers.map(() => {
        let reject;
        const promise = new Promise((resolve, no) => { reject = no; });
        promise.catch(() => {});
        return { promise, reject };
    });
    const deferred = barriers.map(() => {
        let resolve;
        let reject;
        const promise = new Promise((yes, no) => { resolve = yes; reject = no; });
        promise.catch(() => {});
        return { promise, resolve, reject };
    });
    let closed = false;
    let runningTask = null;

    function stageOf(after, before) {
        const index = barriers.indexOf(before);
        if (index < 0 || after !== (index === 0 ? undefined : barriers[index - 1])) {
            throw new Error(`Invalid page-prep stage: ${after ?? '(start)'} -> ${before}.`);
        }
        return index;
    }

    function add({ id, after, before, promise, run, parent }, deferredTask) {
        const stage = stageOf(after, before);
        if (typeof id !== 'string' || !id) {
            throw new Error('A page-prep task needs a nonempty ID.');
        }
        if (ids.has(id)) {
            throw new Error(`Duplicate page-prep task ID: ${id}.`);
        }
        if (parent) {
            if (parent.stage !== stage || parent.settled || !parent.started) {
                throw new Error(`Page-prep child ${id} must belong to a pending task in the same stage.`);
            }
        }
        else if (closed) {
            throw new Error(`Cannot register page-prep root task ${id}: registration is closed.`);
        }
        if (deferredTask ? typeof run !== 'function' : !('promise' in arguments[0])) {
            throw new Error(`Page-prep task ${id} requires ${deferredTask ? 'run' : 'promise'}.`);
        }
        ids.add(id);
        const task = { id, stage, started: !deferredTask, settled: false, run, promise: undefined };
        stages[stage].push(task);
        if (!deferredTask) {
            task.promise = observe(task, promise);
        }
        return task;
    }

    function observe(task, value) {
        const promise = Promise.resolve(value).catch(cause => {
            const error = new Error(`Page-prep task "${task.id}" failed at ${barriers[task.stage]}: ${cause?.message ?? cause}`, { cause });
            error.taskId = task.id;
            error.barrier = barriers[task.stage];
            console.error(error);
            stageFailures[task.stage].reject(error);
            throw error;
        }).finally(() => { task.settled = true; });
        promise.catch(() => {});
        return promise;
    }

    // Task-local waits retain identity across await boundaries.
    function context(task) {
        return {
            track(options) { return add({ ...options, after: task.stage ? barriers[task.stage - 1] : undefined, before: barriers[task.stage], parent: task }, false).promise; },
            wait(barrier) {
                const index = barriers.indexOf(barrier);
                if (index >= task.stage) {
                    throw new Error(`Page-prep task ${task.id} cannot wait for ${barrier}: it contributes to that barrier or a preceding one.`);
                }
                return wait(barrier);
            },
        };
    }

    async function drain(index) {
        const tasks = stages[index];
        let count = 0;
        while (count < tasks.length) {
            const batch = tasks.slice(count);
            count = tasks.length;
            for (const task of batch) {
                if (!task.started) {
                    task.started = true;
                    runningTask = task;
                    try {
                        task.promise = observe(task, task.run(context(task)));
                    }
                    catch (cause) {
                        task.promise = observe(task, Promise.reject(cause));
                    }
                    finally {
                        runningTask = null;
                    }
                }
            }
            await Promise.race([Promise.all(batch.map(task => task.promise)), stageFailures[index].promise]);
        }
    }

    function closeRegistration() {
        if (closed) {
            return;
        }
        closed = true;
        (async () => {
            for (let index = 0; index < barriers.length; index++) {
                try {
                    await drain(index);
                    deferred[index].resolve();
                }
                catch (error) {
                    for (let later = index; later < barriers.length; later++) {
                        deferred[later].reject(error);
                    }
                    return;
                }
            }
        })();
    }

    function wait(barrier) {
        const index = barriers.indexOf(barrier);
        if (index < 0) {
            throw new Error(`Unknown page-prep barrier: ${barrier}.`);
        }
        // Best-effort guard for synchronous calls only. Async tasks must use context.wait().
        if (runningTask && index >= runningTask.stage) {
            throw new Error(`Page-prep task ${runningTask.id} cannot wait for ${barrier}: it contributes to that barrier or a preceding one.`);
        }
        return deferred[index].promise;
    }

    function waitForStylesheet(link) {
        if (link.sheet) {
            return Promise.resolve();
        }
        return new Promise((resolve, reject) => {
            const cleanup = () => {
                link.removeEventListener('load', loaded);
                link.removeEventListener('error', failed);
                window.removeEventListener('load', loaded);
            };
            const loaded = () => {
                if (!link.sheet && document.readyState !== 'complete') {
                    return;
                }
                cleanup();
                link.sheet ? resolve() : reject(new Error(`Stylesheet failed: ${link.href}`));
            };
            const failed = () => { cleanup(); reject(new Error(`Stylesheet failed: ${link.href}`)); };
            link.addEventListener('load', loaded);
            link.addEventListener('error', failed);
            window.addEventListener('load', loaded, { once: true });
            loaded();
        });
    }

    function waitForImage(image) {
        const source = image.currentSrc || image.src;
        if (typeof image.decode === 'function') {
            return image.decode().catch(cause => { throw new Error(`Image failed: ${source}`, { cause }); });
        }
        if (image.complete) {
            return image.naturalWidth ? Promise.resolve() : Promise.reject(new Error(`Image failed: ${source}`));
        }
        return new Promise((resolve, reject) => {
            image.addEventListener('load', resolve, { once: true });
            image.addEventListener('error', () => reject(new Error(`Image failed: ${source}`)), { once: true });
        });
    }

    add({
        id: 'page-prep:dom', before: 'initialContentReady',
        promise: document.readyState === 'loading'
            ? new Promise(resolve => document.addEventListener('DOMContentLoaded', resolve, { once: true }))
            : Promise.resolve(),
    }, false);

    add({
        id: 'page-prep:resources', after: 'renderedContentReady', before: 'layoutResourcesReady',
        run: async () => {
            await Promise.all([
                Promise.all(Array.from(document.querySelectorAll('link[rel~="stylesheet"]'))
                    .filter(link => link.getAttribute('href')?.trim())
                    .map(waitForStylesheet)),
                Promise.all(Array.from(document.images).filter(image => image.getAttribute('loading') !== 'lazy').map(waitForImage)),
            ]);
            await document.fonts?.ready;
        },
    }, true);

    return {
        barriers,
        track(options) { return add(options, false).promise; },
        register(options) { add(options, true); },
        wait,
        closeRegistration,
    };
})();
