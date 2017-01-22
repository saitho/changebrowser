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
    $body.rewatajax(options, { project_id: projectId }, 'body div#body');

    $('button#fetchdata-button').unbind('click');
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
});
