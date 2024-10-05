///////////
// Types //
///////////

class ValueTable {
    constructor(divId, vars, hasDescPerPoint, hasDescPerVarAndExp, pointExtension, expExtension) {
        this.divId = divId;
        this.vars = vars;
        this.hasDescPerPoint = hasDescPerPoint;
        this.hasDescPerVarAndExp = hasDescPerVarAndExp;
        this.pointExtension = pointExtension;
        this.expExtension = expExtension;

        let table = document.createElement('table');
        table.id = this.#makeValueTableId();
        table.classList.add('table');
        table.classList.add('m-0');
        table.classList.add('border-dark')
    
        let thead = document.createElement('thead');
    

        ////////////////
        // Handle row //
        ////////////////

        let handleTr = document.createElement('tr');
        handleTr.id = this.#makeHandleRowId();

        // Empty cells

        // First one
        let emptyTd = document.createElement('td');
        emptyTd.style.borderWidth = '0px';
        handleTr.appendChild(emptyTd);

        // The rest
        const numberOfEmptyCells = (this.hasDescPerPoint ? 1 : 0) + this.vars.length;
        for (let i = 0; i < numberOfEmptyCells; i++) {
            let td = document.createElement('td');
            handleTr.appendChild(td);
        }

        thead.appendChild(handleTr);


        /////////////////////
        // Description row //
        /////////////////////

        if (this.hasDescPerVarAndExp) {
            let descTr = document.createElement('tr');
            descTr.id = this.#makeDescRowId();
            descTr.classList.add('border-end');

            // Empty cell
            let td = document.createElement('td');
            td.style.borderWidth = '0px';
            td.classList.add('border-end');
            descTr.appendChild(td);

            // Description for points
            if (this.hasDescPerPoint) {
                let th = document.createElement('th');
                th.classList.add('text-center');
                th.classList.add('border-end');

                descTr.appendChild(th);
            }

            // Description for vars
            for (let i = 0; i < this.vars.length; i++) { 
                const v = this.vars[i];

                let th = document.createElement('th');
                th.innerText = v.desc;
                th.classList.add('text-center');

                // Add thick border to the right of last var col
                if (i === this.vars.length - 1) {
                    th.style.borderRight = '2px solid black';
                }
                else {
                    th.classList.add('border-end');
                }

                descTr.appendChild(th);
            }

            thead.appendChild(descTr);
        }

    
        /////////////////
        // Heading row //
        /////////////////

        let headingTr = document.createElement('tr');
        headingTr.id = this.#makeHeadingRowId();
        headingTr.classList.add('border-end');


        // Empty cell
        let td = document.createElement('td');
        td.style.borderWidth = '0px';
        td.classList.add('border-end');
        headingTr.appendChild(td);

        // Empty cell for description col
        if (this.hasDescPerPoint) {
            let td = document.createElement('td');
            td.classList.add('border-end');
            td.style.borderBottom = '2px solid black';
            headingTr.appendChild(td);
        }

        // Vars
        for (let i = 0; i < this.vars.length; i++) { 
            const v = this.vars[i];

            let td = document.createElement('td');
            td.innerText = '$' + v.name + '$';
            td.classList.add('text-center');
            td.style.borderBottom = '2px solid black';
            td.style.verticalAlign = 'bottom';

            // Add thick border to the right of last var col
            if (i === this.vars.length - 1) {
                td.style.borderRight = '2px solid black';
            }
            else {
                td.classList.add('border-end');
            }

            headingTr.appendChild(td);
        }
    
        thead.appendChild(headingTr);
        table.appendChild(thead);
    
        let tbody = document.createElement('tbody');
        table.appendChild(tbody);
    
        let divWrapper = document.getElementById(divId);
        divWrapper.classList.add('d-grid');
        divWrapper.style.gridTemplateColumns = '1fr min-content';
        divWrapper.style.justifyItems = 'center';
        divWrapper.style.alignItems = 'center';

        divWrapper.appendChild(table);


        /////////////////////////
        // "extend-exp" button //
        /////////////////////////

        if (this.expExtension.enabled) {
            let extendExpButton = document.createElement('button');
            extendExpButton.innerHTML = '<i class="bi-plus-circle"></i>';
            extendExpButton.classList.add('btn');
            extendExpButton.classList.add('btn-link');
            extendExpButton.onclick = () => { this.addExp(this.expExtension.defaultRawExp, this.expExtension.defaultEditMode, this.expExtension.defaultInitiallyInvisible, this.expExtension.defaultDesc); this.rerender(); };
            divWrapper.appendChild(extendExpButton);
        }


        ///////////////////////////
        // "extend-point" button //
        ///////////////////////////

        if (this.pointExtension.enabled) {
            let extendPointButton = document.createElement('button');
            extendPointButton.innerHTML = '<i class="bi-plus-circle"></i>';
            extendPointButton.classList.add('btn');
            extendPointButton.classList.add('btn-link');
            extendPointButton.onclick = () => { this.addPoint(this.pointExtension.defaultAssignment, this.pointExtension.defaultEditMode, this.pointExtension.defaultInitiallyInvisible, this.pointExtension.defaultDesc); this.rerender(); };
            divWrapper.appendChild(extendPointButton);
        }
    }


    //////////////////
    // Change Table //
    //////////////////

    // editMode: 'always', 'clickable', 'never'

    addPoint(assignment, editMode, initiallyInvisible, desc) {
        const tbody = document.getElementById(this.#makeValueTableId()).getElementsByTagName('tbody')[0];

        const pointId = tbody.children.length;
    
        let tr = document.createElement('tr');
        tr.id = this.#makePointId(pointId);
        tr.dataset.pointId = pointId;
        tr.dataset.editMode = editMode;
        tr.classList.add('border-end');


        ////////////////
        // Handle col //
        ////////////////

        let closeTd = document.createElement('td');
        closeTd.style.textAlign = 'center';
        closeTd.classList.add('p-0');
        closeTd.classList.add('border-end');
        closeTd.style.borderWidth = '0px';
        closeTd.style.verticalAlign = 'middle';

        let closeButton = document.createElement('button');
        closeButton.innerHTML = '<i class="bi-x text-secondary opacity-50"></i>';
        closeButton.classList.add('btn');
        closeButton.classList.add('btn-link');
        closeButton.classList.add('p-0');
        closeButton.onclick = () => { document.getElementById(this.#makePointId(pointId)).remove(); };
        closeTd.appendChild(closeButton);
        tr.appendChild(closeTd);


        ////////////////////
        // Decription col //
        ////////////////////

        if (this.hasDescPerPoint) {
            let td = document.createElement('td');
            td.innerHTML = desc;
            td.style.verticalAlign = 'middle';
            td.classList.add('text-center');
            td.classList.add('border-end');
            tr.appendChild(td);
        }


        //////////////
        // Var cols //
        //////////////

        for (let i = 0 ; i < this.vars.length; i++) {
            let v = this.vars[i];

            let td = document.createElement('td');
            td.id = this.#makeAssignedVarId(pointId, v.name);
            td.style.verticalAlign = 'middle';
            td.classList.add('bg-opacity-50');

            const onNewValueUpdateMeta = (newValue) => {
                // Validate number
                if (validateNumber(newValue).valid) {
                    td.classList.remove('bg-danger');
                    td.dataset.isValid = 'true';
                }
                else {
                    td.classList.add('bg-danger');
                    td.dataset.isValid = 'false';
                }

                td.dataset.value = newValue;
            };

            let div = document.createElement('div');
            div.classList.add('d-flex');
            div.classList.add('justify-content-center');

            if (editMode == 'always') {
                let input = document.createElement('input');
                input.type = 'number';
                input.classList.add('form-control');
                input.style.width = '90px';
                input.oninput = () => {
                    onNewValueUpdateMeta(commasToDots(input.value));

                    this.updatePoint(pointId);
                    this.rerender();
                };

                // Set value and simulate input event for initial validation
                input.value = dotsToCommas(assignment[v.name]);
                onNewValueUpdateMeta(commasToDots(input.value));

                div.appendChild(input);
            }
            else {
                const onNewValueUpdatePlainDisplay = function () {
                    if (td.dataset.isValid === 'true') {
                        div.innerText = '$' + dotsToCommasInTex(td.dataset.value) + '$';
                    }
                    else {
                        div.innerText = td.dataset.value;
                    }
                }

                onNewValueUpdateMeta(assignment[v.name]);
                onNewValueUpdatePlainDisplay();

                if (editMode == 'clickable') {
                    const onUpdateFunction = (newValue) => {
                        onNewValueUpdateMeta(newValue);

                        this.updatePoint(pointId);
                        this.rerender();
                    };
                    const onBlurFunction = () => {
                        onNewValueUpdatePlainDisplay();

                        if (td.dataset.isValid === 'true') {
                            this.rerender();
                        }
                    }

                    td.addEventListener('click', function handler() {
                        // Disable the click listener temporarily
                        td.removeEventListener('click', handler);
                  
                        // Create an input field and set its value to the current cell content
                        let input = document.createElement('input');
                        input.type = 'number';
                        input.classList.add('form-control');
                        input.style.width = '90px';
                        input.oninput = () => { onUpdateFunction(commasToDots(input.value)); };

                        // Set value
                        input.value = dotsToCommas(td.dataset.value);

                        // Empty the div and place the input field
                        while (div.firstChild) {
                            div.removeChild(div.firstChild);
                        }
                        div.appendChild(input);
                        input.focus();  // Automatically focus the input
                  
                        // Handle blur (when the input loses focus)
                        input.addEventListener('blur', () => {
                            onBlurFunction();

                            td.addEventListener('click', handler);  // Re-enable the click listener
                        });
                  
                        // Optionally, handle pressing "Enter" to confirm the change
                        input.addEventListener('keydown', (event) => {
                          if (event.key === 'Enter') {
                            input.blur();  // Trigger blur event on pressing "Enter"
                          }
                        });
                    });
                }
            }

            td.appendChild(div);

            // Add thick border to the right of last var col
            if (i === this.vars.length - 1) {
                td.style.borderRight = '2px solid black';
            }
            else {
                td.classList.add('border-end');
            }

            tr.appendChild(td);
        }


        /////////////////
        // Result cols //
        /////////////////

        const expTds = document.getElementById(this.#makeHeadingRowId()).querySelectorAll('td.exp');

        for (let expTd of expTds) {
            const expId = expTd.dataset.expId;

            let resultTd = document.createElement('td');
            resultTd.id = this.#makeResultId(tr.dataset.pointId, expId);
            resultTd.style.verticalAlign = 'middle';
            resultTd.style.borderLeft = '2px solid black';
            resultTd.onclick = () => { resultTd.classList.toggle('opacity-0'); };
            if (initiallyInvisible) {
                resultTd.classList.add('opacity-0');
            }

            tr.appendChild(resultTd);
        }
    
        tbody.appendChild(tr);

        this.updatePoint(pointId);
    }

    // editMode: 'always', 'clickable', 'never'

    addExp(rawExp, editMode, initiallyInvisible, desc) {
        const headingTr = document.getElementById(this.#makeHeadingRowId());
        const expId = headingTr.children.length;


        ////////////////
        // Handle row //
        ////////////////

        let handleTr = document.getElementById(this.#makeHandleRowId());
        let closeTd = document.createElement('td');
        closeTd.id = this.#makeHandleExpId(expId);
        closeTd.style.textAlign = 'center';
        closeTd.classList.add('p-0');

        let closeButton = document.createElement('button');
        closeButton.innerHTML = '<i class="bi-x text-secondary opacity-50"></i>';
        closeButton.classList.add('btn');
        closeButton.classList.add('btn-link');
        closeButton.classList.add('p-0');
        closeButton.onclick = () => { this.removeExp(expId);};
        closeTd.appendChild(closeButton);
        handleTr.appendChild(closeTd);


        //////////////
        // Desc row //
        //////////////

        if (this.hasDescPerVarAndExp) {
            let descRow = document.getElementById(this.#makeDescRowId());
            let th = document.createElement('th');
            th.id = this.#makeDescExpId(expId);
            th.style.borderLeft = '2px solid black';
            th.innerHTML = desc;
            descRow.appendChild(th);
        }


        /////////////////
        // Heading row //
        /////////////////

        let td = document.createElement('td');
        td.id = this.#makeHeadingExpId(expId);
        td.dataset.expId = expId;
        td.classList.add('exp');
        td.style.borderLeft = '2px solid black';
        td.style.borderBottom = '2px solid black';
        td.style.verticalAlign = 'bottom';
        td.classList.add('bg-opacity-50');

        const onNewValueUpdateMeta = (newValue) => {
            // Validate exp
            if (this.validateExp(newValue)) {
                td.classList.remove('bg-danger');
                td.dataset.isValid = 'true';
            }
            else {
                td.classList.add('bg-danger');
                td.dataset.isValid = 'false';
            }

            td.dataset.value = newValue;
        };

        // Set and validate initially
        onNewValueUpdateMeta(rawExp);

        let div = document.createElement('div');
        div.classList.add('d-flex');

        if (editMode == 'always') {
            let input = document.createElement('input');
            input.type = 'text';
            input.classList.add('form-control');
            input.style.width = '120px';
            input.oninput = () => {
                onNewValueUpdateMeta(commasToDots(input.value));

                this.updateExp(expId);
                this.rerender();
            };

            // Display initially
            input.value = dotsToCommas(rawExp);

            div.appendChild(input);
        }
        else {
            const onNewValueUpdatePlainDisplay = () => {
                if (td.dataset.isValid === 'true') {
                    const chunks    = td.dataset.value.split('&');

                    let texChunks = [];
                    for (let i = 0; i < chunks.length; i++) {
                        const chunk = chunks[i];

                        if (i % 2 == 0) {
                            texChunks.push(math.parse(chunk).toTex());
                        }
                        else {
                            texChunks.push(chunk);
                        }
                    }
                    // Add empty chunk
                    texChunks.push('');

                    div.innerHTML = this.formatExp(texChunks, expId);
                }
                else {
                    div.innerText = td.dataset.value;
                }
            }

            // Display intially
            onNewValueUpdatePlainDisplay();
            
            if (editMode == 'clickable') {
                const onUpdateFunction = (newValue) => {
                    onNewValueUpdateMeta(newValue);

                    this.updateExp(expId);
                    this.rerender();
                };
                const onBlurFunction = () => {
                    onNewValueUpdatePlainDisplay();

                    if (td.dataset.isValid === 'true') {
                        this.rerender();
                    }
                }

                td.addEventListener('click', function handler() {
                    // Disable the click listener temporarily
                    td.removeEventListener('click', handler);
                
                    // Create an input field and set its value to the current cell content
                    let input = document.createElement('input');
                    input.type = 'text';
                    input.classList.add('form-control');
                    input.style.width = '120px';
                    input.oninput = () => { onUpdateFunction(commasToDots(input.value)); };

                    // Set value
                    input.value = dotsToCommas(td.dataset.value);

                    // Empty the div and place the input field
                    while (div.firstChild) {
                        div.removeChild(div.firstChild);
                    }
                    div.appendChild(input);
                    input.focus();  // Automatically focus the input

                    // Handle blur (when the input loses focus)
                    input.addEventListener('blur', () => {
                        onBlurFunction();

                        td.addEventListener('click', handler);  // Re-enable the click listener
                    });
                
                    // Optionally, handle pressing "Enter" to confirm the change
                    input.addEventListener('keydown', (event) => {
                        if (event.key === 'Enter') {
                            input.blur();  // Trigger blur event on pressing "Enter"
                        }
                    });
                });
            }
        }

        td.appendChild(div);
        headingTr.appendChild(td);


        ////////////////
        // Point rows //
        ////////////////

        const tbody = document.getElementById(this.#makeValueTableId()).getElementsByTagName('tbody')[0];

        for (let tr of tbody.children) {
            let resultTd = document.createElement('td');
            resultTd.id = this.#makeResultId(tr.dataset.pointId, expId);
            resultTd.style.verticalAlign = 'middle';
            resultTd.style.borderLeft = '2px solid black';
            resultTd.onclick = () => { resultTd.classList.toggle('opacity-0'); };
            if (initiallyInvisible) {
                resultTd.classList.add('opacity-0');
            }

            tr.appendChild(resultTd);
        }

        this.updateExp(expId);
    }

    removeExp(expId) {
        // Remove from handle row
        document.getElementById(this.#makeHandleExpId(expId)).remove();

        // Remove from description row
        if (this.hasDescPerVarAndExp) {
            document.getElementById(this.#makeDescExpId(expId)).remove();
        }

        // Remove from heading row
        document.getElementById(this.#makeHeadingExpId(expId)).remove();

        // Remove result column from all rows
        const tbody = document.getElementById(this.#makeValueTableId()).getElementsByTagName('tbody')[0];

        for (let tr of tbody.children) {
            document.getElementById(this.#makeResultId(tr.dataset.pointId, expId)).remove();
        }
    }


    //////////////////
    // Update Table //
    //////////////////

    update() {
        const expThs = document.getElementById(this.#makeHeadingRowId()).querySelectorAll('td.exp');

        for (let td of expThs) {
            this.updateExp(td.dataset.expId);
        }
    }

    updateExp(expId) {
        const tbody = document.getElementById(this.#makeValueTableId()).getElementsByTagName('tbody')[0];
        
        for (let tr of tbody.children) {
            this.updateResult(tr.dataset.pointId, expId);
        }
    }

    updatePoint(pointId) {
        const expThs = document.getElementById(this.#makeHeadingRowId()).querySelectorAll('td.exp');

        for (let td of expThs) {
            this.updateResult(pointId, td.dataset.expId);
        }
    }

    updateResult(pointId, expId) {
        const resultTd = document.getElementById(this.#makeResultId(pointId, expId));
    

        /////////////////////////////////
        // Collect variable assignment //
        /////////////////////////////////

        let errorOccurred = false;
        let assignment = {};
        for (let v of this.vars) {
            const varTd = document.getElementById(this.#makeAssignedVarId(pointId, v.name));
    
            // Register invalid input
            if (varTd.dataset.isValid === 'false') {
                errorOccurred = true;
                break;
            }
    
            assignment[v.name] = parseFloat(varTd.dataset.value);
        }

        // Check for invalid input
        if (errorOccurred) {
            resultTd.innerHTML = '<em>Variablenbelegung ungültig</em>';
            return;
        }
    

        /////////////////
        // Collect exp //
        /////////////////

        const expTd = document.getElementById(this.#makeHeadingExpId(expId));

        // Register invalid input
        if (expTd.dataset.isValid === 'false') {
            resultTd.innerText = '';
            return;
        }
        
        const exp = expTd.dataset.value;


        //////////////
        // Evaluate //
        //////////////

        try {
            const chunks  = exp.split('&');

            let texChunks = [];
            for (let i = 0; i < chunks.length; i++) {
                const chunk = chunks[i];

                if (i % 2 == 0) {
                    let mathjsChunk = math.parse(chunk);

                    // Transform as follows:
                    // 1) Substitute variables for their values
                    //    This introduces implicit multiplications 2x -> 2(3)
                    // 2) Make implicit multiplication explicit: 2(3) -> 2 * 3
                    mathjsChunk = mathjsChunk.transform(function (node, path, parent) {
                        if (node.isSymbolNode && node.name in assignment) {
                            return new math.ConstantNode(assignment[node.name]);
                        }
                        else {
                            return node;
                        }
                    });
    
                    // TODO: Hack
                    let doAgain = true;
                    while (doAgain) {
                        doAgain = false;
                        mathjsChunk = mathjsChunk.transform(function (node, path, parent) {
                            if (node.isOperatorNode && node.fn === 'multiply' && node.implicit) {
                                doAgain = true;
                                return new math.OperatorNode('*', 'multiply', node.args);
                            }
                            else {
                                return node;
                            }
                        });
                    }
    
                    texChunks.push(mathjsChunk.toTex());
                }
                else {
                    texChunks.push(chunk);
                }
            }

            // Replace `&` and `~` by ``
            const result = (Math.round(math.evaluate(exp.replace(/&/g, '').replace(/~/g, ''), assignment)*1000) / 1000).toString();

            // Add result chunk
            texChunks.push('=~ \\underline{\\underline{' + result + '}}');

            // Update view
            resultTd.innerHTML = this.formatExp(texChunks, expId);
        }
        catch (error) {
            resultTd.innerHTML = '<em>Termwert nicht definiert</em>';
        }
    }


    /////////////
    // Helpers //
    /////////////

    #makeValueTableId() {
        return 'value-table-' + this.divId;
    }

    #makeHandleRowId() {
        return this.#makeValueTableId() + '-handles';
    }

    #makeDescRowId() {
        return this.#makeValueTableId() + '-desc';
    }

    #makeHeadingRowId() {
        return this.#makeValueTableId() + '-heading';
    }

    #makeHandleExpId(expId) {
        return this.#makeHandleRowId() + '-' + expId;
    }

    #makeDescExpId(expId) {
        return this.#makeDescRowId() + '-' + expId;
    }

    #makeHeadingExpId(expId) {
        return this.#makeHeadingRowId() + '-' + expId;
    }
    
    #makePointId(pointId) {
        return this.#makeValueTableId() + '-' + pointId;
    }

    #makeAssignedVarId(pointId, varName) {
        return this.#makePointId(pointId) + '-' + varName;
    }

    #makeResultId(pointId, expId) {
        return this.#makePointId(pointId) + '-result-' + expId;
    }

    #makeSyncWidthId(expId, chunkId) {
        return 'value-table-' + this.divId + '-' + expId + '-' + chunkId;
    }

    // Must only be called after Mathjax etc. is loaded
    rerender() {
        MathJax.typeset();
        setupSyncWidths();
        syncWidths();
    }

    validateExp(text) {
        // Ignore the `&` and `~` chars
        text = text.replace(/&/g, '').replace(/~/g, '');

        if (text.trim() === '') {
            return false;
        }

        try {
            const mathjsExp = math.parse(text);

            // Check that there are no free variables
            const allowedVarNames = new Set(this.vars.map(
                function (v) {
                    return v.name;
                }
            ));
            let success = true;
            mathjsExp.traverse(function (node) {
                if (node.isSymbolNode) {
                    if (!allowedVarNames.has(node.name)) {
                        success = false;
                    }
                }
            });

            return success;
        }
        catch (error) {
            return false;
        }
    }

    // `tex` is already in Latex format
    formatExp = function(texChunks, expId) {
        // Replace all dots by commas
        texChunks = texChunks.map(dotsToCommasInTex);

        // Prepare each chunk
        for (let i = 0; i < texChunks.length; i++) {
            let texChunk = texChunks[i];
            if (texChunk.trim() === '') {
                texChunk = '';
            }
            else {
                texChunk = '$' + texChunk + '$';
            }

            // Surround the chunk
            texChunks[i] = '<span style="display:inline-block;" data-sync-width-id="' + this.#makeSyncWidthId(expId, i) + '">' + texChunk + '</span>';
        }

        return texChunks.join('&nbsp;&nbsp;');
    }
}

function dotsToCommas(text) {
    // Replace dots by commas
    return text.toString().replace(/\./g, ',')
}

function dotsToCommasInTex(tex) {
    // Replace dots by commas
    return tex.toString().replace(/\./g, '{,}')
}

function commasToDots(text) {
    // Replace commas by dots
    return text.toString().replace(/,/g, '.');
}

function validateNumber(text) {
    // Special case `text` is really a number
    if (typeof text === 'number') {
        return {
            valid: true,
            number: text
        }
    }

    const n = parseFloat(text);
    if (isNaN(n) || !Number.isFinite(n) || text.trim() === '' || n != text) {
        return {
            valid: false
        }
    }
    else {
        return {
            valid: true,
            number: n
        }
    }
}