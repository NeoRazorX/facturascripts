let waitDatalistCounter = 0;

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

function widgetSelectGetData(input, parent) {
    let datalist = $('#' + input.attr('list'));
    datalist.html('');

    let data = {
        action: 'datalist',
        activetab: input.closest('form').find('input[name="activetab"]').val(),
        field: input.attr("data-field"),
        fieldcode: input.attr("data-fieldcode"),
        fieldfilter: input.attr("data-fieldfilter"),
        fieldtitle: input.attr("data-fieldtitle"),
        required: input.attr('required') === 'required' ? 1 : 0,
        source: input.attr("data-source"),
        term: getValueTypeParent(parent),
    };

    $.ajax({
        method: "POST",
        url: window.location.href,
        data: data,
        dataType: "json",
        success: function (results) {
            datalist.html('');
            results.forEach(function (element) {
                datalist.append('<option value="' + element.key + '">' + element.value + '</option>');
            });
            input.change();
        },
        error: function (msg) {
            alert(msg.status + " " + msg.responseText);
        }
    });
}

$(document).ready(function () {
    $('.parentDatalist').each(function () {
        let parentStr = $(this).attr('parent');
        if (parentStr === 'undefined' || parentStr === false || parentStr === '') {
            return;
        }

        let input = $(this);
        let parent = input.closest('form').find('[name="' + parentStr + '"]');
        if (parent.is('select') || ['color', 'datetime-local', 'date', 'time'].includes(parent.attr('type'))) {
            parent.change(function(){
                widgetSelectGetData(input, parent);
            });
        } else if (parent.attr('type') === 'hidden') {
            var hiddenInput = document.querySelector("[name='" + parentStr + "']");
            hiddenInput.addEventListener('change', function () {
                widgetSelectGetData(input, parent);
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
                waitDatalistCounter++;
                let waitNum = waitDatalistCounter;
                await new Promise(r => setTimeout(r, 500));
                if (waitNum < waitDatalistCounter) {
                    return false;
                }

                widgetSelectGetData(input, parent);
            });
        }

        if (parent.length > 0) {
            widgetSelectGetData(input, parent);
        }
    });
});