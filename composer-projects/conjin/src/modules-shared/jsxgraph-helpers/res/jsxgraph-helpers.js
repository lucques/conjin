// Register setup synchronously, before the template closes page-prep registration.
// The callback may return a promise; it must include the final initial label creation.
function jsx_register_initial_board(id, create) {
    if (!window.pagePrep) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', create, { once: true });
        }
        else {
            create();
        }
        return;
    }

    const promise = new Promise((resolve, reject) => {
        const initialize = () => {
            try {
                resolve(create());
            }
            catch (error) {
                reject(error);
            }
        };
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initialize, { once: true });
        }
        else {
            initialize();
        }
    });
    pagePrep.track({
        id: `jsxgraph:initial-board:${id}`,
        before: 'initialContentReady',
        promise,
    });
}

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

function jsx_create_segment_midpoint_label(board, segment, text, perpendicularOffsetPx, attributes) {
    const coordinates = function() {
        const point1 = segment.point1.coords;
        const point2 = segment.point2.coords;
        const dxPx = point2.scrCoords[1] - point1.scrCoords[1];
        const dyPx = point2.scrCoords[2] - point1.scrCoords[2];
        const lengthPx = Math.hypot(dxPx, dyPx);
        const midpointX = 0.5 * (point1.usrCoords[1] + point2.usrCoords[1]);
        const midpointY = 0.5 * (point1.usrCoords[2] + point2.usrCoords[2]);

        if (lengthPx === 0) {
            return [midpointX, midpointY];
        }

        // Offset to the left of the projected segment while retaining its exact longitudinal midpoint.
        const normalXPx = dyPx / lengthPx;
        const normalYPx = -dxPx / lengthPx;

        return [
            midpointX + perpendicularOffsetPx * normalXPx / board.unitX,
            midpointY - perpendicularOffsetPx * normalYPx / board.unitY
        ];
    };

    const label = board.create('text', [
        function() { return coordinates()[0]; },
        function() { return coordinates()[1]; },
        text
    ], {
        ...attributes,
        element3d: segment.visProp.element3d
    });

    segment.addChild(label);
    label.addParents(segment);

    return label;
}

// One spacing function per coordinate drives both ticks and grid, even with hidden axes.
function jsx_coordinate_spacing(board, coordinate, spacing) {
    let previousUnit;
    let previousSpan;
    let major;
    return function() {
        if (spacing.major !== null) {
            return spacing.major;
        }
        const bounds = board.getBoundingBox();
        const unit = coordinate === 'x' ? board.unitX : board.unitY;
        const span = coordinate === 'x' ? bounds[2] - bounds[0] : bounds[1] - bounds[3];
        if (unit !== previousUnit || span !== previousSpan) {
            // Use JSXGraph's 1/2/5 progression and its default minimum tick separation.
            const configuredMinimum = board.options.axis.ticks.minTicksDistance
                ?? board.options.ticks.minTicksDistance;
            const minimumPixels = typeof configuredMinimum === 'function' ? configuredMinimum() : configuredMinimum;
            const minimum = minimumPixels * spacing.subdivisions / unit;
            let minimumStep = 10 ** Math.floor(Math.log10(minimum));
            if (2 * minimumStep >= minimum) {
                minimumStep *= 2;
            } else if (5 * minimumStep >= minimum) {
                minimumStep *= 5;
            }
            const target = span / 6;
            let targetStep = 10 ** Math.floor(Math.log10(target));
            if (5 * targetStep < target) {
                targetStep *= 5;
            } else if (2 * targetStep < target) {
                targetStep *= 2;
            }
            major = Math.max(minimumStep, targetStep);
            previousUnit = unit;
            previousSpan = span;
        }
        return major;
    };
}

function jsx_configure_2d_axes(board, axes, spacing, grid, printMode, sequence, formatters) {
    const distances = {
        x: jsx_coordinate_spacing(board, 'x', spacing.x),
        y: jsx_coordinate_spacing(board, 'y', spacing.y)
    };
    board.defaultAxes = {};
    ['x', 'y'].forEach(function(coordinate) {
        const axis = axes[coordinate];
        const horizontal = coordinate === 'x';
        const labelPosition = sequence
            ? (horizontal ? { offset: [0, 0], anchorX: 'middle', anchorY: 'top' } : { offset: [-8, 0], anchorX: 'right' })
            : (horizontal ? { offset: [5, 3], anchorX: 'right', anchorY: 'bottom' } : { offset: [6, 5], anchorX: 'left' });
        // JSXGraph's navigation and snapping expect both default axis objects to exist.
        const element = board.create('axis', [[0, 0], horizontal ? [1, 0] : [0, 1]], {
            visible: axis.is_enabled,
            fixed: true,
            name: axis.label,
            withLabel: axis.is_enabled && axis.label !== '',
            strokeWidth: axis.thickness,
            ...(printMode ? { strokeColor: 'black' } : {}),
            label: { fontSize: axis.label_font_size, cssStyle: 'font-weight: 900;', position: 'last', ...labelPosition },
            ticks: {
                visible: axis.ticks.is_enabled,
                insertTicks: false,
                ticksDistance: distances[coordinate](),
                minorTicks: axis.ticks.minor ? spacing[coordinate].subdivisions - 1 : 0,
                majorHeight: 10 + axis.thickness,
                drawLabels: axis.ticks.is_enabled && axis.ticks.numbers,
                generateLabelText: function(tick) {
                    const value = tick.usrCoords[horizontal ? 1 : 2];
                    return formatters[coordinate] === null
                        ? String(Math.round(value * 100) / 100)
                        : formatters[coordinate](value);
                },
                label: {
                    fontSize: axis.ticks.numbers_font_size,
                    offset: horizontal ? [0, -8 - axis.thickness] : [-9 - axis.thickness, 0]
                }
            }
        });
        // The tick constructor requires a numeric distance; attributes can evaluate functions afterward.
        element.defaultTicks.setAttribute({ ticksDistance: distances[coordinate] });
        board.defaultAxes[coordinate] = element;
        if (axis.ticks.is_enabled) {
            jsx_add_black_major_ticks(board, element, axis.thickness, distances[coordinate]);
        }
    });
    if (grid.is_enabled) {
        board.create('grid', [], {
            majorStep: function() { return [distances.x(), distances.y()]; },
            minorElements: grid.minor ? [spacing.x.subdivisions - 1, spacing.y.subdivisions - 1] : 0,
            minor: { face: 'line', strokeOpacity: 0.25 },
            ...(printMode ? { strokeColor: 'black', minor: { face: 'line', strokeColor: 'black', strokeOpacity: 0.25 } } : {})
        });
    }
    board.fullUpdate();
}

function jsx_add_black_major_ticks(board, axis, strokeWidth, distance) {
    board.create('ticks', [axis, distance()], {
        insertTicks: false,
        ticksDistance: distance,
        minorTicks: 0,
        majorHeight: 10 + strokeWidth,
        drawLabels: false,
        strokeColor: 'black',
        highlightStrokeColor: 'black',
        strokeWidth: strokeWidth,
        strokeOpacity: 1,
        fixed: true
    });
}

function jsx_make_ticks3d_labels_zoom_invariant(board, ticks) {
    // JSXGraph updates 3D tick lengths during zoom, but normally fixes their label positions at creation time.
    if (!ticks.labels) {
        return;
    }
    const evaluate = function(value) {
        return typeof value === 'function' ? value() : value;
    };
    const evaluateVector = function(vector) {
        return vector.map(evaluate);
    };
    const normalize = function(vector) {
        const length = Math.hypot(...vector);
        return vector.map(function(value) {
            return value / length;
        });
    };

    ticks.labels.forEach(function(label, index) {
        label.F = function() {
            const point = typeof ticks.point === 'function'
                ? ticks.point().slice(1)
                : evaluateVector(ticks.point);
            const axisDirection = normalize(evaluateVector(ticks.direction1));
            const tickDirection = normalize(evaluateVector(ticks.direction2));
            const tickOffset = index * ticks.evalVisProp('ticksdistance');
            const tickEndings = ticks.evalVisProp('tickendings');
            const labelDistance = 2 * ticks.evalVisProp('majorheight') * tickEndings[1]
                / Math.sqrt(board.unitX * board.unitY);

            return point.map(function(value, coordinate) {
                return value
                    + tickOffset * axisDirection[coordinate]
                    + labelDistance * tickDirection[coordinate];
            });
        };
    });
}
