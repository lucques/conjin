function sql_js_installTaggedDb(db, tagsDivId, itemsDivId) {
    //////////////////
    // Install tags //
    //////////////////

    db.then(function(dbRes) {
        const tagResults = dbRes.exec('SELECT COUNT(*) FROM items; SELECT tag, COUNT(*) AS size FROM items_to_tags GROUP BY tag ORDER BY size DESC, tag');

        if (tagResults.length > 0) {
            assert(tagResults.length === 2);
            const totalCount = tagResults[0].values[0][0];
            const result = tagResults[1];

            // Get the target container element
            const container = document.getElementById(tagsDivId);

            // Create a <p> element for the tags
            const paragraph = document.createElement('p');
            paragraph.className = 'd-flex flex-wrap gap-2'; // Add flexbox and spacing

            // Add "Tags: " text
            const tagsLabel = document.createElement('strong');
            tagsLabel.textContent = 'Tags: ';
            tagsLabel.className = 'p-1'; // Add padding for spacing
            paragraph.appendChild(tagsLabel);

            // Create the "all" button element
            const allButton = document.createElement('button');
            allButton.className = 'btn btn-sm btn-secondary tags-all';
            allButton.textContent = 'Alle';

            // Create the badge for the count
            const badge = document.createElement('span');
            badge.className = 'badge bg-light text-dark ms-2'; // Bootstrap badge styling with spacing
            badge.textContent = totalCount;

            // Add the badge to the button
            allButton.appendChild(badge);           
            
            // Set the click event for the button
            allButton.onclick = function () {
                updateURLParameter('tag', null);
                sql_js_updateTaggedDbItems(db, tagsDivId, itemsDivId);
            };

            // Append the "all" button to the paragraph
            paragraph.appendChild(allButton);

            // Add buttons for each tag with counts
            for (const row of result.values) {
                const tag = row[0];
                const count = row[1];

                // Create the button element
                const button = document.createElement('button');
                button.className = 'btn btn-sm btn-primary tags-single';
                button.dataset.tag = tag;
                button.textContent = tag;

                // Create the badge for the count
                const badge = document.createElement('span');
                badge.className = 'badge bg-light text-dark ms-2'; // Bootstrap badge styling with spacing
                badge.textContent = count;

                // Add the badge to the button
                button.appendChild(badge);

                // Set the click event for the button
                button.onclick = function () {
                    updateURLParameter('tag', tag);
                    sql_js_updateTaggedDbItems(db, tagsDivId, itemsDivId);
                };

                // Append the button to the paragraph
                paragraph.appendChild(button);
            }

            // Add expand/collapse buttons
            const expandCollapseDiv = document.createElement('div');
            expandCollapseDiv.className = 'd-flex gap-2 mt-3'; // Styling for spacing and layout

            const expandAllButton = document.createElement('button');
            expandAllButton.className = 'btn btn-sm btn-secondary';
            expandAllButton.textContent = 'Alle ausklappen';
            expandAllButton.onclick = function () {
                const items = document.querySelectorAll(`#${itemsDivId} .collapse`);
                items.forEach(item => bootstrap.Collapse.getOrCreateInstance(item).show());
            };

            const collapseAllButton = document.createElement('button');
            collapseAllButton.className = 'btn btn-sm btn-secondary';
            collapseAllButton.textContent = 'Alle einklappen';
            collapseAllButton.onclick = function () {
                const items = document.querySelectorAll(`#${itemsDivId} .collapse`);
                items.forEach(item => bootstrap.Collapse.getOrCreateInstance(item).hide());
            };

            // Append the expand/collapse buttons to the container
            expandCollapseDiv.appendChild(expandAllButton);
            expandCollapseDiv.appendChild(collapseAllButton);
            
            // Clear the container and add the paragraph and expand/collapse buttons
            container.innerHTML = ''; // Clear any previous content
            container.appendChild(paragraph);
            container.appendChild(expandCollapseDiv);
        }
    });


    ///////////////////////////////////////////////////
    // Register handling of browser history movement //
    ///////////////////////////////////////////////////

    window.addEventListener('popstate', () => {
        const selectedTag = getURLParameter('tag'); // Get the current tag from the URL
        sql_js_updateTaggedDbItems(db, tagsDivId, itemsDivId);
    });

    
    ////////////////////////////////
    // Initially update the items //
    ////////////////////////////////

    sql_js_updateTaggedDbItems(db, tagsDivId, itemsDivId);
}

function sql_js_updateTaggedDbItems(db, tagsDivId, itemsDivId) {
    // Get the tag from the URL
    const curTag = getURLParameter('tag');
    const whereClause = curTag ? ` WHERE items.id IN (SELECT item_id FROM items_to_tags WHERE tag='${curTag}')` : '';

    db.then(function(dbRes) {

        //////////////////
        // Update items //
        //////////////////

        const container = document.getElementById(itemsDivId);

        // Clear the container
        container.innerHTML = '';

        const itemResults = dbRes.exec('SELECT id, title, content, GROUP_CONCAT(items_to_tags.tag, ",") AS tags FROM items LEFT JOIN items_to_tags ON id = item_id' + whereClause + ' GROUP BY id, title, content ORDER BY sorting');

        if (itemResults.length === 0) {
            const noEntriesParagraph = document.createElement('p');
            noEntriesParagraph.textContent = '(keine Einträge)';
            container.appendChild(noEntriesParagraph);
            return;
        }

        assert(itemResults.length === 1);
        const result = itemResults[0];

        // Create the accordion container
        const accordion = document.createElement('div');
        accordion.className = 'accordion';
        accordion.id = 'itemsAccordion';

        result.values.forEach((row, index) => {
            const id = row[0];
            const title = row[1];
            const content = row[2];
            const tags = row[3];

            // Create the accordion item
            const accordionItem = document.createElement('div');
            accordionItem.className = 'accordion-item';

            // Accordion Header
            const accordionButton = document.createElement('button');
            accordionButton.className = 'accordion-button collapsed';
            accordionButton.type = 'button';
            accordionButton.setAttribute('data-bs-toggle', 'collapse');
            accordionButton.setAttribute('data-bs-target', `#collapse${index}`);
            accordionButton.innerHTML = title;

            accordionItem.appendChild(accordionButton);

            // Accordion Collapse Body
            const accordionCollapse = document.createElement('div');
            accordionCollapse.id = `collapse${index}`;
            accordionCollapse.className = 'accordion-collapse collapse';

            const accordionBody = document.createElement('div');
            accordionBody.className = 'accordion-body';
            accordionBody.innerHTML = content;

            accordionCollapse.appendChild(accordionBody);
            accordionItem.appendChild(accordionCollapse);
            accordion.appendChild(accordionItem);
        });

        container.appendChild(accordion);
        

        /////////////////////////////////////
        // Update tag div: Mark active tag //
        /////////////////////////////////////

        for (const tag of document.getElementById(tagsDivId).getElementsByClassName('tags-all')) {
            if (curTag === null) {
                tag.classList.add('active');
            } else {
                tag.classList.remove('active');
            }
        }

        for (const tag of document.getElementById(tagsDivId).getElementsByClassName('tags-single')) {
            if (tag.dataset.tag === curTag) {
                tag.classList.add('active');
            } else {
                tag.classList.remove('active');
            }
        }
    });
}


