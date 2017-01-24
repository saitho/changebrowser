// todo: define public function rewatajaxParseBodyData for standalone release

(function($) {
    $.fn.extend({
        rewatajax: function(options, connectorData, outputToDiv, staticResult) {
            var defaults = {
                table_class: '',
                sort_by: '',
                sort_mode: '',
                current_page: 1,
                per_page: 30,
                pager_max_buttons: 5,
                ajax_connector: '/ajax.php',
                search: '',
                search_timeout: 300,
                language: {
                    filter: 'Filter',
                    search_text: 'Search...',
                    search_results: '%1 results found.',
                    no_entries_message: 'No results found.',
                    dateFilter_start: 'Start date',
                    dateFilter_end: 'End date'
                }
            };
            options = $.extend(defaults, options);

            var $this = this;
            var header_data = null;
            var body_data = null;
            var response_options = null;

            var cached_header = null;
            var searchTimeout = null;

            var tableId = options.id;
            if(!tableId) {
                return;
            }
            var allSortableSelector = 'table#'+tableId+' > thead > tr > th[data-sort-by]';
            var allFilterableSelector = 'table#'+tableId+' > thead > tr > th[data-filterable]';
            var tableClass = options.table_class;

            $this.handleConnectorResponse = function(headerData, bodyData, responseOptions) {
                header_data = headerData;
                body_data = bodyData;
                response_options = responseOptions;
                $this.updatePager();
            };
            $this.callConnector = function(callWhenFinished) {
                connectorData['sort_by'] = options.sort_by;
                connectorData['sort_mode'] = options.sort_mode;
                connectorData['per_page'] = options.per_page;
                connectorData['current_page'] = options.current_page;
                connectorData['search'] = options.search;

                if(staticResult != undefined) {
                    header_data = staticResult.header_data;
                    body_data = staticResult.body_data;
                    response_options = staticResult.response_options;
                    $this.handleConnectorResponse(header_data, body_data, response_options, callWhenFinished);
                    callWhenFinished();
                }else{
                    $.ajax({
                        method: 'GET',
                        url: options.ajax_connector,
                        data: connectorData,
                        dataType: 'json'
                    }).done(function( response ) {
                        if(response.status) {
                            var _body_data = rewatajaxParseBodyData(response.body_data, response.header);
                            $this.handleConnectorResponse(response.header, _body_data, response.options, callWhenFinished);
                            callWhenFinished();
                        }
                    });
                }
            };

            $this.searchKeyUp = function (string) {
                var _this = this;
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function () {
                    _this.search(string);
                }, options.search_timeout);
            };
            this.search = function (string) {
                options.current_page = 1;
                options.search = string;
                $this.callConnector(function() { $this.updateContent(); });
            };

            $this.getFilterForm = function(key) {
                var type = header_data[key].type;
                var form = '';
                switch(type) {
                    case 'date':
                        var datePickerStart = +type+"-datepicker-start";
                        var datePickerEnd = +type+"-datepicker-end";
                        form = "<div class='form-group'>";
                            form += "<div class='input-group date datepicker' id='"+datePickerStart+"'>";
                                form += "<label for='"+key+"-dateFilter-start'>"+options.language.dateFilter_start+"</label>";
                                form += "<input class='form-control' name='"+key+"-dateFilter-start' id='"+key+"-dateFilter-start' />";
                                form += "<span class='input-group-addon' data-action='showDatepicker' data-id='"+key+"-dateFilter-start'><span class='fa fa-calendar'></span></span>";
                            form += "</div>";
                            form += "<div class='input-group date datepicker' id='"+datePickerEnd+"'>";
                                form += "<label for='"+key+"-dateFilter-start'>"+options.language.dateFilter_end+"</label>";
                                form += "<input class='form-control' name='"+key+"-dateFilter-end' id='"+key+"-dateFilter-end' />";
                                form += "<span class='input-group-addon' data-action='showDatepicker' data-id='"+key+"-dateFilter-end'><span class='fa fa-calendar'></span></span>";
                            form += "</div>";
                        form += "</div>";
                        break;
                }
                return form;
            };

            $this.getHead = function() {
                var thead = document.createElement('thead');
                var tr = document.createElement('tr');

                for(var key in header_data) {
                    var name = header_data[key];
                    var titleTh = document.createElement('th');
                    var thValue = name;
                    if(name.constructor === Object) {
                        thValue = name.content;
                        if(name.id) {
                            titleTh.id = name.id;
                        }
                        var thClass = '';
                        if(name.class) {
                            thClass += name.class;
                        }
                        if(name.width) {
                            titleTh.width = name.width;
                        }
                        if(name.sortable) {
                            titleTh.dataset.sortBy = key;
                            titleTh.dataset.sortMode = '';
                            if(response_options.sortedBy == key) {
                                var sortMode = 'up';
                                titleTh.dataset.sortMode = 'desc';
                                if(response_options.sortMode == 'desc') {
                                    sortMode = 'down';
                                    titleTh.dataset.sortMode = 'asc';
                                }
                            }
                        }
                        if(thClass) {
                            titleTh.className = thClass.trim();
                        }
                    }
                    var innerHTML = '<label>'+thValue+'</label>';
                    if(name.filterable) {
                        titleTh.dataset.filterable = '';
                        innerHTML += '<a class="rewatajax-filter" tabindex="0" role="button" data-html="true" data-toggle="popover" data-placement="bottom" data-trigger="click" title="'+options.language.filter+'" data-content="'+$this.getFilterForm(key)+'"><i class="fa fa-filter"></i></a>';
                    }
                    titleTh.innerHTML = innerHTML;
                    tr.appendChild(titleTh);
                }

                thead.appendChild(tr);
                return thead;
            };
            $this.getBody = function() {
                var tbody = document.createElement('tbody');
                if(body_data.length === 0) {
                    var tr = document.createElement('tr');
                    var td = document.createElement('td');
                    td.colSpan = Object.keys(header_data).length;
                    td.className = 'no-results';
                    td.innerText = options.language.no_entries_message;
                    tr.appendChild(td);
                    tbody.appendChild(tr);
                    return tbody;
                }
                $(body_data).each(function(key, element) {
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
                        td.colSpan = Object.keys(header_data).length;
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
                return tbody;
            };

            this.createSearch = function (tableContainer) {
                var search = document.createElement('div');
                search.className = 'table_search';

                var _this = this;
                var result_counter = document.createElement('span');
                result_counter.style.cssText = 'float: right';
                result_counter.className = 'rewatajax_resultcounter';
                search.append(result_counter);

                var search_input = document.createElement('input');
                search_input.type = 'text';
                search_input.className = 'rewatajax_search_input';
                search_input.style.cssText = 'float: left';
                search_input.placeholder = options.language.search_text;
                $(search_input).keyup(function () {
                    _this.searchKeyUp($(this).val());
                });

                search.append(search_input);

                tableContainer.prepend(search);
            };

            this.format = function(str, arr) {
                return str.replace(/%(\d+)/g, function(_,m) {
                    return arr[--m];
                });
            };
            this.gotoPage = function (page) {
                options.current_page = page;
                this.updatePager();
                $this.callConnector(function() { $this.updateContent(); });
            };
            this.updatePager = function () {
                $(this).find('span.rewatajax_resultcounter').text(this.format(options.language.search_results, [response_options.total_results]));

                var _this = this;
                var $pagination = $(this).find('nav.pagination_container');
                $pagination.empty(); // Empty pager first
                if (response_options.total_pages > 1) {
                    var pagination_container = document.createElement('ul');
                    pagination_container.className = 'pagination';

                    /**
                     * Check if we need to show the first page button
                     */
                    if (options.current_page >= options.pager_max_buttons) {
                        var pagination_element = document.createElement('li');
                        pagination_element.className = 'page-item';
                        a_element = document.createElement('a');
                        a_element.className = 'page-link';
                        a_element.setAttribute('href', 'javascript:;');
                        a_element.innerText = '1';
                        pagination_element.appendChild(a_element);

                        $(pagination_element).click(function () { _this.gotoPage(1);});
                        pagination_container.appendChild(pagination_element);

                        pagination_element = document.createElement('li');
                        pagination_element.className = 'page-item page-dots';
                        pagination_element.innerHTML = '...';
                        pagination_container.appendChild(pagination_element);
                    }

                    /**
                     * Render page buttons
                     */
                    var i_start = Math.floor(options.current_page - (options.pager_max_buttons/2))-1;
                    var i_end = i_start + options.pager_max_buttons+2;

                    i_end = (i_end > response_options.total_pages) ? response_options.total_pages : i_end;
                    i_start = (i_start < 0) ? 0 : i_start;

                    for(var i=i_start; i<i_end; i++) {
                        pagination_element = document.createElement('li');
                        pagination_element.className = 'page-item';
                        if (options.current_page == (i+1)) {
                            pagination_element.className = 'page-item active';
                        }

                        var a_element = document.createElement('a');
                        a_element.className = 'page-link';
                        a_element.setAttribute('href', 'javascript:;');
                        a_element.innerText = (i+1);
                        pagination_element.appendChild(a_element);
                        $(pagination_element).click(function () { _this.gotoPage( $(this).find('a').text() ); });
                        pagination_container.appendChild(pagination_element);
                    }

                    /**
                     * Check if we need to show the last page button
                     */
                    if (response_options.total_results <= Math.floor(response_options.total_pages-options.pager_max_buttons/2)) {
                        pagination_element = document.createElement('li');
                        pagination_element.innerHTML = '...';
                        pagination_element.className = 'page_button page_dots';
                        pagination_container.appendChild(pagination_element);

                        pagination_element = document.createElement('li');
                        pagination_element.className = 'page-item';
                        a_element = document.createElement('a');
                        a_element.className = 'page-link';
                        a_element.setAttribute('href', 'javascript:;');
                        a_element.innerText = (response_options.total_pages);
                        pagination_element.appendChild(a_element);
                        $(pagination_element).click(function () { _this.gotoPage(response_options.total_pages);});
                        pagination_container.appendChild(pagination_element);
                    }
                    $pagination.html(pagination_container);
                }
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

            $this.updateHead = function() {
                var thead = $this.getHead().children;
                $(outputToDiv).find('table#'+tableId+' > thead').html(thead);
                $("[data-toggle=popover]").popover();
            };
            $this.updateContent = function() {
                var tbody = $this.getBody().children;
                $(outputToDiv).find('table#'+tableId+' > tbody').html(tbody);
            };
            $this.drawTable = function() {
                if(!header_data) {
                    console.log('Header data missing.');
                    return;
                }
                if(!body_data) {
                    console.log('Content data missing.');
                    return;
                }
                var table = document.createElement('table');
                table.id = tableId;
                table.className = tableClass;
                cached_header = $this.getHead();
                table.appendChild(cached_header);
                var tbody = $this.getBody();
                table.appendChild(tbody);
                $(outputToDiv).html(table);
            };

            $this.init = function() {
                var tableContainer = $(this);
                $this.cleanup(tableContainer);
                tableContainer.innerHTML = '';
                var table = document.createElement('table');
                table.id = tableId;
                table.className = tableClass;
                var thead = document.createElement('thead');
                thead.id = tableId+'_thead';
                table.appendChild(thead);
                var tbody = document.createElement('tbody');
                tbody.id = tableId+'_tbody';
                table.appendChild(tbody);

                var ajaxLoading = document.createElement('div');
                ajaxLoading.className = 'ajax_loading';
                table.insertBefore(ajaxLoading, table.firstChild);

                tableContainer.html(table);

                $this.createSearch(tableContainer);
                var paginator = document.createElement('nav');
                paginator.setAttribute('aria-label', 'Page navigation');
                paginator.className = 'pagination_container';
                tableContainer.append(paginator);

                $this.callConnector(function() { $this.updateHead(); $this.updateContent(); });
                tableContainer.find('div.ajax_loading').hide();

                tableContainer.on('click', allSortableSelector+' > label', function() {
                    var parent = $(this).parent('th');
                    var direction = 'asc';
                    if($(parent).attr('data-sort-mode') == 'asc') {
                        direction = 'desc';
                    }
                    $(allSortableSelector).attr('data-sort-mode', '');
                    $(parent).attr('data-sort-mode', direction);
                    options.sort_by = $(parent).attr('data-sort-by');
                    options.sort_mode = direction;

                    $this.callConnector(function() { $this.updateContent(); });
                });
                tableContainer.on('click', allFilterableSelector+' > a', function() {
                    var parent = $(this).parent('th');

                    if($(this).hasClass('active')) {
                        $(this).removeClass('active');
                    }else{
                        $(this).addClass('active');
                    }

                    //$this.callConnector(function() { $this.updateContent(); });
                });

                //$('div.datepicker > input').datepicker({
                //    showOn: ""
                //});
                tableContainer.on('click', 'span[data-action="showDatepicker"] > span', function() {
                    var id = $(this).data('id');
                    console.log($(this));
                    //$('#'+id).datepicker("show");
                });

                $(document).keydown(function( event ) {
                    var tag = event.target.tagName.toLowerCase();
                    if(tag != 'input' && tag != 'select') {
                        if(event.which == 37) { //37 - back
                            if(options.current_page > 1) {
                                $this.gotoPage(Number(options.current_page)-1);
                            }
                        }else if(event.which == 39) { // 39 - forward
                            if(options.current_page < response_options.total_pages) {
                                $this.gotoPage(Number(options.current_page)+1);
                            }
                        }
                    }
                });
            };

            $this.cleanup = function(tableContainer) {
                $(document).unbind('keydown');
                if(tableContainer) {
                    tableContainer.unbind('click');
                }
            };
            $this.init();
            return this;
        }
    });
})(jQuery);

function createTableObject(tableOptions, theadObject, tbodyObject, noResultsMessage, options) {
    var $this = this;
    var tableId = tableOptions.id;
    if(!tableId) {
        return;
    }
    var tableClass = tableOptions.class;

    $this.createHead = function(table, theadObject, tbodyObject, noResultsMessage, options) {
        var thead = document.createElement('thead');
        var tr = document.createElement('tr');

        for(var key in theadObject) {
            var name = theadObject[key];
            var titleTh = document.createElement('th');
            var thValue = name;
            if(name.constructor === Object) {
                thValue = name.content;
                if(name.id) {
                    titleTh.id = name.id;
                }
                var thClass = '';
                if(name.class) {
                    thClass += name.class;
                }
                if(name.width) {
                    titleTh.width = name.width;
                }
                if(name.sortable) {
                    thClass += ' sortable';
                    if(options.sortedBy == key) {
                        var sortMode = 'up';
                        if(options.sortMode == 'desc') {
                            sortMode = 'down';
                        }
                        thClass += ' sortable-'+sortMode;
                    }
                }
                if(thClass) {
                    titleTh.className = thClass.trim();
                }
            }
            titleTh.innerHTML = '<span>'+thValue+'</span>';
            tr.appendChild(titleTh);
        }

        thead.appendChild(tr);
        table.appendChild(thead);
    };

    var sortableSelector = 'table#'+tableId+' > thead > tr > th.sortable';
    $('body').on('click', sortableSelector+' > span', function() {
        var parent = $(this).parent('th');
        var direction = '';
        if(parent.hasClass('sortable-up')) {
            $(sortableSelector+'-up').removeClass('sortable-up');
            parent.addClass('sortable-down');
            direction = 'desc';
        }else{
            $(sortableSelector+'-down').removeClass('sortable-down');
            parent.addClass('sortable-up');
            direction = 'asc';
        }
    });

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
    $this.createBody = function(table, theadObject, tbodyObject, noResultsMessage, options) {
        var tbody = document.createElement('tbody');
        if(tbodyObject.length === 0) {
            if(noResultsMessage !== null) {
                var tr = document.createElement('tr');
                var td = document.createElement('td');
                td.colSpan = Object.keys(theadObject).length;
                td.className = 'no-results';
                var text = document.createTextNode(noResultsMessage);
                td.appendChild(text);
                tr.appendChild(td);
                table.appendChild(tr);
            }
            return;
        }
        $(tbodyObject).each(function(key, element) {
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
                td.colSpan = Object.keys(theadObject).length;
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
    $this.createHead(table, theadObject, tbodyObject, noResultsMessage, options);
    $this.createBody(table, theadObject, tbodyObject, noResultsMessage, options);
    return table;
}