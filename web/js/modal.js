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

        if(modalConfig.footer) {
            if(modalConfig.footer.buttons) {
                $.each(modalConfig.footer.buttons, function(buttonId, buttonOptions) {
                    var button = document.createElement('button');
                    button.type = 'button';
                    button.id = buttonId;

                    switch(buttonOptions.type) {
                        case 'close':
                            button.dataset.dismiss = 'modal';
                            break;
                        case 'submit':
                            button.setAttribute('type', 'submit');
                            button.setAttribute('form', buttonOptions.submitForm);
                            break;
                        default:
                            // submit
                            break;
                    }

                    if(buttonOptions.class) {
                        button.className = buttonOptions.class;
                    }
                    button.innerText = buttonOptions.text;
                    footerDiv.appendChild(button);
                });
            }
        }

        contentDiv.appendChild(footerDiv);
    };

    var $modal = $('div.modal#'+id);
    $modal.find('div').remove();

    // Create dialog
    var dialogDiv = document.createElement('div');
    dialogDiv.className = 'modal-dialog modal-lg';
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