//////////////////
// Just display //
//////////////////

function sql_js_inline_resultsToHTML(results, title = '', truncated = false)
{
    if (results.length === 0) {
        return '<p>(leeres Ergebnis)</p>';
    }

    let content = '';

    for (const result of results)
    {
        content += `
            <table class="table table-bordered border-dark table-striped">
            <thead>
        `;

        if (title !== '') {
            content += `
                <tr>
                    <th class="text-center" colspan="${result.columns.length}">${title}</th>
                </tr>
            `;
        }
        
        content += `
                <tr>
        `;
        
        for (const colName of result.columns)
        {
            content += `<th>${colName}</th>`;
        }
        content += `
                </tr>
            </thead>
            <tbody>
        `;
        for (const row of result.values)
        {
            content += `<tr>`;
            for (const cell of row)
            {
                content += `<td>${cell}</td>`;
            }
            content += `</tr>`;
        }
        if (truncated) {
            content += `
                <tr>
                    ${'<td class="text-center">...</td>'.repeat(result.columns.length)}
                </tr>
            `;
        }
        content += `
            </tbody>
            </table>
        `;
    }

    return content;
}


////////////////////////////////////////////////////////////
// Display schema of multiple tables, each with resultset //
////////////////////////////////////////////////////////////

// tables: list of {
//    name: string
//    columns: list of
//       {
//           type: data,
//           name: string,
//           isPartOfPrimaryKey: boolean,
//           isPartOfForeignKey: boolean,
//       }
//       or
//       {
//           type: ellipsis,
//       }
//    result: resultset  // optional,
//    truncated: boolean // optional,
// }
function sql_js_inline_schemaWithResultsToHTML(tables, showButton = true)
{
    let content = `<div class="d-grid" style="grid-template-columns: auto auto auto auto; align-items: baseline;">`;

    // For each table
    for (const table of tables)
    {
        const collapseButtonId = nextUniqueId();
        const tbodyId = nextUniqueId();

        content += `
            <div style="justify-self:end;">
                <strong>${table.name}</strong>
            </div>
            <div style="font-size:150%;">
                (
            </div>
            <div>
        `;

        if (showButton && table.result !== undefined) {
            content += `
                <button id="${collapseButtonId}" class="btn btn-link p-0" type="button" onClick="sql_js_inline_resultsAndSchemaToHTML_aux_toggle('${collapseButtonId}', '${tbodyId}')">
                    <i class="bi bi-chevron-right"></i>
                </button>
            `;
        }

        content += `
            </div>
            <div style="justify-self: start; align-self: start;">
                <div style="display:flex;">
                <table class="table table-borderless last-child" style="margin-top:5px;">
                <thead onClick="sql_js_inline_resultsAndSchemaToHTML_aux_toggle('${collapseButtonId}', '${tbodyId}')">
                    <tr class="align-middle">
        `;

        for (const col of table.columns)
        {
            if (col.type === 'data') {
                let colContent = col.name;

                if (col.isPartOfPrimaryKey) {
                    colContent = `<u>${colContent}</u>`;
                }
                if (col.isPartOfForeignKey) {
                    colContent = `<em>${colContent}</em>`;
                }
                content += `<td style="border: 1px solid #555; border-bottom: 1px solid black; padding-top:0; padding-bottom:0;">${colContent}</td>`;
            }
            else if (col.type === 'ellipsis') {
                content += `<td style="border: 1px solid #555; border-bottom: 1px solid black; padding-top:0; padding-bottom:0;">...</td>`;
            }
        }
        content += `
                    </tr>
                </thead>
                <tbody id="${tbodyId}"${table.open === true ? '' : ' class="collapse"' }}>
        `;

        if (table.result !== undefined) {
            for (const row of table.result.values)
            {
                content += `<tr>`;

                let index = 0;
                for (const col of table.columns) {
                    if (col.type === 'data') {
                        content += `<td style="border: 1px solid #555;">${row[index]}</td>`;
                        index++;
                    }
                    else if (col.type === 'ellipsis') {
                        content += `<td style="border: 1px solid #555;">...</td>`;
                    }
                }
                content += `</tr>`;
            }
            if (table.truncated === true) {
                content += `
                        <tr>
                            ${'<td class="text-center p-0" style="border: 1px solid #555;">...</td>'.repeat(table.columns.length)}
                        </tr>
                `;
            }
        }
        content += `
                </tbody>
                </table>
                <div style="font-size:150%;">
                    )
                </div>
                </div>
            </div>
        `;
    }

    content += `</div>`;

    return content;
}

function sql_js_inline_resultsAndSchemaToHTML_aux_toggle(collapseButtonId, tbodyId) {
    // Toggle collapse
    const tbody = document.getElementById(tbodyId);
    tbody.classList.toggle('collapse');
    
    // Only if button exists
    const collapseButton = document.getElementById(collapseButtonId);
    if (collapseButton !== null) {
        const icon = document.getElementById(collapseButtonId).querySelector('i');
        if (icon !== null) {
            const isExpanded = !tbody.classList.contains('collapse');
            if (isExpanded) {
                icon.classList.remove('bi-chevron-right');
                icon.classList.add('bi-chevron-down');
            } else {
                icon.classList.remove('bi-chevron-down');
                icon.classList.add('bi-chevron-right');
            }
        }
    }
}