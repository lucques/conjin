///////////
// Hooks //
///////////

// Global variables

mathJaxAfterRenderingHooks = [];


// Use these public interfaces to place your hooks

function addMathJaxAfterRenderingHook(func) {
    mathJaxAfterRenderingHooks.push(func);
}


/////////////////
// Main config //
/////////////////

MathJax = {
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
                    const spacing = parser.GetBrackets('\\begin{' + begin.getName() + '}');
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

            // Call user-defined ready functions
            for (const func of mathJaxAfterRenderingHooks) {
                MathJax.startup.promise.then(func);
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