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