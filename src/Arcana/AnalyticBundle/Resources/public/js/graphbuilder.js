var graphBuilder = {
    graphs: [],
    addGraph: function(container, lines, labels) {
        var array = graphBuilder.convertLines(lines);
        var plot = $.jqplot(container, array, {
            title: 'Visitors',
            axes: {
                xaxis:{
                    tickOptions: {
                        formatString:'%#d-%m-%Y'
                    },
                    renderer: $.jqplot.DateAxisRenderer
                },
                yaxis:{
                    min: 0
                }
            },
            legend: {
                show: true
            },
            highlighter: {
                show: true,
                sizeAdjust: 7.5
            },
            series: [ // @todo this should be automated
                {
                    lineWidth: 4,
                    color: '#428bca',
                    label: 'Visits'
                }
            ]
        });
        graphBuilder.graphs.push(plot);
    },
    convertLines: function(lines) {
        var array = [];
        for (var current = 0; current < lines.length; current++) {
            array.push(graphBuilder.objectToArray(lines[current]));
        }

        return array;
    },
    objectToArray: function(lines) {
        var array = [];

        for(var date in lines) {
            var count = lines[date];

            array.push([date, count]);
        }

        return array;
    }
};