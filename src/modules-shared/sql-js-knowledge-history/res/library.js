function sql_js_printHistoryDb(db, divId)
{   
    db.then(function(db) {
        const results = db.exec('SELECT date_begin, date_end, time_unit, title, description FROM events ORDER BY date_begin');

        if (results.length === 0) {
            return '<p>(keine Einträge)</p>';
        }

        let content = '';

        for (const result of results)
        {
            content += `
                <table class="table table-bordered border-dark table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Event</th>
                    </tr>
                </thead>
                <tbody>
            `;

            for (const row of result.values)
            {
                const dateBegin = row[0];
                const dateEnd = row[1];
                const timeUnit = row[2];
                const title = row[3];
                const description = row[4];

                content += `<tr>`;

                if (timeUnit == 'y') {
                    // Extract year (unclear how many digits, possibly negative)
                    const yearFrom = dateBegin.match(/-?\d+/)[0];
                    const yearTo = dateEnd.match(/-?\d+/)[0];

                    if (yearFrom === yearTo) {
                        content += `<td>${yearFrom}</td>`;
                    }
                    else {
                        content += `<td>${yearFrom} to ${yearTo}</td>`;
                    }
                }
                else if (timeUnit == 'm') {
                    // Extract month
                    const monthFrom = dateBegin.match(/-?\d{1,}-\d{1,2}/)[0];
                    const monthTo = dateBegin.match(/-?\d{1,}-\d{1,2}/)[0];

                    if (monthFrom === monthTo) {
                        content += `<td>${monthFrom}</td>`;
                    }
                    else {
                        console.log(monthFrom);
                        console.log(monthTo);
                        content += `<td>${monthFrom} to ${monthTo}</td>`;
                    }
                }
                else {
                    if (dateBegin === dateEnd) {
                        content += `<td>${dateBegin}</td>`;
                    }
                    else {
                        content += `<td>${dateBegin} to ${dateEnd}</td>`;
                    }
                }

                content += `<td><strong>${title}</strong>`;
                
                if (description !== null) {
                    content += `<br>${description}`;
                }

                content += `</td></tr>`;
            }

            content += `
                </tbody>
                </table>
            `;
        }

        document.getElementById(divId).innerHTML = content;
    });
}