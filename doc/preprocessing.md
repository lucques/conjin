# Preprocessing
Preprocessing means to traverse the whole content tree, collecting meta data and caching it for ordinary processing.

It is triggered explicitly by calling `/preprocess/`.

In PHP, the `preprocess_all: () -> Target` function in `preprocess_mech.php` handles the request. The resulting `Target` obj is a tree represenation of the whole content, with each node containing meta data such as title, activated modules and more. This obj is then saved to the cache.

The whole `content` directory tree is traversed top-down according to `add_subpage` calls. For each target file (i.e. `index.php`), there is one call to the **recursive** `preprocess: PreprocessingContext -> Target` function:
1. Start with a fresh `PreprocessingContext` obj.
    - Status: `CONSTRUCTED`.
2. Then inherited modules get initialized and the `Preprocessor` objs get spawned via `$create_preprocessor: ProcessingContext -> Preprocessor`.
    - Status: `INITIALIZED_INHERITED`
3. Include the `index.php` and retrieve the `$preprocess: PreprocessContext -> ()` function. Execute it.
    - Gets provided with the `PreprocessingContext` obj which offers:
        - Provides attributes to be set (e.g. `title`)
        - Preprocessor's functions can be called from within `index.php`
        - Preprocessing modules can be activated, potentially causing the spawning of `Preprocessor` objs on-the-fly
        - Processing modules can be activated
        - If a template / preproc module / proc module gets inherited, then by default it gets inherited *monotonously all the way down*
    - Afterwards, activate proc module dependencies of template
    - Status: `PASSED_THROUGH`
4. Initialize template module.
    - Status: `INITIALIZED_TEMPLATE`
5. Recurse on children (create child-context and run `preprocess`)
6. For each `Preprocessor` obj:
    - Execute its `finish: dict<string, PreprocessingContext> -> ()` function which gets provided the child-contexts.
    - Status: `FINISHED_PREPROCESSORS`
7. Eventually, there is a `Target` obj produced that contains all the content meta data.