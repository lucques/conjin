//////////
// Math //
//////////

function graphLayout(xAxisFrom, xAxisTo, xAxisDTick, yAxisFrom, yAxisTo, yAxisDTick, xAxisLabel = '', yAxisLabel = '') {
    return {
	    margin: { t: 0 },
        annotations: [
            {
                ax: 0,
                axref: 'x',
                ay: yAxisTo - 1*yAxisDTick,
                ayref: 'y',
                x: 0,
                xref: 'x',
                y: yAxisTo,
                yref: 'y',
                arrowwidth: 2,
                arrowhead: 2
            },
            {
                ax: xAxisTo - 1*xAxisDTick,
                axref: 'x',
                ay: 0,
                ayref: 'y',
                x: xAxisTo,
                xref: 'x',
                y: 0,
                yref: 'y',
                arrowwidth: 2,
                arrowhead: 2
            },
            {
                xref: 'paper',
                yref: 'paper',
                xanchor: 'left',
                x: 0.07,
                y: 1.01,
                text: yAxisLabel,
                showarrow: false
            },
            {
                xref: 'paper',
                yref: 'paper',
                x: 1,
                y: 0,
                text: xAxisLabel,
                showarrow: false
            }
        ],
        xaxis: {
            range: [xAxisFrom, xAxisTo],
            dtick: xAxisDTick,
            zerolinewidth: 2,
            zeroline: true
        },
        yaxis: {
            range: [yAxisFrom, yAxisTo],
            dtick: yAxisDTick,
            zerolinewidth: 2,
            zeroline: true
        }
    };
}

function graphSingleFunctionTraces(f, xs, xsDetailed = undefined) {
    const ys = xs.map(f);

    traceMarkers = {
        x: xs,
        y: ys,
        mode: 'markers',
        line: { dash: 'dot', width: 1 },
        showlegend: false,
        hoverinfo: 'x+y'
    }

    if (xsDetailed != undefined) {
        const ysDetailed = xsDetailed.map(f);
        traceLine = {
            x: xsDetailed,
            y: ysDetailed,
            mode: 'lines',
            line: { dash: 'dot', width: 1 },
            showlegend: false,
            hoverinfo: 'skip'
        }
        return [traceMarkers, traceLine];
    }
    else {
        return [traceMarkers];
    }
}

function graphScatterTraces(xs, ys) {
    traceMarkers = {
        x: xs,
        y: ys,
        mode: 'markers',
        line: { dash: 'dot', width: 1 },
        showlegend: false,
        hoverinfo: 'x+y'
    }

    return [traceMarkers];
}