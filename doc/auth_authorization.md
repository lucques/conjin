### Authorization
Authorization / rights management works according to the following principles:

- There are users, groups and privileges
    - User <-> Group <-> Privilege
    - Users and groups share an m:n relationship.
    - Groups and privileges share an m:n relationship.
- The following auth configuration is implicit:
    - For every user there is a group belonging to that user (`UserBased` group).
    - There are two built-in users, specified by `rootUser` and `guestUser`, typically `"root"` and `"guest"`.
    - They are special in the following ways:
        - The `rootUser` has all privileges
        - Any privilege of `guestUser` is also given to any other user.
- Further details:
    - A group is either `Custom` or `UserBased`, with different namespaces
    - A privilege is
        - of a predefined set (currently only `Preprocess`) or
        - a target-specific action.
    - A target-specific action is of a predefined set (currently only `View` -- view the target) or a custom action.
    - The `guestUser` is used when no other user is logged in, and it cannot login itself
