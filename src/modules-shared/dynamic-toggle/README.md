# dynamic-toggle

This module allows to specify CSS classes that can be toggled for the `<body>` DOM element. The user choice is persisted in local storage. The name "dynamic" means that toggling happens on client-side and not on server-side (e.g. via GET params).


## Usage

1. Configure this module e.g. like this:
    ```
    [
        'toggles' => [
            [
                'css_class' => 'sidebar-active',
                'on_by_default' => true,
            ],
            [
                'css_class' => 'reduced-nav',
                'on_by_default' => true,
            ]
        ]
    ]
    ```

2. Add some event listener that calls the `dtToggle` function e.g.
    ```
    document.querySelector('#some-button').addEventListener('click', event => { dtToggle('sidebar-active'); });
    ```