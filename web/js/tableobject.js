function createTableObject(tableOptions, headArray, bodyArray, noResultsMessage) {
    var $this = this;
    var tableId = tableOptions.id;
    if(!tableId) {
        return;
    }
    var tableClass = tableOptions.class;
    $this.createHead = function(table, array, bodyArray) {
        var thead = document.createElement('thead');
        var tr = document.createElement('tr');
        $(array).each(function(key, name) {
            var titleTh = document.createElement('th');
            var thValue = name;
            if(name.constructor === Object) {
                thValue = name.content;
                if(name.id) {
                    titleTh.id = name.id;
                }
                if(name.class) {
                    titleTh.className = name.class;
                }
                if(name.width) {
                    titleTh.width = name.width;
                }
            }
            titleTh.innerText = thValue;
            tr.appendChild(titleTh);
        });
        thead.appendChild(tr);
        table.appendChild(thead);
    };

    /**
     * Returns true if it is a DOM element
     * http://stackoverflow.com/questions/384286/javascript-isdom-how-do-you-check-if-a-javascript-object-is-a-dom-object
     * @param o object
     * @returns bool
     */
    $this.isElement = function(o){
        return (
            typeof HTMLElement === 'object' ? o instanceof HTMLElement : //DOM2
                o && typeof o === 'object' && o !== null && o.nodeType === 1 && typeof o.nodeName==='string'
        );
    };
    $this.addValueToTd = function(td, value) {
        if(isElement(value)) {
            td.appendChild(value);
        }else{
            var tdValue = value;
            if(value.constructor === Object) {
                tdValue = value.content;
                if(value.id) {
                    td.id = value.id;
                }
                if(value.class) {
                    td.className = value.class;
                }
            }
            td.innerHTML = tdValue;
        }
    };
    $this.createBody = function(table, headArray, bodyArray, noResultsMessage) {
        var tbody = document.createElement('tbody');
        if(bodyArray.length === 0) {
            if(noResultsMessage !== null) {
                var tr = document.createElement('tr');
                var td = document.createElement('td');
                td.colSpan = headArray.length;
                td.className = 'no-results';
                var text = document.createTextNode(noResultsMessage);
                td.appendChild(text);
                tr.appendChild(td);
                table.appendChild(tr);
            }
            return;
        }
        $(bodyArray).each(function(key, element) {
            var tr = document.createElement('tr');
            $(element.columns).each(function(k, v) {
                var td = document.createElement('td');
                if(v == null) {
                    tr.appendChild(td);
                }else {
                    $this.addValueToTd(td, v);
                    tr.appendChild(td);
                }
            });
            tbody.appendChild(tr);
            if(element.additionalFullWidthRow != null) {
                tr = document.createElement('tr');
                if(!element.additionalFullWidthRow.id) {
                    element.additionalFullWidthRow.id = '';
                }
                tr.className = 'hidden-row hidden-row-'+element.additionalFullWidthRow.id;
                tr.style.display = 'none';
                var td = document.createElement('td');
                td.colSpan = headArray.length;
                if(element.additionalFullWidthRow.html) {
                    if($this.isElement(element.additionalFullWidthRow.html)) {
                        elementsFromHtml = element.additionalFullWidthRow.html;
                    }else{
                        var div = document.createElement('div');
                        div.innerHTML = element.additionalFullWidthRow.html;
                        var elementsFromHtml = div.firstChild.cloneNode(true);
                    }
                    td.appendChild(elementsFromHtml);
                }else{
                    var text = document.createTextNode(element.additionalFullWidthRow.text);
                    td.appendChild(text);
                }
                tr.appendChild(td);
                tbody.appendChild(tr);
            }
        });
        table.appendChild(tbody);
    };

    var table = document.createElement('table');
    table.id = tableId;
    table.className = tableClass;
    $this.createHead(table, headArray, bodyArray);
    $this.createBody(table, headArray, bodyArray, noResultsMessage);
    return table;
}