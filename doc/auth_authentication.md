# Authentication
On each request, authentication takes place (call to `auth_init()`). Afterwards, the `$GLOBALS['user']` contains the current user.

There are different methods to authenticate, they are tried in the following order:
1. Auth is disabled.
    - `$_GET['user']` is set         => User is as specified
    - `$_GET['user']` is not set     => User is `root`
2. Cookie. If a cookie `user` and `password` is sent, then for the current request that user/password combination is used for login.
    - User/password is correct       => User is as specified
    - User/password is incorrect     => User is `guest`
3. Login session. Gets stored in `$_SESSION['user']`
    - `$_SESSION['user']` is set     => User is as specified (resume session)
    - `$_SESSION['user']` is not set => User is `guest`
