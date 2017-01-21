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
function currentProjectDetails(keepModalHidden) {
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
            if(!keepModalHidden) {
                $('div#'+modalId).modal('show');
            }
        }
    });
}

function submitForm(url, form) {
    $.ajax({
        method: $(form).attr('method'),
        url: url,
        data: { formData: $(form).serialize() },
        dataType: 'json'
    }).done(function( response ) {

    });
}

function addProject(keepModalHidden) {
    var url = paths.ajax_project_add;
    $.ajax({
        method: 'GET',
        url: url,
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
            if(!keepModalHidden) {
                $modal.modal('show');
            }

            $('form#projectForm').submit(function( event ) {
                event.preventDefault();
                submitForm(url, this);
            });

           // var saveButton = $modal.find('div.modal-footer > button#saveButton');
           // saveButton.click(function() {
           //     console.log('clicked save button');
           //     submitForm(url, $modal.find('form[name=projectForm]'));
           // });
        }
    });
}

function loadProject(projectId) {
    var $body = $('body div#body');
    currently_loaded_project = projectId;
    $body.rewatajax({id: 'changeTable', ajax_connector: paths.ajax_loadProject}, { project_id: projectId }, 'body div#body');
    return;


    $.ajax({
        method: 'GET',
        url: paths.ajax_loadProject,
        data: { project_id: projectId },
        dataType: 'json'
    }).done(function( response ) {
        var headerData = response.header;

        if(response.status) {
            currently_loaded_project = projectId;
            var changeData = [];
            var responseOptions = response.options;
            $(response.changes).each(function(key, change) {
                var subTable = createTableObject(
                    {id: 'subtable-'+change.id, class: 'table table-responsive table-bordered changecontent-table'},
                    change.changeContents_head,
                    change.changeContents_content
                );

                var fieldIndex = [];
                for(var changeKey in change) {
                    if(headerData[changeKey]) {
                        if(change[changeKey] == '' || change[changeKey] == undefined) {
                            continue;
                        }
                    }
                    fieldIndex[changeKey] = change[changeKey];
                }

                var columns = [];
                for(headerKey in headerData) {
                    var headerValueTransform = headerData[headerKey].transform;
                    var originalFieldValue = fieldIndex[headerKey];

                    if(headerValueTransform != '' && headerValueTransform != undefined) {
                        var transformedText = '';
                        switch(headerValueTransform) {
                            case 'date':
                                transformedText = new Date(originalFieldValue.date).toLocaleString();
                                break;
                            default:
                                transformedText = headerValueTransform;
                                if(transformedText.match('!_self')) {
                                    if(originalFieldValue == undefined || !originalFieldValue) {
                                        break;
                                    }else{
                                        transformedText = transformedText.replace('!_self', originalFieldValue);
                                    }
                                }
                                if(transformedText != '') {
                                    for(var replaceKey in fieldIndex) {
                                        transformedText = transformedText.replace('!'+replaceKey, fieldIndex[replaceKey]);
                                    }
                                }
                                break;
                        }
                        columns.push(transformedText);
                    }else{
                        columns.push(change[headerKey]);
                    }
                }

                changeData.push({
                    columns: columns,
                    additionalFullWidthRow: {
                        html: subTable,
                        id: change.id
                    }
                });
            });

            var table = createTableObject(
                {id: 'changeTable'},
                headerData,
                changeData,
                Translator.trans('no_entries_found'),
                responseOptions
        );
            $body.html(table);
        }
    });
}

$(document).ready(function() {
    // Enable tooltips
    $('[data-toggle="tooltip"]').tooltip();
});
