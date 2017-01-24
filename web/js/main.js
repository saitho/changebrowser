var $body = $('body div#body');
function toggleDetails(changeId) {
    var $targetRow = $('tr.hidden-row-'+changeId);
    var $togglerIcon = $('i#dropDown-activator-'+changeId);
    if($targetRow.css('display') === 'none') {
        $targetRow.show();
        $togglerIcon.removeClass('fa-angle-down');
        $togglerIcon.addClass('fa-angle-up');
    }else{
        $targetRow.hide();
        $togglerIcon.removeClass('fa-angle-up');
        $togglerIcon.addClass('fa-angle-down');
    }
}

var modalId = 'universalModal';
var currently_loaded_project = null;


function rewatajaxParseBodyData(response, header_data) {
    var changeData = [];
    $(response).each(function(key, change) {
        var subTable = createTableObject(
            {id: 'subtable-'+change.id, class: 'table table-bordered changecontent-table'},
            change.changeContents_head,
            change.changeContents_content
        );

        var fieldIndex = [];
        for(var changeKey in change) {
            if(header_data[changeKey]) {
                if(change[changeKey] == '' || change[changeKey] == undefined) {
                    continue;
                }
            }
            fieldIndex[changeKey] = change[changeKey];
        }

        var columns = [];
        for(var headerKey in header_data) {
            var headerValueType = header_data[headerKey].type;
            var headerValueTransform = header_data[headerKey].transform;
            var originalFieldValue = fieldIndex[headerKey];

            //if(headerValueTransform != '' && headerValueTransform != undefined) {
            var transformedText = '';
            switch(headerValueType) {
                case 'date':
                    transformedText = new Date(originalFieldValue.date).toLocaleString();
                    break;
                default:
                    transformedText = headerValueTransform;
                    if(transformedText && transformedText.match('!_self')) {
                        if(originalFieldValue == undefined || !originalFieldValue) {
                            transformedText = '';
                            break;
                        }else{
                            transformedText = transformedText.replace('!_self', originalFieldValue);
                        }
                    }
                    if(transformedText && transformedText != '') {
                        for(var replaceKey in fieldIndex) {
                            transformedText = transformedText.replace('!'+replaceKey, fieldIndex[replaceKey]);
                        }
                    }else{
                        transformedText = change[headerKey];
                    }
                    break;
            }
            columns.push(transformedText);
        }

        changeData.push({
            columns: columns,
            additionalFullWidthRow: {
                html: subTable,
                id: change.id
            }
        });
    });
    return changeData;
}

function submitForm(url, form, modal) {
    $.ajax({
        method: $(form).attr('method'),
        url: url,
        data: { formData: $(form).serialize() },
        dataType: 'json'
    }).done(function( response ) {
        if(response.status) {
            if(modal) {
                modal.modal('hide');
                var option = document.createElement('option');
                option.value = response.id;
                option.innerText = response.title;
                $('select#projectList').append(option);
            }
        }
    });
}

function flashMessage(message, type) {
    if(type == undefined) {
        type = 'info';
    }
    $.bootstrapGrowl(message, {
        ele: 'body', // which element to append to
        type: type, // (null, 'info', 'error', 'success')
        offset: {from: 'top', amount: 10}, // 'top', or 'bottom'
        align: 'center', // ('left', 'right', or 'center')
        width: 'auto', // (integer, or 'auto')
        delay: 4000,
        allow_dismiss: false,
        newest_on_top: true,
        stackup_spacing: 10 // spacing between consecutively stacked growls.
    });
}

function format(str, arr) {
    return str.replace(/%(\d+)/g, function(_,m) {
        return arr[--m];
    });
}

var graphMode = 'day';
//graphMode = 'month';

function loadProject(projectId) {
    currently_loaded_project = projectId;
    var options = {
        id: 'changeTable',
        ajax_connector: paths.ajax_loadProject,
        pager_max_buttons: 15,
        language: {
            dateFilter_start: Translator.trans('rewatajax.dateFilter_start'),
            dateFilter_end: Translator.trans('rewatajax.dateFilter_end'),
            filter: Translator.trans('rewatajax.filter'),
            search_results: Translator.trans('rewatajax.search_results'),
            search_text: Translator.trans('rewatajax.search_text'),
            no_entries_message: Translator.trans('rewatajax.no_entries_message')
        }
    };

    $.ajax({
        method: 'GET',
        url: paths.ajax_loadProject,
        data: { project_id: projectId },
        dataType: 'json'
    }).done(function( response ) {
        if(response.status) {
            var header_data = response.header;
            var response_options = response.options;
            var body_data = rewatajaxParseBodyData(response.body_data, header_data);
            var staticResult = {header_data: header_data, body_data: body_data, response_options: response_options};
            $body.rewatajax(options, { project_id: projectId }, 'body div#body', staticResult);

            var statistic = response.statistics;
            var datasets = statistic.datasets;
            if(graphMode == 'month') {
                datasets = statistic.datasetByMonth;
            }
            var xAxisLabels = statistic.xAxisLabels;
            refreshGraph(xAxisLabels, datasets);
        }
    });
}

function refreshGraph(monthLabels, datasets) {
    window.chartColors = {
        feature: 'rgb(255, 99, 132)',
        task: 'rgb(255, 159, 64)',
        bugfix: 'rgb(255, 205, 86)',
        cleanup: 'rgb(75, 192, 192)',
        _none: 'rgb(255,255,255)'
    };

    var datasetConfig = [];
    for(var tag in datasets) {
        datasetConfig.push({
            label: tag,
            borderWidth: 0.5,
            borderColor: '#222',
            backgroundColor: window.chartColors[tag],
            data: Object.values(datasets[tag])
        });
    }

    var config = {
        type: 'line',
        data: {
            labels: monthLabels,
            datasets: datasetConfig
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            title:{
                display: true,
                text:"Chart.js Line Chart - Stacked Area"
            },
            tooltips: {
                mode: 'index',
                intersect: false
            },
            hover: {
                mode: 'index'
            },
            scales: {
                xAxes: [{
                    gridLines: {
                        display: false
                    },
                    ticks: {
                        beginAtZero: true,
                        min: monthLabels[0],
                        max: monthLabels[monthLabels.length - 1]
                    },
                    scaleLabel: {
                        display: true,
                        labelString: 'Month'
                    }
                }],
                yAxes: [{
                    ticks: {
                        display: false,
                        beginAtZero: true,
                        min: 0,
                        stepSize: 1
                    },
                    stacked: true,
                    scaleLabel: {
                        display: true,
                        labelString: 'Changes'
                    }
                }]
            }
        }
    };

    if(window.myLineChart != null) {
        window.myLineChart.destroy();
    }

    var ctx = document.getElementById("canvas").getContext("2d");
    window.myLineChart = new Chart(ctx, config);



    canvas.onclick = function(evt){
        var activePoints = myLineChart.getElementsAtEvent(evt);
        if(activePoints[0]) {
            var index = activePoints[0]._index;
            console.log(monthLabels[index]);
        }
    };


    // Hook into main event handler
    var parentEventHandler = Chart.Controller.prototype.eventHandler;
    Chart.Controller.prototype.eventHandler = function() {
        var ret = parentEventHandler.apply(this, arguments);

        this.clear();
        this.draw();

        var yScale = this.scales['y-axis-0'];

        // Draw the vertical line here
        var eventPosition = Chart.helpers.getRelativePosition(arguments[0], this.chart);
        this.chart.ctx.beginPath();
        this.chart.ctx.moveTo(eventPosition.x, yScale.getPixelForValue(yScale.max));
        this.chart.ctx.strokeStyle = "#ff0000";
        this.chart.ctx.lineTo(eventPosition.x, yScale.getPixelForValue(yScale.min));
        this.chart.ctx.stroke();

        return ret;
    };
}

$(document).ready(function() {
    // Enable tooltips
    $('[data-toggle="tooltip"]').tooltip();

    $('button#button-project-add').click(function() {
        $.ajax({
            method: 'GET',
            url: paths.ajax_project_add,
            data: {  },
            dataType: 'json'
        }).done(function( response ) {
            if(response.status) {
                var modalConfig = {
                    header: response.modal.header,
                    content: response.modal.content,
                    footer: {
                        buttons: {
                            closeButton: {
                                type: 'close',
                                class: 'btn btn-default',
                                text: 'Close'
                            },
                            saveButton: {
                                type: 'submit',
                                submitForm: 'projectForm',
                                class: 'btn btn-primary',
                                text: 'Save changes'
                            }
                        }
                    }
                };
                createModal(modalId, modalConfig);
                var $modal = $('div#'+modalId);
                $modal.modal('show');

                $('form#projectForm').submit(function( event ) {
                    event.preventDefault();
                    submitForm(url, this, $modal);
                });
            }
        });
    });
    $('button#button-project-details').click(function() {
        $.ajax({
            method: 'GET',
            url: paths.ajax_project_details,
            data: { project_id: currently_loaded_project },
            dataType: 'json'
        }).done(function( response ) {
            if(response.status) {
                var modalConfig = {
                    header: response.modal.header,
                    content: response.modal.content,
                    footer: {
                        showSaveButton: true
                    }
                };
                createModal(modalId, modalConfig);
                $('div#'+modalId).modal('show');
            }
        });
    });
    $('button#fetchdata-button').click(function() {
        var projectId = currently_loaded_project;
        var $projectBarButtons = $('div#projectBar button');
        var $projectBarSelects = $('div#projectBar select');
        if($projectBarButtons.hasClass('disabled')) {
            console.log('Already working.');
        }else{
            $.ajax({
                method: 'POST',
                url: paths.ajax_cli_fetchData,
                data: { project_id: projectId },
                dataType: 'json',
                beforeSend: function(xhr) {
                    $projectBarButtons.addClass('disabled');
                    $projectBarSelects.attr('disabled', true);
                },
                error: function(xhr, textStatus, errorThrown) {
                    flashMessage(textStatus, 'error');
                },
                complete: function() {
                    $projectBarButtons.removeClass('disabled');
                    $projectBarSelects.attr('disabled', false);
                }
            }).done(function( response ) {
                var type = 'error';
                if(response.status) {
                    type = 'success';
                }
                var changes_count = response.changes_count;

                var text = Translator.trans('cli.no_new_changes_found');
                if(changes_count > 0) {
                    text = format(Translator.trans('cli.changes_fetched'), [changes_count]);
                    loadProject(projectId);
                }

                flashMessage(text, type);
            });
        }
    });
});
