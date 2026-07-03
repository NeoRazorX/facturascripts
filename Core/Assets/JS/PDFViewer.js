/*
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

function fsPdfPreviewModal(labels) {
    const existing = document.getElementById('fsPdfPreviewModal');
    if (existing) {
        return existing;
    }

    const modalHTML = `
    <div class="modal fade" id="fsPdfPreviewModal" tabindex="-1" aria-labelledby="fsPdfPreviewModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="fsPdfPreviewModalLabel">${labels.title}</h5>
            <div class="ms-auto me-2">
              <button type="button" id="fsPdfPreviewPrintBtn" class="btn btn-secondary me-1">
                <i class="fa-solid fa-print fa-fw"></i> ${labels.print}
              </button>
              <button type="button" id="fsPdfPreviewDownloadBtn" class="btn btn-primary">
                <i class="fa-solid fa-download fa-fw"></i> ${labels.download}
              </button>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-0">
            <iframe id="fsPdfPreviewFrame" style="width: 100%; height: 75vh; border: 0; display: block;"></iframe>
          </div>
        </div>
      </div>
    </div>
  `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    const modalEl = document.getElementById('fsPdfPreviewModal');
    const frame = document.getElementById('fsPdfPreviewFrame');

    document.getElementById('fsPdfPreviewPrintBtn').addEventListener('click', function () {
        if (frame.contentWindow && typeof frame.contentWindow.fsPrint === 'function') {
            frame.contentWindow.fsPrint();
        }
    });

    document.getElementById('fsPdfPreviewDownloadBtn').addEventListener('click', function () {
        if (!frame.contentWindow || typeof frame.contentWindow.fsDownloadPdf !== 'function') {
            return;
        }

        const btn = this;
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + labels.generating;
        frame.contentWindow.fsDownloadPdf(modalEl.dataset.filename || null).then(function () {
            btn.disabled = false;
            btn.innerHTML = original;
        }).catch(function () {
            btn.disabled = false;
            btn.innerHTML = original;
        });
    });

    return modalEl;
}

function fsShowPdfPreview(extraData = {}, filename = '', labels = {}) {
    labels = Object.assign({
        title: 'PDF',
        print: 'Print',
        download: 'PDF',
        generating: '...'
    }, labels);

    const modalEl = fsPdfPreviewModal(labels);
    modalEl.dataset.filename = filename;

    animateSpinner('add');
    $.ajax({
        method: 'POST',
        url: window.location.href,
        data: Object.assign({action: 'pdf-preview'}, extraData),
        dataType: 'html',
        success: function (html) {
            animateSpinner('remove');
            document.getElementById('fsPdfPreviewFrame').srcdoc = html;
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        },
        error: function () {
            animateSpinner('remove', false);
        }
    });
}
