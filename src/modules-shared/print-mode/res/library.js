// Render the `#content main` element in a paged view
function setupPrintMode() {
    const contentNode = document.getElementById('content');
    const mainNode = document.querySelector('#content main');
    contentNode.removeChild(mainNode);
    
    let paged = new Paged.Previewer();
    let flow = paged.preview(mainNode, ['/modules-shared/print-mode/res/sheets.css'], contentNode);
}