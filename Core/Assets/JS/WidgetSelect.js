let waitSelectCounter = 0;
const widgetSelectSharedLists = {};
let widgetSelectSharedAdapter = null;

function widgetSelectGetOptionData(element) {
    if (element.tagName.toLowerCase() === 'optgroup') {
        let children = [];
        $(element).children('option').each(function () {
            children.push(widgetSelectGetOptionData(this));
        });

        return {
            text: element.label,
            disabled: element.disabled,
            children: children
        };
    }

    return {
        id: $(element).val(),
        text: $(element).text(),
        disabled: element.disabled,
        title: element.title
    };
}

function widgetSelectLoadSharedLists() {
    $('select.select2[data-shared-source="true"]').each(function () {
        let list = [];
        $(this).children('option, optgroup').each(function () {
            list.push(widgetSelectGetOptionData(this));
        });

        widgetSelectSharedLists[$(this).attr('data-shared-list')] = list;
    });
}

function widgetSelectListContains(list, id) {
    for (let index = 0; index < list.length; index++) {
        if (list[index].children && widgetSelectListContains(list[index].children, id)) {
            return true;
        }
        if (list[index].id != null && list[index].id.toString() === id.toString()) {
            return true;
        }
    }

    return false;
}

function widgetSelectGetSharedAdapter() {
    if (widgetSelectSharedAdapter !== null) {
        return widgetSelectSharedAdapter;
    }

    const amd = $.fn.select2.amd;
    const SelectAdapter = amd.require('select2/data/select');
    const Utils = amd.require('select2/utils');

    function SharedAdapter($element, options) {
        this.sharedData = (widgetSelectSharedLists[$element.attr('data-shared-list')] || []).slice();
        let adapter = this;
        $element.find('option').each(function () {
            let item = widgetSelectGetOptionData(this);
            if (false === widgetSelectListContains(adapter.sharedData, item.id)) {
                adapter.sharedData.push(item);
            }
        });
        SharedAdapter.__super__.constructor.call(this, $element, options);
    }

    Utils.Extend(SharedAdapter, SelectAdapter);

    SharedAdapter.prototype.query = function (params, callback) {
        let results = [];
        for (let index = 0; index < this.sharedData.length; index++) {
            let item = this._normalizeItem(this.sharedData[index]);
            let match = this.matches(params, item);
            if (match !== null) {
                results.push(match);
            }
        }

        callback({results: results});
    };

    SharedAdapter.prototype.select = function (data) {
        let option = this.$element.find('option').filter(function () {
            return $(this).val() == data.id;
        });

        if (option.length === 0) {
            this.addOptions(this.option(data));
        }

        SharedAdapter.__super__.select.call(this, data);
    };

    widgetSelectSharedAdapter = SharedAdapter;
    return widgetSelectSharedAdapter;
}

function getValueTypeParent(parent) {
    if (parent.is('select')) {
        return parent.find('option:selected').val();
    } else if (parent.attr('type') === 'checkbox' && parent.prop("checked")) {
        return parent.val();
    } else if (parent.attr('type') === 'radio') {
        return parent.find(':checked').val();
    } else if (parent.is('input') || parent.is('textarea')) {
        return parent.val();
    }

    return '';
}

function widgetSelectGetData(select, parent) {
    select.html('');

    let data = {
        action: 'select',
        activetab: select.closest('form').find('input[name="activetab"]').val(),
        field: select.attr("data-field"),
        fieldcode: select.attr("data-fieldcode"),
        fieldfilter: select.attr("data-fieldfilter"),
        fieldtitle: select.attr("data-fieldtitle"),
        required: select.attr('required') === 'required' ? 1 : 0,
        source: select.attr("data-source"),
        term: getValueTypeParent(parent),
    };

    $.ajax({
        method: "POST",
        url: window.location.href,
        data: data,
        dataType: "json",
        success: function (results) {
            select.html('');
            results.forEach(function (element) {
                let selected = (element.key === select.attr('value')) ? 'selected' : '';
                let key = (element.key == null) ? '' : element.key;
                select.append('<option value="' + key + '" ' + selected + '>' + element.value + '</option>');
            });
            select.change();
        },
        error: function (msg, textStatus, errorThrown) {
            console.log('widgetSelectGetData AJAX ERROR');
            console.log('status:', msg.status);
            console.log('textStatus:', textStatus);
            console.log('errorThrown:', errorThrown);
            console.log('responseText:', msg.responseText);
            alert(msg.status + " " + msg.responseText);
        }
    });
}

$(document).ready(function () {
    widgetSelectLoadSharedLists();

    $('select.select2').each(function () {
        let options = {
            width: 'style',
            theme: 'bootstrap-5'
        };
        let sharedList = $(this).attr('data-shared-list');
        if (widgetSelectSharedLists[sharedList] !== undefined) {
            options.dataAdapter = widgetSelectGetSharedAdapter();
        }

        $(this).select2(options);
    }).closest('form').on('reset', function () {
        // select2 no restaura su UI con el reset nativo, https://github.com/select2/select2/issues/363
        // el evento reset se dispara antes de que el navegador restaure el formulario,
        // así que restauramos los option a mano y avisamos solo a select2 (namespace)
        const select2 = $(this).find('select.select2');
        select2.find('option').prop('selected', function () {
            return this.defaultSelected;
        });
        select2.trigger('change.select2');
    });

    $('.parentSelect').each(function () {
        let parentStr = $(this).attr('parent');
        if (parentStr === 'undefined' || parentStr === false || parentStr === '') {
            return;
        }

        let select = $(this);
        let parent = select.closest('form').find('[name="' + parentStr + '"]');
        if (parent.is('select') || ['color', 'datetime-local', 'date', 'time'].includes(parent.attr('type'))) {
            parent.change(function(){
                widgetSelectGetData(select, parent);
            });
        } else if (parent.attr('type') === 'hidden') {
            let hiddenInput = document.querySelector("[name='" + parentStr + "']");
            hiddenInput.addEventListener('change', function () {
                widgetSelectGetData(select, parent);
            });

            let previousValue = hiddenInput.value;

            // 1: crea una instancia de MutationObserver
            const observer = new MutationObserver((mutations) => {
                // 2: iterar sobre la matriz `MutationRecord`
                mutations.forEach(mutation => {
                    // 3.1: comprobar si el tipo de mutación y el nombre del atributo coinciden
                    // 3.2: verificar si el valor cambió
                    if (
                        mutation.type === 'attributes'
                        && mutation.attributeName === 'value'
                        && hiddenInput.value !== previousValue
                    ) {
                        previousValue = hiddenInput.value;
                        // 3.4: activar el evento `cambio`
                        hiddenInput.dispatchEvent(new Event('change'));
                    }
                });
            });

            // 4: observar cambios en `hiddenInput`
            observer.observe(hiddenInput, { attributes: true });
        } else if (parent.is('input') || parent.is('textarea')) {
            parent.keyup(async function(){
                // usamos un contador y un temporizador para solamente procesar la última llamada
                waitSelectCounter++;
                let waitNum = waitSelectCounter;
                await new Promise(r => setTimeout(r, 500));
                if (waitNum < waitSelectCounter) {
                    return false;
                }

                widgetSelectGetData(select, parent);
            });
        }

        if (parent.length > 0) {
            widgetSelectGetData(select, parent);
        }
    });
});
