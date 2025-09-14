///////////
// Hooks //
///////////


/////////////////
// Main config //
/////////////////

window.MathJax = {
    startup: {
        ready() {

            ////////////////////////////
            // `tightarray` extension //
            ////////////////////////////

            const {Configuration} = MathJax._.input.tex.Configuration;

            const {EnvironmentMap} = MathJax._.input.tex.SymbolMap;
            const ParseUtil = MathJax._.input.tex.ParseUtil.default;

            const ParseMethods = MathJax._.input.tex.ParseMethods.default;
            const BaseMethods = MathJax._.input.tex.base.BaseMethods.default;
            
            //  Define an environment map to add the new tightarray environment
            new EnvironmentMap('my-tightarray', ParseMethods.environment, {
                tightarray:   ['TightArray'],
            }, {
                // Create a usual array, but with optional inter-column spacing specified,
                // and optional vertical placement (as with the array environment).
                TightArray(parser, begin) {
                    const spacing = parser.GetBrackets('\\begin{' + begin.getName() + '}') || '0em';
                    const align = parser.GetBrackets('\\begin{' + begin.getName() + '}');
                    const item = BaseMethods.Array(parser, begin, null, null, null, spacing);
                    return ParseUtil.setArrayAlign(item, align);
                }
            });
            
            // Define the package for our new environment
            Configuration.create('my-tightarray', {
                handler: {
                    environment: ['my-tightarray'],
                }
            }); 


            ////////////////
            // Add macros //
            ////////////////

            // --- Before rendering ("MathJax is loaded, but not yet initialized") ---

            MathJax.startup.defaultReady();

            // --- After rendering ("MathJax is initialized, and the initial typeset is queued") ---

            // Call user-defined functions (hooks)
            if (typeof mathJaxAfterRenderingHooks !== 'undefined') {
                for (const func of mathJaxAfterRenderingHooks) {
                    MathJax.startup.promise.then(func);
                }
            }
        }
    },
    loader: {load: ['[tex]/color',
                    '[tex]/unicode']},
    tex: {
        inlineMath: [['$', '$']],
        displayMath: [['$$','$$']],
        packages: {'[+]': [
            'color',
            'unicode',
            'my-tightarray',  // Tell TeX to use our package
        ]},
    }
};


///////////
// Utils //
///////////

// Replace decimal point with comma in a number for math mode
// Replace trailing zeros by phantom zeros to ensure the same width for all numbers
// If there is a trailing `,`, replace it by a phantom comma
function mj_num(x, decimalPlaces=2) {
    const strFixed = Number(x).toFixed(decimalPlaces);

    // If ends with . and then trailing zeros, replace it by phantom chars
    if (strFixed.endsWith('.' + '0'.repeat(decimalPlaces))) {
        return strFixed.substring(0, strFixed.indexOf('.')) + '{\\phantom{,}}' + '{\\phantom{0}}'.repeat(decimalPlaces);
    }
    else {
        const strNoTrailZeros = strFixed.replace(/0+$/, match => '{\\phantom{0}}'.repeat(match.length));
        const strCommaFixed = strNoTrailZeros.replace(/\.$/, '{\\phantom{.}}');
        return strCommaFixed.replace('.', '{,}');
    }
}

// If the number is negative, put it in parentheses
function mj_num_parens(x, decimalPlaces=2) {
    if (x >= 0) {
        return mj_num(x, decimalPlaces);
    }
    else {
        return '(' + mj_num(x, decimalPlaces) + ')';
    }
}
