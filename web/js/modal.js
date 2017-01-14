function createModal(id, modalConfig) {
    this.header = function(contentDiv, modalConfig) {
        var headerDiv = document.createElement('div');
        headerDiv.className = 'modal-header';

        var closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'close';
        closeButton.dataset.dismiss = 'modal';
        $(closeButton).attr('aria-label', 'Close');
        var buttonContent = document.createElement('span');
        $(buttonContent).attr('aria-hidden', 'true');
        buttonContent.innerHTML = '&times;';
        closeButton.appendChild(buttonContent);
        headerDiv.appendChild(closeButton);

        if(modalConfig.header) {
            var titleHeader = document.createElement('h4');
            titleHeader.className = 'modal-title';
            var titleText = document.createTextNode(modalConfig.header);
            titleHeader.appendChild(titleText);
            headerDiv.appendChild(titleHeader);
        }

        contentDiv.appendChild(headerDiv);
    };
    this.body = function(contentDiv, modalConfig) {
        if(!modalConfig.content) {
            return;
        }
        var bodyDiv = document.createElement('div');
        bodyDiv.className = 'modal-body';
        bodyDiv.innerHTML = modalConfig.content;
        contentDiv.appendChild(bodyDiv);
    };
    this.footer = function(contentDiv, modalConfig) {
        var footerDiv = document.createElement('div');
        footerDiv.className = 'modal-footer';

        var hideCloseButton = false;
        var showSaveButton = false;
        if(modalConfig.footer) {
            if(modalConfig.footer.hideCloseButton) {
                hideCloseButton = true;
            }
            showSaveButton = modalConfig.footer.showSaveButton;
        }

        if(!hideCloseButton) {
            var closeButton = document.createElement('button');
            closeButton.type = 'button';
            closeButton.className = 'btn btn-default';
            closeButton.dataset.dismiss = 'modal';
            var closeButtonText = document.createTextNode('Close');
            closeButton.appendChild(closeButtonText);
            footerDiv.appendChild(closeButton);
        }

        if(showSaveButton) {
            var saveButton = document.createElement('button');
            saveButton.type = 'button';
            saveButton.className = 'btn btn-primary';
            var saveButtonText = document.createTextNode('Save changes');
            saveButton.appendChild(saveButtonText);
            footerDiv.appendChild(saveButton);
        }

        contentDiv.appendChild(footerDiv);
    };

    var $modal = $('div.modal#'+id);
    $modal.find('div').remove();

    // Create dialog
    var dialogDiv = document.createElement('div');
    dialogDiv.className = 'modal-dialog';
    dialogDiv.displayRole = 'document';

    // Create content
    var contentDiv = document.createElement('div');
    contentDiv.className = 'modal-content';

    // Modal contents:
    this.header(contentDiv, modalConfig);
    this.body(contentDiv, modalConfig);
    this.footer(contentDiv, modalConfig);

    dialogDiv.appendChild(contentDiv);
    $modal.append(dialogDiv);
}