function getValueTypeParent(parent) {
    let term = '';

    if (parent.is('input')) {
        term = parent.val();
    } else if (parent.is('select')) {
        term = parent.find('option:selected').val();
    } else if (parent.is('checkbox') && parent.prop("checked")) {
        term = parent.val();
    } else if (parent.is('radio')) {
        term = parent.find(':checked').val();
    } else if (parent.is('textarea')) {
        term = parent.val();
    }

    return term;
}

function widgetSelectGetData(select) {
    select.html('');

    let data = {
        action: 'select',
        activetab: select.form().find('input[name="activetab"]').val(),
        term: getValueTypeParent(select.form().find('[name="' + select.attr('parent') + '"]')),
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
            results.forEach(function (element) {
                let selected = (element.key == select.attr('value')) ? 'selected' : '';
                select.append('<option value="'+element.key+'" '+selected+'>'+element.value+'</option>');
            });
        },
        error: function (msg) {
            alert(msg.status + " " + msg.responseText);
        }
    });
}

$(document).ready(function () {
    $('.parentSelect').each(function(){
        let parent = $(this).attr('parent');
        if (parent === 'undefined' || parent === false || parent === '') {
            return;
        }

        let select = $(this);
        select.form().find('select[name="' + parent + '"]').on('change', function(){
            widgetSelectGetData(select);
        });

        widgetSelectGetData(select);
    });
});