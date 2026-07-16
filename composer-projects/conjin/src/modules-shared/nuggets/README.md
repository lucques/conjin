# nuggets

## Terminology
- **Nugget**: A nugget is a piece of content that can be embedded in multiple places.
    - Really a nugget can be a tree of nuggets.
        - **Root nugget**: The top-level nugget in a tree of nuggets.
            - A root nugget is a dir that contains a `nugget.php` file.
        - **Leaf nugget**:
            - One that does not contain any other nuggets but is actually contentful.
            - Represented by one of these classes:
                - `LeafNugget`
                - `ExerciseNugget`
                - `DefinitionNugget`
                - `ExampleNugget`
            - Each object is provided by a config (assoc array)
                - This config is usually passed to every print function
    - Optionally, there is a `res` dir that contains its resources
- **Nugget pool**:


## Raw nugget
- A **raw nugget** just contain just raw content.


## Acc nuget
- An **acc nugget** represents an accordion, consisting of a list of **acc parts**.
- An **acc part** contains a title and a variant


## Exercise nugget
- An **exercise nugget** represents an exercise. Contains:
    - Main **exercise item**
    - List of **exercise parts**
- **exercise part** is one of:
    - **exercise explanation**: Text in between exercise items
    - **exercise item**: Sub-exercise, contains:
        - Content
        - List of **exercise hint**
        - List of **exercise sol**


## TODO

- Nugget pools currently require an `index.php` so Conjin recognizes and preprocesses them as targets. Remove this requirement so a pool can consist solely of its `inc/` directory without becoming an otherwise empty renderable target.
