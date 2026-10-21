# Preprocessing phase

**Preprocessing** is the traversal of the whole content tree, collecting meta data, executing target-specific modules and caching data for ordinary processing.

It is triggered explicitly by calling `/preprocess/`.

When a context first activates a module, Conjin loads its `preprocessing.php` and calls only the optional `$preprocess_target(Module $m, TargetPreprocessContext $c)` or `$preprocess_syslet(Module $m, SysletPreprocessContext $c)` entrypoint for that context. There is no separate common hook; see [module entrypoints and local code sharing](modules.md#entrypoints-and-local-code-sharing). Dependencies activated by the entrypoint are recorded before the module itself, preserving dependency order for processing.

It stores serialized objects in the cache:
- `groups_2_userlist_ser`: Reverse dictionary to map user names to groups (needed for authorization)
- `target_root`: The full navigable target tree and root processing data; other processing data can be stored in chunks under `obj/target-chunks/<target ID path>/chunk`
- `syslet_*`: The syslet objs

There are two types of processables that are preprocessed, for each there is one function.


## `preprocess_syslet: ($which: string) -> Syslet`

Less advanced than `preprocess_target_root`. See also next section.


## `preprocess_target_root: () -> Target`

The resulting `Target` object contains the full content hierarchy. Each node retains its ID, parent, ordered children, content location, login profile, and authorization data in `target_root`. The deployment setting `depl.targetSplitDepth` controls how activated modules and template selection are split into lazy chunks: `0` keeps all data in `target_root`; `1` creates a chunk per root child containing its branch; `2` also creates a chunk per grandchild, and so on. The saved tree records chunk locations, so preprocessing must be rerun after changing the setting.

The privileged debug page lists `target_root` and paths such as `target-chunks/ID1/chunk` in its Preprocess objects table. Each entry opens its own JSON view; viewing `target_root` leaves its chunks unloaded.

The whole `content` directory tree is traversed top-down according to `add_subpage` calls. For each target (represented by its own dir), there is one call to the **recursive** `preprocess_all_rec: PreprocessingContext -> Target` function:
1. Start with a fresh `PreprocessingContext` obj.
    - Status: `CONSTRUCTED`.
2. Retrieve the `preprocess SysletPreprocessContext -> ()` function from either
    1. `index.php` or
    2. `system/target_default.php` (if not defined in the target)
    Execute it.
    - Gets provided with the `PreprocessingContext` obj which offers:
        - Add preprocessors via `add_preprocessor`
        - Add sub-targets via `add_subpage`
        - Activate modules via `activate_module`
            - Gets executed immediately which means the dependency mechanism is already resolved by spanning a DAG of modules
        - Activate template via `activate_template`
        - Run preprocessing macros via `run_macro`
    - Status: `PASSED_THROUGH`
3. Recurse on children (create child-context and run `preprocess`)
    - Status: `RECURSED`
4. For each `Preprocessor` obj:
    - Execute its `finish: dict<string, PreprocessingContext> -> ()` function which gets provided the child-contexts (which are already `DONE`).
    - Status: `FINISHED_PREPROCESSORS`
5. Produce `Target` obj and fill `parent` obj of children
    - Status: `DONE`

## Output publication

Preprocessing regenerates the entire `conjin-dyn-preprocessing` directory in a fresh `conjin-dyn-preprocessing-new` sibling. Custom configured paths derive staging and previous-output names by appending `-new` and `-old`. Upload neither synchronizes nor deletes these directories. Before writing generated objects, preprocessing installs `.htaccess` protection denying direct web access. Generated resources continue to be served through the authorized resource handler.

Directory creation and file-write failures abort generation. Existing output stays active while generation runs. On failure, preprocessing attempts to remove staging and reports cleanup failures. Stale staging is discarded at the start of the next run. PHP errors and explicit framework failure responses also attempt staging cleanup; forcefully killed processes may leave staging behind.

Once generation succeeds, preprocessing removes any leftover `-old`; failure here aborts without touching current output. It then renames current output to `-old` and staging to current. If the second rename fails, it attempts rollback and reports whether restoration failed. First deployment publishes staging with a single rename. Successful publication immediately removes `-old`; a cleanup failure leaves new output active and produces a cleanup warning in the successful response and error log.

The two renames are intentionally not atomic. A brief availability gap and failures in requests spanning publication are accepted. If PHP dies between renames, manual repair may be needed: inspect the directories and restore `-old` to the configured current path when current is missing. Do not promote incomplete `-new` output. There is no automatic interrupted-swap recovery or concurrency lock; operators must serialize upload/preprocessing runs. Code/content uploads and generated output are not a single transaction, so old output can be incompatible with uploaded source after generation fails.

Local/test Docker mounts use a stable parent at `/files/conjin-preprocessing-runtime` so the current, staging, and previous directories are renameable children. The host preprocessing volume path and named test volume remain unchanged. Remote PHP requires a writable common parent directory.
