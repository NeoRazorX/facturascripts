/**
 * CDTBankTools — inject "Forzar Conciliación" button in bank movements list.
 *
 * Polls until movement rows exist (loaded via AJAX), then injects
 * a force-reconcile button in each dropdown. Uses form POST to submit.
 */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var attempts = 0;
    var maxAttempts = 20;

    var interval = setInterval(function () {
        attempts++;
        var rows = document.querySelectorAll('tr[data-idmovement]');

        if (rows.length > 0) {
            clearInterval(interval);
            injectButtons(rows);
        } else if (attempts >= maxAttempts) {
            clearInterval(interval);
        }
    }, 500);

    function injectButtons(rows) {
        rows.forEach(function (row) {
            var menu = row.querySelector('.dropdown-menu');
            if (!menu || menu.querySelector('.cdt-force-reconcile')) {
                return;
            }

            var divider = document.createElement('div');
            divider.className = 'dropdown-divider';

            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'dropdown-item text-warning cdt-force-reconcile';
            btn.innerHTML = '<i class="fa-solid fa-check-double me-2"></i> Forzar Conciliación';
            btn.addEventListener('click', function () {
                forceReconcile(row.dataset.idmovement);
            });

            menu.appendChild(divider);
            menu.appendChild(btn);
        });
    }

    function forceReconcile(movementId) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.href;

        var tokenEl = document.querySelector('input[name="multireqtoken"]');
        if (tokenEl) {
            var tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = 'multireqtoken';
            tokenInput.value = tokenEl.value;
            form.appendChild(tokenInput);
        }

        var actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'force-reconcile';
        form.appendChild(actionInput);

        var idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = movementId;
        form.appendChild(idInput);

        document.body.appendChild(form);
        form.submit();
    }
});
