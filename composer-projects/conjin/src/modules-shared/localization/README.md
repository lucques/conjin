# localization

This module provides localization infrastructure for multilingual Conjin websites. It allows tagging a target with its language, linking other language versions of the same content, rendering language controls, and optionally remembering a language selected through such a control.

## Configuration

`languages` defines the available language tags, their labels, and their display order. `rememberExplicitChoice` controls whether language-control links can remember the selected language in a cookie.

```php
[
    'languages' => [
        [
            'languageTag' => 'en',
            'label' => 'English',
        ],
        [
            'languageTag' => 'de',
            'label' => 'Deutsch',
        ],
    ],
    'rememberExplicitChoice' => true,
]
```

Each language used by `set_language`, `add_alternative`, or `add_nontranslation` must be present in `languages`. `language_tag_to_text()` returns the configured label. When no explicit preferred order is passed to `get_language_alternatives_and_nontranslations()`, the order from `languages` is used.

The current page's language, the root-page strategy, and all target associations remain target-specific and are not inferred from this configuration.

## Targets and translations

A target can serve as an **alternative** or a **nontranslation**:

- **alternative**: Contains actual translated content.
- **nontranslation**: Tells the user that translated content is unavailable.

Alternatives are added to the document head and alternatives and nontranslations appear in the visible language switcher. A separately configured language-switch target is added to the document head as `hreflang="x-default"` but does not appear in the visible switcher.

### How to mark a target with a language

Use `true` if the page is an alternative and `false` if it is a nontranslation.

```php
$c->run_macro('localization', 'set_language', 'de', true);
```

### How to add another language version of the same content

```php
$c->run_macro('localization', 'add_alternative', 'de', ['first', 'second']);
```

### How to add a nontranslation

```php
$c->run_macro('localization', 'add_nontranslation', 'de', ['first', 'second']);
```

## Language-neutral switch target

Use `set_language_switch` when a group of localized pages has a language-neutral entry URL, such as a language-selection page or a root page that temporarily redirects according to a remembered preference. Pass its target IDs and configure the same target on every localized variant in the group. An empty target-ID list denotes the application root.

```php
$c->run_macro('localization', 'set_language_switch', []);
```

This adds the following metadata without treating `x-default` as a configured language or exposing it in language controls:

```html
<link rel="alternate" hreflang="x-default" href="https://example.com/" />
```

The module generates fully qualified URLs for `x-default` and ordinary language alternatives using the deployment's configured protocol, host, and URL base. Configure `set_language_switch` only where the designated URL is an appropriate fallback for that particular group of localized pages; do not point unrelated page groups to a generic homepage.

## Remembering an explicit choice

Use `localization_get_language_choice_url()` for links that represent an explicit user language choice:

```php
<a href="<?= localization_get_language_choice_url($target_ids) ?>">
    <?= language_tag_to_text($language_tag) ?>
</a>
```

When `rememberExplicitChoice` is enabled, the helper adds a `localization-explicit-choice=1` query marker. The destination target handles that marker during module initialization, stores its own configured language in the `conjin_language` cookie for one year, and responds with a temporary redirect to the same URL without the marker. The response is marked `Cache-Control: no-store`. When remembering is disabled, the helper returns an ordinary target URL.

The cookie is restricted to the application's URL base, uses `SameSite=Lax`, uses `Secure` for HTTPS deployments, and is HTTP-only. Read it through `localization_get_remembered_language_tag()`, which returns `null` when remembering is disabled, the cookie is absent, or its value is not configured in `languages`. A project's root target can use this value to implement its own redirect or suggestion strategy.

## Reading the browser language preference

Use `localization_get_accepted_language_tag()` to negotiate the request's `Accept-Language` header against the configured `languages`. It returns the matching configured language tag, preserving its configured spelling, or `null` when the header is absent or contains no valid acceptable match. Malformed header entries are ignored. Matching is case-insensitive, observes quality values and `q=0` exclusions, supports `*`, and falls back from a more specific requested tag such as `de-DE` to a configured `de`. More-specific ranges override broader ranges, header order breaks otherwise equal client preferences, and configured language order is the final tie-breaker.

An entry target can combine the browser preference with a remembered explicit choice and an application default:

```php
$language_tag =
    localization_get_remembered_language_tag()
    ?? localization_get_accepted_language_tag()
    ?? 'en';

localization_add_vary_header(cookie: true, accept_language: true);
```

`localization_add_vary_header()` marks whether the response used the language cookie, the `Accept-Language` request header, or both. It preserves fields already present in the response's `Vary` header. This cache metadata is important when a cache can store the resulting response or redirect. Browser preference is only a hint and should not override an explicit remembered choice.

The destination must be a normal target that activates `localization`. Conjin handles static redirect targets before target modules are initialized, so such targets cannot record a choice.

### Conditional-cache limitation

Conjin performs its `If-Modified-Since` check before initializing target modules. If a client sends a conditional request for a language-choice URL and Conjin returns `304 Not Modified`, localization initialization is skipped and the cookie cannot be written. In normal browser use, the marker URL is a distinct URL and its first successful response redirects with `Cache-Control: no-store`, so the browser should not cache or later revalidate that response. This makes the limitation unlikely to surface in practice, but the current framework lifecycle cannot guarantee that every choice request reaches the module. A future general-purpose module request hook would have to run before the conditional-cache check to remove this limitation completely.

## Examples

See the [demo pages](../../../../demo-app/src/content/module-demos/localization/index.php).
