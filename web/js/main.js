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
                            var expression = '!_self';
                            transformedText = transformedText.replace(new RegExp(expression, 'g'), originalFieldValue);
                        }
                    }
                    if(transformedText && transformedText != '') {
                        for(var replaceKey in fieldIndex) {
                            expression = '!'+replaceKey;
                            transformedText = transformedText.replace(new RegExp(expression, 'g'), fieldIndex[replaceKey]);
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


function loadProject(projectId) {
    currently_loaded_project = projectId;
    var today = new Date();
    var dd = today.getDate();
    var mm = today.getMonth()+1; //January is 0!
    var yyyy = today.getFullYear();
    var options = {
        id: 'changeTable',
        ajax_connector: paths.ajax_loadProject,
        pager_max_buttons: 15,
        language: {
            dateFilter_range: Translator.trans('rewatajax.dateFilter_range'),
            filter: Translator.trans('rewatajax.filter'),
            search_results: Translator.trans('rewatajax.search_results'),
            search_text: Translator.trans('rewatajax.search_text'),
            no_entries_message: Translator.trans('rewatajax.no_entries_message')
        },
        daterangepicker: {
            maxDate: dd+'.'+mm+'.'+yyyy,
            locale: {
                format: Translator.trans('lang.dateISO'),
                cancelLabel: 'Clear',
                applyLabel: 'Apply',
                fromLabel: 'From',
                toLabel: 'To',
                customRangeLabel: 'Custom',
                weekLabel: 'W',
                daysOfWeek: [
                    'Su',
                    'Mo',
                    'Tu',
                    'We',
                    'Th',
                    'Fr',
                    'Sa'
                ],
                monthNames: [
                    'January',
                    'February',
                    'March',
                    'April',
                    'May',
                    'June',
                    'July',
                    'August',
                    'September',
                    'October',
                    'November',
                    'December'
                ]
            },
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        },
        format: {
            dateISO: Translator.trans('lang.dateISO')
        }
    };

    // cleanup event watchers
    $body.unbind('rewatajax.callConnector');
    $body.unbind('rewatajax.createSearch');

    var rewatajax = $body.rewatajax(options, { project_id: projectId })
        .on('rewatajax.callConnector', function(e, response) {
            refreshGraph(response.statistics.xAxisLabels, response.statistics.datasets);
        })
        .on('rewatajax.createSearch', function(e, search_div) {
            var inputGroup = document.createElement('div');
            inputGroup.className = 'input-group col-md-8';

            var inputGroupBtn = document.createElement('div');
            inputGroupBtn.className = 'input-group-btn';

            var a = document.createElement('a');
            a.setAttribute('data-toggle', 'tooltip');
            a.setAttribute('data-placement', 'top');
            a.setAttribute('aria-haspopup', 'true');
            a.setAttribute('aria-expanded', 'false');
            a.title = Translator.trans('label.export_changelog');

            a.className = 'btn btn-xs btn-success';
            a.href = 'javascript:;';
            var i = document.createElement('i');
            i.className = 'fa fa-file-text';
            a.appendChild(i);
            inputGroupBtn.appendChild(a);
            inputGroup.appendChild(inputGroupBtn);

            search_div.appendChild(inputGroup);
        });
    rewatajax.init();
}

function refreshGraph(monthLabels, datasets) {
    window.chartColors = {
        feature: 'rgb(91,192,222)',
        task: 'rgb(99,108,114)',
        bugfix: 'rgb(217,83,79)',
        cleanup: 'rgb(240,173,78)',
        _none: 'rgb(230,230,230)'
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
                display: false
            },
            tooltips: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    afterBody : function(tooltipItems, data) {
                        var total = 0;
                        for(var i in tooltipItems) {
                            total += tooltipItems[i].yLabel;
                        }
                        return '--- '+Translator.trans('label.graphTooltip.total')+': '+total;
                    }
                }
            },
            hover: {
                mode: 'index'
            },
            legend: {
                position: 'bottom'
            },
            scales: {
                xAxes: [{
                    type: 'time',
                    time: {
                        displayFormats: {
                            'day': Translator.trans('lang.dateISO')
                        }
                    },
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

    if(1 < datasetConfig.length) {
        window.myLineChart = new Chart(ctx, config);

        canvas.onclick = function(evt){
            var activePoints = myLineChart.getElementsAtEvent(evt);
            if(activePoints[0]) {
                var index = activePoints[0]._index;
                var filterLink = $('a#filter-date');
                filterLink.popover("show");

                // needs zero timeout in order to access the data property
                window.setTimeout(function () {
                    var popoverId = filterLink.attr('aria-describedby');
                    var $input = $('div#'+popoverId+' > div.popover-content > input#date-dateFilter-range');
                    var drp = $input.data('daterangepicker');
                    var date = new Date(monthLabels[index]);
                    drp.setStartDate(date);
                    drp.setEndDate(date);
                    drp.clickApply();
                }, 0);
            }
        };

        // Sets a vertical red line in the graph that moves with the mouse position
        // line is hidden when hovering data points
     //   var parentEventHandler = Chart.Controller.prototype.eventHandler;
     //   Chart.Controller.prototype.eventHandler = function() {
     //       var ret = parentEventHandler.apply(this, arguments);
     //       this.clear();
     //       this.draw();
     //       var yScale = this.scales['y-axis-0'];
     //       // Draw the vertical line here
     //       var eventPosition = Chart.helpers.getRelativePosition(arguments[0], this.chart);
     //       this.chart.ctx.beginPath();
     //       this.chart.ctx.moveTo(eventPosition.x, yScale.getPixelForValue(yScale.max));
     //       this.chart.ctx.strokeStyle = "#ff0000";
     //       this.chart.ctx.lineTo(eventPosition.x, yScale.getPixelForValue(yScale.min));
     //       this.chart.ctx.stroke();
     //       return ret;
     //   };
    } else {
    }
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
                        buttons: {
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
