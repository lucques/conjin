# localization

This module allows tagging a target by its language and provide links to other language versions of the same content. Also there are template snippets to enable language switching in the navigation.


## Usage

A target can serve as a **alternative** or a **nontranslation**:
- **alternative**: Contains actual content
- **nontranslation**: Contains no content, but tells the user about this etc.

Only *alternatives* should land in the head section. *Nontranslations* should appear in the language switcher though.


### How to mark a target with a language
Use `true` if the page is an alternative, `false` for nontranslation
```
$c->run_macro('localization', 'set_language', 'de', true);
```


### How to add a link to another language version of the same content:
```
$c->run_macro('localization', 'add_alternative', 'de', ['first', 'second']);
```


### How to add a link to a nontranslation:
```
$c->run_macro('localization', 'add_nontranslation', 'de', ['first', 'second']);