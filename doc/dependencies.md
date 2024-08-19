# Dependencies
- Docker
    - Version: >= 26
- Password handling: `secret-tool` is recommended
    

Some design decisions are taken as follows.
- Publishing new content should be extremely easy: Create new dir. Create `index.php`. Run `/preprocess/`. Done. No build steps.
- PHP
    - Advantages:
        - Easy deployment
        - Fast development cycle, no need to recompile, just reload the page 
        - Templating is built-in, everything is directed at producing output
        - Easy to write reusable macros
        - Well-established and large standard library
        - Good IDE support
        - In the future: Use additional external packages from Packagist
    - Disadvantages:
        - Not as modular as other languages. When including content files (`index.php`), template files (e.g. `header.php`) or module files (`module.php`), the scoping mechanism is not very elegant -- at these few points things have been crafted with great care.
- HTML & CSS
    - Complete control on page layout and styles
    - May also switch to Markdown on some sections and then generate HTML in the future
    - Good IDE support
    - Bootstrap CSS framework
- Docker
    - Easy deployment of orchestrated containers via docker-compose
- Dhall configuration language
    - Main selling point: Configuration files should be programmable (Mainly: DRY principle through the use of functions).
    - Declarative and statically typed: It is hard to misconfigure, good error messages
    - Possible to turn off static types for some sections by using the diret `JSON` embedding mechanism
    - Comments:
        - Disadvantage: Not well-established.
        - I wanted to try out the programmable-configuration approach to see how it works and I quite like Dhall although it is very verbose (type inference is given up to favor simplicity). Alternative to look into: CUE language
- LinkChecker
    - Find errors: Rendering failure, broken links, missing images etc. 
    - Comments:
        - Disadvantage: Slow, although multi-threading is used