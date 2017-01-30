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

function submitForm(url, form, modal, callback) {
    $.ajax({
        method: $(form).attr('method'),
        url: url,
        data: { formData: $(form).serialize() },
        dataType: 'json'
    }).done(function( response ) {
        if(response.status) {
            if(callback) {
                callback(response, modal, form);
            }
        }
    });
}

function flashMessage(message, type, manualClose) {
    if(type == undefined) {
        type = 'info';
    }

    var options = {
        allow_dismiss: false,
        element: 'body',
        type: type, // (null, 'info', 'error', 'success')
        offset: 10,
        placement: {
            from: 'top',
            align: 'center'
        },
        delay: 4000,
        newest_on_top: true
    };

    if(manualClose) {
        options.allow_dismiss = true;
        options.delay = 0;
    }

    $.notify({message: message}, options);
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
            if(!response.flags.hasChanges) {
                flashMessage(
                    Translator.trans('text.warning.no_changes', {project_id: projectId}),
                    'warning',
                    true
                );
            }else if(!response.flags.hasCompleteChanges) {
                flashMessage(
                    Translator.trans('text.warning.incomplete_changes', {project_id: projectId}),
                    'warning',
                    true
                );
            }
            refreshGraph(response.statistics.xAxisLabels, response.statistics.datasets);
        })
        .on('rewatajax.createSearch', function(e, search_div) {
            var inputGroup = document.createElement('div');
            inputGroup.className = 'input-group col-md-8';

            var inputGroupBtn = document.createElement('div');
            inputGroupBtn.className = 'input-group-btn';

            var button = document.createElement('button');
            button.id = 'export-changes';
            button.setAttribute('data-toggle', 'tooltip');
            button.setAttribute('data-placement', 'top');
            button.setAttribute('aria-haspopup', 'true');
            button.setAttribute('aria-expanded', 'false');
            button.title = Translator.trans('label.export_changelog');

            button.className = 'btn btn-xs btn-success';
            var i = document.createElement('i');
            i.className = 'fa fa-file-text';
            button.appendChild(i);
            inputGroupBtn.appendChild(button);
            inputGroup.appendChild(inputGroupBtn);

            search_div.appendChild(inputGroup);

            var exportFunction = function() {
                var url = paths.ajax_change_export;
                $.ajax({
                    method: 'GET',
                    url: url,
                    data: {project_id: currently_loaded_project},
                    dataType: 'json'
                }).done(function (response) {
                    if (response.status) {
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
                                        submitForm: 'exportForm',
                                        class: 'btn btn-primary',
                                        text: 'Export'
                                    }
                                }
                            }
                        };
                        var $modal = $('div#'+modalId);
                        $modal.createModal(modalConfig);
                        $modal.modal('show');
                    }
                });
            };
            $body.unbind('click', exportFunction);
            $body.on('click', 'button#export-changes', exportFunction);
        });
    rewatajax.init();

    var infoIcon = function(text) {
        return '<i class="fa fa-info" title="'+text+'" data-toggle="tooltip" data-placement="right"></i>';
    };

    var changeDetailsFunction = function() {
        var changeId = $(this).data('id');
        var containerName = 'changeDetails-container-'+changeId;

        var originalTitleInfo = '';
        var originalTitle = $(this).data('title');
        var editedTitle = $(this).data('editedtitle');
        var title = originalTitle;
        if(editedTitle != '') {
            originalTitleInfo = Translator.trans('label.originalTitle')+': '+title;
            title = editedTitle;
        }

        var content = '<div class="form-group">' +
            '<label for="titleInput">'+Translator.trans('label.changeTitle')+'</label>' +
            '<input type="text" class="form-control" id="titleInput" required value="'+title+'">' +
            '<small class="form-text text-muted" id="titleInput-info">'+originalTitleInfo+'</small>' +
        '</div><hr />' +
            '<div id="'+containerName+'"></div>';

        var modalConfig = {
            header: Translator.trans('title.changeDetails'),
            content: content,
            footer: {
                buttons: {
                    closeButton: {
                        type: 'close',
                        class: 'btn btn-default',
                        text: 'Close'
                    },
                    'changeDetails-save': {
                        type: 'submit',
                        class: 'btn btn-primary',
                        text: 'Save changes'
                    }
                }
            }
        };
        var $modal = $('div#'+modalId);
        $modal.createModal(modalConfig);
        $modal.modal('show');

        var saveButton = $('button#changeDetails-save');
        saveButton.unbind('click');
        saveButton.click(function() {
           var titleInput = $('div.modal-body input#titleInput').val();
           var refreshTitle = titleInput;
           var addNote = true;
            if(titleInput == editedTitle) {
                return;
            }else if(titleInput == originalTitle) {
                titleInput = '';
                refreshTitle = originalTitle;
                addNote = false;
            }

            // Ajax request
            $.ajax({
                method: 'POST',
                url: paths.ajax_change_details,
                data: { change_id: changeId, edited_title: titleInput },
                dataType: 'json'
            }).done(function( response ) {
                if(response.status == true) {
                    var titleField = $('button.changeDetailsButton[data-id='+changeId+']')
                        .parents('tr.rewatajax-row')
                        .find('td.rewatajax-column[data-type="showTitle"]');
                    titleField.text(refreshTitle);

                    var infoField = $('div.modal-body small#titleInput-info');
                    infoField.html('');
                    if(addNote) {
                        var text = Translator.trans('label.originalTitle')+': '+originalTitle;
                        console.log(text);
                       titleField.append(' '+infoIcon(text));
                       infoField.html(text);
                    }
                }
            });
        });

        var options = {
            id: 'detailTable',
            ajax_connector: paths.ajax_change_details,
            pager_max_buttons: 15,
            search: false,
            language: {
                dateFilter_range: Translator.trans('rewatajax.dateFilter_range'),
                filter: Translator.trans('rewatajax.filter'),
                search_results: Translator.trans('rewatajax.search_results'),
                search_text: Translator.trans('rewatajax.search_text'),
                no_entries_message: Translator.trans('rewatajax.no_entries_message')
            },
            format: {
                dateISO: Translator.trans('lang.dateISO')
            }
        };
        var rewatajaxDetails = $('div#'+containerName).rewatajax(options, { change_id: changeId });
        rewatajaxDetails.init();
    };
    $body.unbind('click', changeDetailsFunction);
    $body.on('click', 'button.changeDetailsButton', changeDetailsFunction);
}

function refreshGraph(monthLabels, datasets) {
    window.chartColors = {
        feature: 'rgb(91,192,222)',
        task: 'rgb(99,108,114)',
        bugfix: 'rgb(217,83,79)',
        cleanup: 'rgb(240,173,78)',
        undefined: 'rgb(214,214,214)'
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
                position: 'bottom',
                labels: {
                    generateLabels: function(chart) {
                        labels = Chart.defaults.global.legend.labels.generateLabels(chart);
                        for(var i in labels) {
                            labels[i].text = Translator.trans('tag.'+labels[i].text);
                        }
                        return labels;
                    }
                }
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
    $('body').tooltip({
        selector: '[data-toggle="tooltip"]'
    });

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
                var $modal = $('div#'+modalId);
                $modal.createModal(modalConfig);
                $modal.modal('show');

                $('form#projectForm').submit(function( event ) {
                    event.preventDefault();
                    submitForm(url, this, $modal, function(response) {
                        if(response) {
                            modal.modal('hide');
                            var option = document.createElement('option');
                            option.value = response.id;
                            option.innerText = response.title;
                            $('select#projectList').append(option);
                        }
                    });
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
                var $modal = $('div#'+modalId);
                $modal.createModal(modalConfig);
                $modal.modal('show');
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
