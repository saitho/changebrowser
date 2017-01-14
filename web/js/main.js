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

var currently_loaded_project = null;
function currentProjectDetails(keepModalHidden) {
    $.ajax({
        method: 'POST',
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
            var modalId = 'myModal';
            createModal(modalId, modalConfig);
            if(!keepModalHidden) {
                $('div#'+modalId).modal('show');
            }
        }
    });
}

function addProject(keepModalHidden) {
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
                    showSaveButton: true
                }
            };
            var modalId = 'myModal';
            createModal(modalId, modalConfig);
            if(!keepModalHidden) {
                $('div#'+modalId).modal('show');
            }
        }
    });
}

function loadProject(projectId) {
    var $body = $('body div#body');
    $.ajax({
        method: 'POST',
        url: paths.ajax_loadProject,
        data: { project_id: projectId },
        dataType: 'json'
    }).done(function( response ) {
        if(response.status) {
            currently_loaded_project = projectId;
            var changeData = [];
            $(response.changes).each(function(key, change) {
                var span = null;
                if(change.type) {
                    span = document.createElement('span');
                    span.className = 'label '+change.CSSClassForType;
                    var text = document.createTextNode(change.type);
                    span.appendChild(text);
                }

                var detailLink = document.createElement('a');
                var iElement = document.createElement('i');
                iElement.id = 'dropDown-activator-'+change.id;
                iElement.className = 'fa fa-angle-down';
                detailLink.appendChild(iElement);
                detailLink.setAttribute('href', 'javascript:toggleDetails(\''+change.id+'\');');
                detailLink.className = 'pull-right btn btn-xs btn-primary';

                changeData.push({
                    columns: [
                        span, change.title, change.author, new Date(change.date.date).toLocaleString(), detailLink
                    ],
                    additionalFullWidthRow: {
                        html: change.detailHTML,
                        id: change.id
                    }
                });
            });

            var table = createTableObject(
                'changeTable',
                [
                    '',
                    Translator.trans('label.title'),
                    Translator.trans('label.author'),
                    Translator.trans('label.date'),
                    ''
                ],
                changeData,
                Translator.trans('no_entries_found')
        );
            $body.html(table);
        }
    });
}

$(document).ready(function() {
    // Enable tooltips
    $('[data-toggle="tooltip"]').tooltip();
});
