/**
 * @typedef {Object} ColInfo
 * @property {string} name
 * @property {string} typ
 * 
 * @typedef {Object} ForeignKeyInfo
 * @property {string} table
 * @property {string} foreignCol
 * 
 * @typedef {Object} TableInfo
 * @property {string} name
 * @property {Array.<ColInfo>} cols
 * @property {Array.<string>} primarykey
 * @property {Map<string, Array.<ForeignKeyInfo>} foreignkeys
 */

////////////////////
// Pure functions //
////////////////////

/**
 * @param {string} createTableBefehl 
 * @returns {TableInfo}
*/
function sql_js_analysis_extractTableInfo(createTableBefehl) {
    // Nutze `[\s\S]` um *beliebiges* Zeichen zu treffen (`.` trifft nicht `\n`)

    const treffer = createTableBefehl.match(/CREATE TABLE\s*\"?([\w]+)\"?\s*\(([\s\S]*)\)/);

    const name  = treffer[1];
    const colsDaten = treffer[2].replace(/--.*\n/g, ''); // Entferne SQL-Kommentare

    const tabellenTeilstueckTreffer = colsDaten.match(/([^\(,]+(?:\([^\)]+\))?)+/g);

    const cols           = [];
    const primarykey = [];
    const foreignkeys   = {};

    for (const tabellenTeilstueck of tabellenTeilstueckTreffer)
    {
        const tabellenTeilstueckTrimmed = tabellenTeilstueck.trim();

        // Pruefe einen von drei Faellen
        // 1. Expliziter primarykey?
        if (tabellenTeilstueckTrimmed.startsWith('PRIMARY KEY')) {
            const treffer = tabellenTeilstueckTrimmed.match(/PRIMARY KEY\s*\((\"?[\w]+\"?)(?:\s*,\s*([\w]+))?\)/);
            treffer.shift(); // Entferne ersten Treffer (den ganzen String)
            for (const primarykeyTeilstueck of treffer) {
                primarykey.push(primarykeyTeilstueck);
            }
        }
        // 2. foreignkeys?
        else if (tabellenTeilstueckTrimmed.startsWith('FOREIGN KEY')) {
            const treffer = tabellenTeilstueckTrimmed.match(/FOREIGN KEY\s*\(([\w]+)\)\s*REFERENCES\s*(\w+)\s*\(\s*([\w]+)\s*\)/);
            const spalte = treffer[1];
            foreignkeys[spalte] = {
                table:      treffer[2],
                foreignCol: treffer[3]
            };
        }
        // 3. Gewoehnliche Spalte?
        else {
            const [name, ...verbleibendeStuecke] = tabellenTeilstueckTrimmed.split(/\s+/);
            const verbleibendeStueckeVereint = verbleibendeStuecke.join(' ').trim();

            if (verbleibendeStueckeVereint.includes('PRIMARY KEY')) {
                // Entferne `PRIMARY KEY` string
                const typ = verbleibendeStueckeVereint.split('PRIMARY KEY').join('').trim();

                cols.push({
                    name,
                    typ
                });

                primarykey.push(name);
            }
            else {
                cols.push({
                    name,
                    typ: verbleibendeStueckeVereint
                });
            }
        }
    }
    
    return { name, cols, primarykey, foreignkeys };
}

// TODO translate to English


//////////
// View //
//////////

/**
 * @param {string} id DOM id
 * @param {Array.<TableInfo>} tableInfos 
 */
function sql_js_analysis_updateTableInfoViewCompact(id, tableInfos) {
    let htmlKompakt = '<table>';

    for (const tabellenInfo of tableInfos) {
        // Ignoriere interne SQLite-Tabellen
        if (tabellenInfo.name === 'sqlite_sequence') {
            continue;
        }

        const tabellenKompakt = [];

        for (const spalte of tabellenInfo.cols)
        {
            // Teil des Primaer- *und* foreignkeyss?
            if (tabellenInfo.primarykey.includes(spalte.name) && spalte.name in tabellenInfo.foreignkeys)
            {
                const foreignkeys = tabellenInfo.foreignkeys[spalte.name];
                tabellenKompakt.push(`<em><span style="text-decoration:underline;">${spalte.name}</span></em>`);                
            }
            // Nur Teil des primarykeys?
            else if (tabellenInfo.primarykey.includes(spalte.name))
            {
                tabellenKompakt.push(`<span style="text-decoration:underline;">${spalte.name}</span>`);
            }
            // Nur Teil des foreignkeyss?
            else if (spalte.name in tabellenInfo.foreignkeys)
            {
                const foreignkeys = tabellenInfo.foreignkeys[spalte.name];
                tabellenKompakt.push(`<em>${spalte.name}</em>`);
            }
            // Gewoehnliche Spalte?
            else {
                tabellenKompakt.push(`${spalte.name}`);
            }
        }

        htmlKompakt      += `<tr><td class="text-end" style="vertical-align:top;"><strong>${tabellenInfo.name}</strong>(</td><td>${tabellenKompakt.join(', ')})</td></tr>`;
    }

    htmlKompakt += '</table>';

    document.getElementById(id).innerHTML = htmlKompakt;
}

/**
 * @param {string} id DOM id
 * @param {TableInfo} tableInfo 
*/
function sql_js_analysis_updateTableInfoViewDetailed(id, tableInfo) {
    let htmlAusfuehrlich = '';

    for (const tabellenInfo of tableInfo) {
        // Ignoriere interne SQLite-Tabellen
        if (tabellenInfo.name === 'sqlite_sequence') {
            continue;
        }

        const tabellenAusfuehrlich = [];

        for (const spalte of tabellenInfo.cols)
        {
            // Teil des Primaer- *und* foreignkeyss?
            if (tabellenInfo.primarykey.includes(spalte.name) && spalte.name in tabellenInfo.foreignkeys)
            {
                const foreignkeys = tabellenInfo.foreignkeys[spalte.name];
                tabellenAusfuehrlich.push(`<li><em><span style="text-decoration:underline;">${spalte.name}</span></em> (${spalte.typ})<ul><li>Teil des Primärschlüssels</li><li>Fremdschlüssel auf ${foreignkeys.tabelle}.${foreignkeys.foreignCol}</li></ul></li>`);
                
            }
            // Nur Teil des primarykeys?
            else if (tabellenInfo.primarykey.includes(spalte.name))
            {
                tabellenAusfuehrlich.push(`<li><span style="text-decoration:underline;">${spalte.name}</span> (${spalte.typ})<ul><li>Teil des Primärschlüssels</li></ul></li>`);
            }
            // Nur Teil des foreignkeyss?
            else if (spalte.name in tabellenInfo.foreignkeys)
            {
                const foreignkeys = tabellenInfo.foreignkeys[spalte.name];
                tabellenAusfuehrlich.push(`<li><em>${spalte.name}</em> (${spalte.typ})<ul><li>Fremdschlüssel auf ${foreignkeys.table}.${foreignkeys.foreignCol}</li></ul></li>`);
            }
            // Gewoehnliche Spalte?
            else {
                tabellenAusfuehrlich.push(`<li>${spalte.name} (${spalte.typ})</li>`);
            }
        }

        htmlAusfuehrlich += `<div><strong>${tabellenInfo.name}</strong><br><ul>${tabellenAusfuehrlich.join('')}</ul></div>`;
    }

    document.getElementById(id).innerHTML = htmlAusfuehrlich;
}