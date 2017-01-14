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

function loadProject(projectId) {
    var $body = $('body div#body');
    $.ajax({
        method: 'POST',
        url: paths.ajax_loadProject,
        data: { project_id: projectId },
        dataType: 'json'
    }).done(function( response ) {
        if(response.status) {
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
                        text: 'Ich war versteckt...',
                        id: change.id
                    }
                });
            });

            var table = createTableObject(
                'changeTable',
                ['', translations.label.title, translations.label.author, translations.label.date, ''],
                changeData,
                translations.no_entries_found
        );
            $body.html(table);
        }
    });
}

$(document).ready(function() {

    // Enable tooltips
    $('[data-toggle="tooltip"]').tooltip();

});

(function ($) {
    // Handling the modal confirmation message.
    $(document).on('submit', 'form[data-confirmation]', function (event) {
        var $form = $(this),
            $confirm = $('#confirmationModal');

        if ($confirm.data('result') !== 'yes') {
            //cancel submit event
            event.preventDefault();

            $confirm
                .off('click', '#btnYes')
                .on('click', '#btnYes', function () {
                    $confirm.data('result', 'yes');
                    $form.find('input[type="submit"]').attr('disabled', 'disabled');
                    $form.submit();
                })
                .modal('show');
        }
    });
})(window.jQuery);
