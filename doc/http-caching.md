# HTTP caching

Conjin's request entrypoint defaults to `Cache-Control: no-store`. This covers rendered HTML, login and OIDC flows, errors and other dynamic responses. All framework redirect helpers explicitly send `no-store` and remove `Last-Modified`, including permanent content redirects; redirect destinations are not cached by default.

HTML has no cache validator. A target's `index.php` modification time cannot describe changes to templates, modules, nuggets or dynamic state, so the former HTML timestamp check has been removed. HTML ETags are not implemented.

Public module resources use `Cache-Control: public, no-cache`. Content resources use `Cache-Control: private, no-cache`, after target authorization, successful file resolution and resource module callbacks. Both policies allow storage but require validation before reuse. Content resources deliberately remain private even for guest-accessible targets. A resource callback's explicit `no-store` policy is preserved and suppresses conditional-cache handling.

Cacheable resource responses send `Last-Modified` on the initial 200 response as well as on 304 responses. GET and HEAD requests with a matching or newer valid `If-Modified-Since` receive a bodyless 304; stale or invalid dates receive 200. HEAD has no body. Other resource methods receive 405 with `Allow: GET, HEAD` and `no-store`. If `If-None-Match` is present, the date condition is ignored; resources currently do not issue ETags. Static-file validators describe the resource file, not unrelated rendering dependencies.

Session startup no longer emits PHP's automatic cache headers. Cache policy therefore does not depend on whether a guest has a session cookie or a form starts a session during rendering. Protected resource authorization always precedes conditional validation, so an unauthorized request cannot obtain a 304 instead of an authorization response.
