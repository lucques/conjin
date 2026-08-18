# Authentication

## User identity

A user identity is the stable, minimal representation of a user. User identities
are defined by the `User` type in `composer-projects/conjin/deployment/dhall/types.dhall` and appear in the generated
JSON and the PHP authorization code as associative arrays.

There are two variants:

- A static identity contains its configured user name. The configured `guest`
  and `root` users are static identities.
- An OpenID identity contains the OpenID provider name and the provider-specific
  user ID. Both values are needed because an ID is only unique within its
  provider.

The corresponding PHP representations are:

```php
[
    'tag' => 'Static',
    'contents' => 'username'
]
```

and:

```php
[
    'tag' => 'OpenId',
    'contents' => [
        'providerName' => 'provider',
        'id' => 'user-id'
    ]
]
```

User identities are used in configuration, explicit group assignments,
authorization comparisons, and serialization. They do not contain data loaded
during authentication.


## Resolved user

A resolved user represents the current user at runtime. It implements the
`ResolvedUser` interface and is stored in `$GLOBALS['user']` and, for session
authentication, in `$_SESSION['user']`. `auth_get_user()` therefore returns a
`ResolvedUser`, not a user-identity array.

There are two readonly implementations:

- `StaticResolvedUser` contains the public `name` attribute.
- `OpenIdResolvedUser` contains the public `providerName`, `id`, `email`, and
  `markers` attributes. `email` is nullable, and `markers` is a list of
  `OpenIdMarker` objects. Each marker contains an `attributeName` and a
  `markerName`.

The email address and markers are loaded from the configured OpenID claims when
authentication succeeds. Markers can grant dynamic group membership; the email
address is available to application code such as the issue tracker. Static
resolved users have neither OpenID markers nor an email attribute.

Every resolved user can be reduced to its stable identity:

```php
$identity = auth_get_user()->get_user_identity();
```

For an OpenID resolved user, this deliberately removes `email` and `markers`.
Consequently, changing claim data does not change the user's identity, and a
resolved user can be compared with the Dhall-defined identities used by the
authorization system.


## User names

User names / ids must not contain whitespace.


## Authentication order

There are many ways to authenticate. The following order is used.

1. **Implicit authentication**.
On each request, a call to `auth_init()` takes place. Afterwards, `$GLOBALS['user']` is set.
    1. **By trusted local deployment request**.
        - Local and isolated-test preprocessing and link-checking jobs contact the unexposed Apache port 8080. When Apache marks the request as internal and the deployment enables internal authentication, the `user` cookie is accepted without a password after verifying that it names a configured static user.
        - This mechanism is not enabled for normal port-80 requests or remote deployments. See [Local deployment](./deployment_local.md#internal-job-authentication).
    2. **By password cookie**.
        - If a `password` cookie is sent, the password is checked against all configured static users.
        - Password correct   => User is the matching static user
        - Password incorrect => User is `guest`
        - TODO: Currently only password is checked. Username+password should also be supported.
    3. **Resume session**.
        - Former session may be resumed. The resolved user is retrieved from `$_SESSION['user']`.
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


## Login profiles

Applications define their login profiles through a `$profiles` map and select one of them through `$default_profile_id` in `system/login.php`. The map associates every profile ID with a preprocessing callback. Every callback receives an independent `SysletPreprocessContext`, so profiles may use different templates, modules, and module configurations while sharing the framework authentication handlers.

```php
$profiles = [
    'default' => function (SysletPreprocessContext $c) {
        $c->activate_template('template-generic');
    },
    'standalone' => function (SysletPreprocessContext $c) {
        $c->activate_template('template-navigable');
    },
];

$default_profile_id = 'default';
```

Both variables are required, and `$default_profile_id` must identify an entry in `$profiles`. The profile ID `default` has no reserved meaning and may be replaced by any valid profile ID. The former single `$preprocess` callback form is not supported.

During preprocessing, every map entry becomes a `LoginProfile` object. `LoginProfile` extends `Syslet` with its immutable `$id`, while retaining the activated modules and template. The resulting map and selected default ID are stored as the `login_profiles` and `default_login_profile_id` preprocessing objects. Login render callbacks receive the selected `LoginProfile` and can access its ID as `$login_profile->id`; the profile ID is not kept in global state.

Targets select a profile during preprocessing. The selection is inherited by descendants, and targets without a selection use the profile identified by `$default_profile_id`.

```php
$c->set_login_profile('standalone');
```

When an anonymous user requests a protected target, Conjin redirects to `/login/<profile>/?redirect=...`. `/login/` selects the configured default profile independently of its ID. Profile IDs may contain lowercase ASCII letters, digits, underscores, and hyphens.

Login pages and OpenID Connect protocol endpoints use separate URL namespaces. `/auth/oidc/<provider>/start` initiates authentication and `/auth/oidc/<provider>/callback` receives the provider response. The callback URL must be registered exactly as an allowed redirect URI at the provider. Conjin stores the selected login profile and destination in the session for the duration of the authentication attempt.
