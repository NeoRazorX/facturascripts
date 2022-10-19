let waitCounter = 0;

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
        activetab: select.form().find('input[name="activetab"]').val(),
        term: getValueTypeParent(parent),
        field: select.attr("data-field"),
        fieldcode: select.attr("data-fieldcode"),
        fieldfilter: select.attr("data-fieldfilter"),
        fieldtitle: select.attr("data-fieldtitle"),
        source: select.attr("data-source"),
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
                select.append('<option value="' + element.key + '" ' + selected + '>' + element.value + '</option>');
            });
        },
        error: function (msg) {
            alert(msg.status + " " + msg.responseText);
        }
    });
}

$(document).ready(function () {
    $('.parentSelect').each(function () {
        let parentStr = $(this).attr('parent');
        if (parentStr === 'undefined' || parentStr === false || parentStr === '') {
            return;
        }

        let select = $(this);
        let parent = select.form().find('[name="' + parentStr + '"]');

        if (parent.is('select') || ['color', 'datetime-local', 'date', 'time', 'hidden'].includes(parent.attr('type'))) {
            parent.change(function(){
                widgetSelectGetData(select, parent);
            });
        } else if (parent.is('input') || parent.is('textarea')) {
            parent.keyup(async function(){
                // usamos un contador y un temporizador para solamente procesar la última llamada
                waitCounter++;
                let waitNum = waitCounter;
                await new Promise(r => setTimeout(r, 500));
                if (waitNum < waitCounter) {
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