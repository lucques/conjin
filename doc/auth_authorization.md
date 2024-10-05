# Authorization
Authorization / rights management works according to the following principles:

- There are **users**, **groups** and **privileges**
    - User <-> Group  (many-to-many relationship)
    - Group <-> Privilege (many-to-many relationship)
    - An **actor** is either a user or a group
- **OpenID providers** can be combined with groups
    - OpenIDProvider <-> Group (many-to-many relationship)
- The following auth configuration is implicit:
    - There are two built-in users, specified by config `staticRootUser` and `staticGuestUser`, typically named "root" and "guest".
    - They are special in the following ways:
        1. **`staticRootUser`**: Has all privileges.
        2. **`staticGuestUser`**: Any privilege of `staticGuestUser` is also given to any other user.
- Further details:
    - A **privilege** is
        - of a predefined set (currently only `Debug`, `Preprocess`, `LoginLogout`) or
        - a target action.
        - TODO: `LoginLogout` is not yet implemented.
    - A **target action** is of a predefined set (currently only `View` -- view the target) or a **custom target action**.
    - Privileges can be given in two ways:
        1. Assign a privilege to an actor directly (i.e. either user or group)
        2. Only for targets:
            - **target rules** allow to allow/deny target actions for actors, thereby allowing hierarchical rights management: Rules are inherited down the target tree.
    - The `staticGuestUser` is used when no other user is logged in, and it cannot be logged in to