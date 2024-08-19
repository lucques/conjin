# Preprocessing phase

**Preprocessing** is the traversal of the whole content tree, collecting meta data, executing target-specific modules and caching data for ordinary processing.

It is triggered explicitly by calling `/preprocess/`.

It stores serialized objects in the cache:
- `groups_2_userlist`: Reverse dictionary to map user names to groups (needed for authorization)
- `target_root`: The root target obj which is a tree containing all targets
- `syslet_*`: The syslet objs

There are two types of processables that are preprocessed, for each there is one function.


## `preprocess_syslet: ($which: string) -> Syslet`

Less advanced than `preprocess_target_root`. See also next section.


## `preprocess_target_root: () -> Target`

The resulting `Target` obj is a tree representation of the whole content, with each node containing meta data such as title, activated modules and more.

The whole `content` directory tree is traversed top-down according to `add_subpage` calls. For each target file (i.e. `index.php`), there is one call to the **recursive** `preprocess_all_rec: PreprocessingContext -> Target` function:
1. Start with a fresh `PreprocessingContext` obj.
    - Status: `CONSTRUCTED`.
3. Include the `index.php` and retrieve the `$preprocess SysletPreprocessContext -> ()` function. Execute it.
    - Gets provided with the `PreprocessingContext` obj which offers:
        - Add preprocessors via `add_preprocessor`
        - Add sub-targets via `add_subpage`
        - Activate modules via `activate_module`
            - Gets executed immediately which means the dependency mechanism is already resolved by spanning a DAG of modules
        - Activate template via `activate_template`
        - Run preprocessing macros via `run_macro`
    - Status: `PASSED_THROUGH`
5. Recurse on children (create child-context and run `preprocess`)
    - Status: `RECURSED`
6. For each `Preprocessor` obj:
    - Execute its `finish: dict<string, PreprocessingContext> -> ()` function which gets provided the child-contexts (which are already `DONE`).
    - Status: `FINISHED_PREPROCESSORS`
7. Produce `Target` obj and fill `parent` obj of children
    - Status: `DONE`