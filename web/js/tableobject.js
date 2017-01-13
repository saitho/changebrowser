function createTableObject(tableId, headArray, bodyArray, noResultsMessage) {
    var $this = this;
    $this.createHead = function(table, array, bodyArray) {
        var tr = document.createElement('tr');
        $(array).each(function(key, name) {
            var titleText = document.createTextNode(name);
            var titleTd = document.createElement('th');
            titleTd.appendChild(titleText);
            tr.appendChild(titleTd);
        });
        table.appendChild(tr);
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
            var text = document.createTextNode(value);
            td.appendChild(text);
        }
    };
    $this.createBody = function(table, headArray, bodyArray, noResultsMessage) {
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
            $(element).each(function(k, v) {
                var td = document.createElement('td');
                if(v === null) {
                    tr.appendChild(td);
                }else if(v.constructor === Array) {
                    // Concatinate values...
                    $(v).each(function(aK, aV) {
                        $this.addValueToTd(td, aV);
                    });
                    tr.appendChild(td);
                }else{
                    $this.addValueToTd(td, v);
                    tr.appendChild(td);
                }
            });
            table.appendChild(tr);
        });
    };

    var table = document.createElement('table');
    table.id = tableId;
    $this.createHead(table, headArray, bodyArray);
    $this.createBody(table, headArray, bodyArray, noResultsMessage);
    return table;
}