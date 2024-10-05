# Authentication

## User
There are two types of users:
- Static users. They are defined statically in the Dhall config.
    - Contain "guest" and "root" users.
- OpenID users. They belong to an OpenID provider.


## User names
User names / ids must not contain whitespace.


## Authentication order
There are many ways to authenticate. The following order is used.

1. **Implicit authentication**.
On each request, a call to `auth_init()` takes place. Afterwards, `$GLOBALS['user']` is set.
    1. **By cookie**.
        - If a cookie `user` and `password` is sent, then for the current request that user/password combination is used for login.
        - User/password is correct       => User is as specified
        - User/password is incorrect     => User is `guest`
        - TODO: Currently only passowrd is checked. Should be username+password.
    2. **Resume session**.
        - Former session may be resumed. User is retrieved in `$_SESSION['user']`
        - `$_SESSION['user']` is set     => User is as specified (resume session)
        - `$_SESSION['user']` is not set => User is `guest`
2. **Explicit authentication**.
If not logged in yet ("guest" user) and via "login" page or something similar, the following is tried.
    - **Login with static user by form**.
        - A password is checked against the static users (password hashes).
        - TODO: There should be two variants: Login by username+password or by just password. Not implemented yet.
    - **Login with OpenID**.
        - User is redirected to OpenID provider, then redirected back to the site.


## Static users
Passwords can be configured in plaintext or as hashes. Plaintext passwords should not contain special characters.