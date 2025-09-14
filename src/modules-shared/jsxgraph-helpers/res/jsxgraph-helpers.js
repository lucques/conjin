///////////
// Utils //
///////////

function jsx_compute_units_per_pixel_x(board) {
    const x1 = board.getBoundingBox()[0];
    const x2 = board.getBoundingBox()[2];
    const coordWidth = x2 - x1;
    const pixelWidth = board.canvasWidth;
    return coordWidth / pixelWidth;
}

function jsx_compute_units_per_pixel_y(board) {
    const y1 = board.getBoundingBox()[1];
    const y2 = board.getBoundingBox()[3];
    const coordHeight = y1 - y2;
    const pixelHeight = board.canvasHeight;
    return coordHeight / pixelHeight;
}