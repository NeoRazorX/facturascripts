/*
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * Completa y valida los campos datetime del formulario.
 *
 * En navegadores como Safari el input datetime-local se comporta como un campo
 * de texto: el usuario puede enviar el formulario con la hora sin rellenar, o
 * incluso con una fecha incompleta. Este script, al cambiar el valor:
 *   1. Rellena automáticamente la hora y los minutos con la hora actual si faltan.
 *   2. Avisa (y limpia el campo) si falta el día, el mes o el año.
 *
 * Se usa delegación de eventos sobre document para cubrir también las filas
 * añadidas dinámicamente (EditListView).
 */
document.addEventListener('change', function (event) {
    const input = event.target;
    if (input && input.matches && input.matches('input.widget-datetime')) {
        widgetDatetimeComplete(input);
    }
});

function widgetDatetimeComplete(input) {
    let value = (input.value || '').trim();
    if (value === '') {
        return;
    }

    // separa fecha y hora aceptando como separador la "T" o un espacio
    const parts = value.split(/[T ]/);
    const datePart = parts[0];
    let timePart = parts.length > 1 ? parts[1] : '';

    // la fecha debe tener año, mes y día (formato YYYY-MM-DD)
    if (/^\d{4}-\d{2}-\d{2}$/.test(datePart) === false) {
        window.alert('Fecha incompleta. Indica día, mes y año.');
        input.value = '';
        input.focus();
        return;
    }

    // completa la hora con la actual si falta o está incompleta (formato HH:MM)
    if (/^\d{2}:\d{2}/.test(timePart) === false) {
        const now = new Date();
        const hh = String(now.getHours()).padStart(2, '0');
        const mm = String(now.getMinutes()).padStart(2, '0');
        timePart = hh + ':' + mm;
    }

    // normaliza el valor al formato esperado por datetime-local: YYYY-MM-DDTHH:MM
    input.value = datePart + 'T' + timePart.substring(0, 5);
}
