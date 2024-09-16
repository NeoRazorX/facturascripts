let waitSelectCounter = 0;

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
                let selected = (element.key == select.attr('value')) ? 'selected' : '';
                let key = (element.key == null) ? '' : element.key;
                select.append('<option value="' + key + '" ' + selected + '>' + element.value + '</option>');
            });
            select.change();
        },
        error: function (msg) {
            alert(msg.status + " " + msg.responseText);
        }
    });
}

$(document).ready(function () {
    $('select.select2').select2({
        width: 'style',
        theme: 'bootstrap4'
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
            var hiddenInput = document.querySelector("[name='" + parentStr + "']");
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