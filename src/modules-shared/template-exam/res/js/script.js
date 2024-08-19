class TableOfValues extends HTMLElement {
    connectedCallback() {
        // Select all <p> elements that are direct children of this custom element
        let pElements = this.querySelectorAll('p');
        console.log(pElements);

        // Remove each <p> element
        pElements.forEach(p => p.remove());


        // Create a new <p> element
        const pElement = document.createElement('p');
        pElement.textContent = 'Test';

        // Append it to the shadow DOM or light DOM
        // If using Shadow DOM
        this.appendChild(pElement);

        // If not using Shadow DOM, replace the above line with:
        // this.appendChild(pElement);
    }
}

customElements.define("table-of-values", TableOfValues);