# Project info
- Prism
- https://prismjs.com/ and https://github.com/PrismJS/prism/
- License: MIT

# Configuration:
- Languages: Markup, CSS, C-like, JavaScript, TypeScript, Java, SQL
- Plugins: Line Numbers, File Highlight, Toolbar, Copy to Keyboard Button, Line Highlight
- Theme: Default (=Prism)
- PrismJS 1.30.0, downloaded on 2025-09-22
- https://prismjs.com/download.html#themes=prism&languages=markup+css+clike+javascript+typescript+java+sql&plugins=line-highlight+line-numbers+file-highlight+toolbar+copy-to-clipboard+download-button

Use `language-typescript` or `language-ts` on code blocks for TypeScript highlighting.

See the [demo page](../../../../demo-app/src/content/module-demos-ext/prism/index.php).

When page-prep is active, Prism automatic highlighting is disabled and one explicit pass runs after initialContentReady, so code inserted by initial content tasks is highlighted before resource checks. Pages without page-prep retain automatic highlighting.
