<?php
/**
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

namespace FacturaScripts\Core\Lib\PDF\Dynamic;

use FacturaScripts\Core\Lib\AssetManager;
use FacturaScripts\Core\Tools;

/**
 * Adds a 'pdf-preview' ajax action to a controller. The controller implements
 * buildPdf() and adds to its execPreviousAction():
 *
 *     case 'pdf-preview':
 *         return $this->pdfPreviewAction();
 *
 * On the client side, fsShowPdfPreview() (PDFViewer.js) opens the preview modal.
 */
trait PDFPreviewTrait
{
    /**
     * Builds and returns the document to preview.
     */
    abstract protected function buildPdf(): PDFBuilder;

    /**
     * Returns the onclick js code for a 'js' type button that opens the preview modal.
     */
    protected function pdfPreviewButtonJs($code, string $filename = ''): string
    {
        $i18n = Tools::lang();
        $labels = [
            'title' => $i18n->trans('pdf-preview'),
            'print' => $i18n->trans('print'),
            'download' => $i18n->trans('download-pdf'),
            'generating' => $i18n->trans('generating-pdf'),
        ];

        $js = 'fsShowPdfPreview({code: ' . json_encode((string)$code) . '}, '
            . json_encode($filename) . ', ' . json_encode($labels) . ')';

        // the button renders this inside onclick="...", so double quotes must be html-escaped
        return htmlspecialchars($js, ENT_COMPAT, 'UTF-8');
    }

    protected function loadPdfViewerAssets(): void
    {
        AssetManager::addJs(Tools::config('route') . '/Dinamic/Assets/JS/PDFViewer.js');
    }

    protected function pdfPreviewAction(): bool
    {
        $this->setTemplate(false);

        if (false === $this->permissions->allowExport) {
            $this->response->setHttpCode(403);
            $this->response->setContent(Tools::lang()->trans('access-denied'));
            $this->response->send();
            return false;
        }

        $this->response->setContent($this->buildPdf()->getHtml());
        $this->response->send();
        return false;
    }
}
