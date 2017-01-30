// todo: rename change* fields for standalone release

function rewatajaxParseBodyData(response, header_data) {
    var changeData = [];
    $(response).each(function(key, change) {
        var fieldIndex = [];
        for(var changeKey in change) {
            if(header_data[changeKey]) {
                if(change[changeKey] == '' || change[changeKey] == undefined) {
                    continue;
                }
            }
            fieldIndex[changeKey] = change[changeKey];
        }

        var columns = [];
        for(var headerKey in header_data) {
            var headerValueType = header_data[headerKey].type;
            var headerValueTransform = header_data[headerKey].transform;
            var originalFieldValue = fieldIndex[headerKey];

            //if(headerValueTransform != '' && headerValueTransform != undefined) {
            var transformedText = '';
            switch(headerValueType) {
                case 'date':
                    transformedText = new Date(originalFieldValue.date).toLocaleString();
                    break;
                default:
                    transformedText = headerValueTransform;
                    if(transformedText && transformedText.match('!_self')) {
                        if(originalFieldValue == undefined || !originalFieldValue) {
                            transformedText = '';
                            break;
                        }else{
                            var expression = '!_self';
                            transformedText = transformedText.replace(new RegExp(expression, 'g'), originalFieldValue);
                        }
                    }
                    if(transformedText && transformedText != '') {
                        for(var replaceKey in fieldIndex) {
                            expression = '!'+replaceKey;
                            transformedText = transformedText.replace(new RegExp(expression, 'g'), fieldIndex[replaceKey]);
                        }
                    }else{
                        transformedText = change[headerKey];
                    }
                    break;
            }
            columns.push(transformedText);
        }

        changeData.push(columns);
    });
    return changeData;
}

(function($) {
    $.fn.extend({
        rewatajax: function(options, connectorData, staticResult) {
            var defaults = {
                table_class: '',
                sort_by: '',
                sort_mode: '',
                current_page: 1,
                per_page: 30,
                pager_max_buttons: 5,
                ajax_connector: '/ajax.php',
                search: true,
                searchWord: '',
                search_timeout: 300,
                language: {
                    filter: 'Filter',
                    search_text: 'Search...',
                    search_results: '%1 results found.',
                    no_entries_message: 'No results found.',
                    dateFilter_range: 'Date range'
                },
                daterangepicker: {
                    opens: 'left',
                    autoUpdateInput: false,
                    locale: {
                        format: 'DD/MM/YYYY',
                        cancelLabel: 'Clear'
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
                    dateISO: 'DD/MM/YYYY'
                },
                filter: []
            };
            options = $.extend(defaults, options);

            var _this = this;
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
            var tableClass = options.table_class;

            /* AJAX stuff */
            this.handleConnectorResponse = function(headerData, bodyData, responseOptions) {
                header_data = headerData;
                body_data = bodyData;
                response_options = responseOptions;
                _this.updatePager();
            };
            this.callConnector = function(callWhenFinished) {
                connectorData['sort_by'] = options.sort_by;
                connectorData['sort_mode'] = options.sort_mode;
                connectorData['per_page'] = options.per_page;
                connectorData['current_page'] = options.current_page;
                connectorData['search'] = options.searchWord;
                connectorData['filter'] = options.filter;

                if(staticResult != undefined) {
                    header_data = staticResult.header_data;
                    body_data = staticResult.body_data;
                    response_options = staticResult.response_options;
                    _this.handleConnectorResponse(header_data, body_data, response_options, callWhenFinished);
                    _this.trigger('rewatajax.callConnector', [staticResult, 'static']);
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
                            _this.handleConnectorResponse(response.header, _body_data, response.options, callWhenFinished);
                            callWhenFinished();
                        }
                        _this.trigger('rewatajax.callConnector', [response, 'ajax']);
                    });
                }
            };

            /* Search and filters */
            this.searchKeyUp = function (string) {
                var _this = this;
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function () {
                    _this.search(string);
                }, options.search_timeout);
            };
            this.search = function (string) {
                options.current_page = 1;
                options.searchWord = string;
                _this.callConnector(function() { _this.updateContent('search'); });
            };
            this.createSearch = function (tableContainer) {
                var search = document.createElement('div');
                search.className = 'table_search';

                var results_div = document.createElement('div');
                results_div.className = 'pull-left';
                results_div.className = 'col-md-4 text-right pull-right';

                var result_counter = document.createElement('span');
                result_counter.className = 'rewatajax_resultcounter';
                results_div.append(result_counter);
                search.append(results_div);

                var search_div = document.createElement('div');
                search_div.className = 'pull-left col-md-8 row';

                var search_input = document.createElement('input');
                search_input.type = 'text';
                search_input.className = 'rewatajax_search_input form-control col-md-4';
                search_input.placeholder = options.language.search_text;
                var thisFunction = this;
                $(search_input).keyup(function () {
                    thisFunction.searchKeyUp($(this).val());
                });

                search_div.append(search_input);
                search.append(search_div);
                _this.trigger('rewatajax.createSearch', [search_div]);
                tableContainer.prepend(search);
            };

            this.getFilterForm = function(key) {
                var type = header_data[key].type;
                var form = '';
                switch(type) {
                    case 'date':
                        form += "<label for='"+key+"-dateFilter-range'>"+options.language.dateFilter_range+"</label>";
                        form += "<input type='text' class='input-sm form-control daterange' value='' onkeydown='return false' data-key='"+key+"' id='"+key+"-dateFilter-range' />";
                        break;
                }
                return form;
            };
            this.filterActive = function(filterKey) {
                for(var k in options.filter) {
                    for(var k2 in options.filter[k].filterOptions) {
                        if(options.filter[k].filterOptions[k2].filterKey == filterKey) {
                            return true;
                        }
                    }
                }
                return false;
            };
            this.hasFilter = function(filterName) {
                $(options.filter).each(function(k, v) {
                    if(v.filterType === filterName) {
                        return true;
                    }
                });
                return false;
            };

            /* Paginator and formatting */
            this.format = function(str, arr) {
                return str.replace(/%(\d+)/g, function(_,m) {
                    return arr[--m];
                });
            };
            this.gotoPage = function (page) {
                options.current_page = page;
                this.updatePager();
                _this.callConnector(function() { _this.updateContent('paginator'); });
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
            this.isElement = function(o){
                return (
                    typeof HTMLElement === 'object' ? o instanceof HTMLElement : //DOM2
                        o && typeof o === 'object' && o !== null && o.nodeType === 1 && typeof o.nodeName==='string'
                );
            };
            this.addValueToTd = function(td, value) {
                if(this.isElement(value)) {
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

            this.getHead = function() {
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
                        var className = 'rewatajax-filter';
                        if(_this.filterActive(key)) {
                            className += ' active';
                        }
                        innerHTML += '<a class="'+className+'" id="filter-'+key+'" tabindex="0" role="button" data-html="true" data-toggle="popover" data-placement="top" data-trigger="click" title="'+options.language.filter+' <button class=\'close\'>&times;</button>" data-content="'+_this.getFilterForm(key)+'"><i class="fa fa-filter"></i></a>';
                    }
                    titleTh.innerHTML = innerHTML;
                    tr.appendChild(titleTh);
                }

                thead.appendChild(tr);
                return thead;
            };
            this.getBody = function() {
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
                    $(element).each(function(k, v) {
                        var td = document.createElement('td');
                        if(v == null) {
                            tr.appendChild(td);
                        }else {
                            _this.addValueToTd(td, v);
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
                            if(_this.isElement(element.additionalFullWidthRow.html)) {
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
            this.updateHead = function(type) {
                var thead = _this.getHead().children;
                _this.find('table#'+tableId+' > thead').html(thead);

                $('[data-toggle="popover"]').popover({
                    html: true
                }).on('shown.bs.popover', function (e) {
                    var current_popover = '#' + $(e.target).attr('aria-describedby');
                    $(current_popover).find('.close').click(function(){
                        $('[data-toggle="popover"]').popover('hide');
                    });

                    var $dateRangeInput = $('input.daterange');
                    if($dateRangeInput.data('daterangepicker')) {
                        $dateRangeInput.data('daterangepicker').remove();
                    }
                    $dateRangeInput.daterangepicker(options.daterangepicker)
                        .on('apply.daterangepicker', function(ev, picker) {
                            var formattedStart = picker.startDate.format(options.daterangepicker.locale.format);
                            var formattedEnd = picker.endDate.format(options.daterangepicker.locale.format);
                            var content = formattedStart + picker.locale.separator + formattedEnd;
                            if(formattedStart == formattedEnd) {
                                content = formattedStart;
                            }
                            $(this).val(content);
                            var filterOption = {
                                filterKey: $(ev.target).data('key'),
                                filterValues: [picker.startDate.format('YYYY-MM-DD'), picker.endDate.format('YYYY-MM-DD')]
                            };
                            if(_this.hasFilter('datetime')) {
                                options.filter[k].filterOptions.push(filterOption);
                            }else{
                                options.filter.push({
                                    filterType: 'datetime',
                                    filterOptions: [filterOption]
                                });
                            }
                            $('a#filter-'+$(this).data('key')).addClass('active');
                            _this.callConnector(function() { _this.updateContent('datepicker'); });
                        })
                        .on('cancel.daterangepicker', function(ev, picker) {
                            console.log('cancel triggered');
                            $(this).val('');
                            $(options.filter).each(function(k, v) {
                                if(v.filterType === 'datetime') {
                                    options.filter.splice(k, 1);
                                }
                            });
                            $('a#filter-'+$(this).data('key')).removeClass('active');
                            _this.callConnector(function() { _this.updateContent('datepicker'); });
                        });
                });
                _this.trigger('rewatajax.updateHead', [type]);
            };
            this.updateContent = function(type) {
                var tbody = _this.getBody().children;
                _this.find('table#'+tableId+' > tbody').html(tbody);
                _this.trigger('rewatajax.updateContent', [type]);
            };
            this.drawTable = function() {
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
                cached_header = _this.getHead();
                table.appendChild(cached_header);
                var tbody = _this.getBody();
                table.appendChild(tbody);
                _this.html(table);
                _this.trigger('rewatajax.drawTable', [type]);
            };

            this.init = function() {
                var tableContainer = $(this);
                _this.cleanup(tableContainer);
                tableContainer.innerHTML = '';
                var table = document.createElement('table');
                table.id = tableId;
                table.className = 'rewatajax-table '+tableClass;
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

                if(options.search) {
                    _this.createSearch(tableContainer);
                }
                var paginator = document.createElement('nav');
                paginator.setAttribute('aria-label', 'Page navigation');
                paginator.className = 'pagination_container';
                tableContainer.append(paginator);

                _this.callConnector(function() { _this.updateHead('paginator'); _this.updateContent('paginator'); });
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

                    _this.callConnector(function() { _this.updateContent('sort'); });
                });

                $(document).keydown(function( event ) {
                    var tag = event.target.tagName.toLowerCase();
                    if(tag != 'input' && tag != 'select') {
                        if(event.which == 37) { //37 - back
                            if(options.current_page > 1) {
                                _this.gotoPage(Number(options.current_page)-1);
                            }
                        }else if(event.which == 39) { // 39 - forward
                            if(options.current_page < response_options.total_pages) {
                                _this.gotoPage(Number(options.current_page)+1);
                            }
                        }
                    }
                });
                _this.trigger('rewatajax.init', []);
            };

            this.cleanup = function(tableContainer) {
                $(document).unbind('keydown');
                if(tableContainer) {
                    tableContainer.unbind('click');
                }
            };
            return this;
        }
    });
})(jQuery);