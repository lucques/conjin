# Authorization
Authorization / rights management works according to the following principles:

- There are **users**, **groups** and **privileges**
    - User <-> Group <-> Privilege
    - Users and groups share an m:n relationship.
    - Groups and privileges share an m:n relationship.
    - An **actor** is either a user or a group
- The following auth configuration is implicit:
    - There are two built-in users, specified by config `rootUser` and `guestUser`, typically named `"root"` and `"guest"`.
    - They are special in the following ways:
        1. **`rootUser`**: Has all privileges.
        2. **`guestUser`**: Any privilege of `guestUser` is also given to any other user.
- Further details:
    - A **privilege** is
        - of a predefined set (currently only `Debug`, `Preprocess`, `LoginLogout`) or
        - a target action.
    - A **target action** is of a predefined set (currently only `View` -- view the target) or a **custom target action**.
    - Privileges can be given in two ways:
        1. Assign a privilege to an actor directly (i.e. either user or group)
        2. Use hierarchic allow/deny rules to assign target actions to actors
    - The `guestUser` is used when no other user is logged in, and it cannot be logged in to