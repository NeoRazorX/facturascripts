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

/**
 * Base CSS for dynamic PDF documents, with the FacturaScripts document style.
 */
class PDFStyles
{
    const A4_WIDTH_MM = 210;
    const A4_HEIGHT_MM = 297;

    public static function get(string $orientation, int $marginMm, array $options = []): string
    {
        // defaults matching the core PDF export look: black titles and light
        // grey table shading (the 0.95 grey of ezPDF tables)
        $options = array_merge([
            'fontcolor' => '#000000',
            'fontsize' => '12px',
            'shadecolor' => '#F2F2F2',
            'titlecolor' => '#000000',
            'titlefontsize' => '18px',
        ], $options);

        // the .page is a full size sheet with the margin as visible padding (wysiwyg preview);
        // html2pdf exports with margin 0, so what you see is exactly what you get
        $width = $orientation === PDFBuilder::ORIENTATION_LANDSCAPE ? self::A4_HEIGHT_MM : self::A4_WIDTH_MM;
        $height = $orientation === PDFBuilder::ORIENTATION_LANDSCAPE ? self::A4_WIDTH_MM : self::A4_HEIGHT_MM;

        return ':root {'
            . '--fs-shade: ' . $options['shadecolor'] . ';'
            . '--fs-title-color: ' . $options['titlecolor'] . ';'
            . '--fs-font-size: ' . $options['fontsize'] . ';'
            . '--fs-title-size: ' . $options['titlefontsize'] . ';'
            . '}'
            . '* { box-sizing: border-box; margin: 0; padding: 0; }'
            . 'body {'
            . 'background: #525659;'
            . "font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;"
            . 'font-size: var(--fs-font-size);'
            . 'color: ' . $options['fontcolor'] . ';'
            . 'padding: 5mm 0;'
            . '}'
            . '.page {'
            . 'position: relative;'
            . 'width: ' . $width . 'mm;'
            . 'min-height: ' . $height . 'mm;'
            . 'background: #fff;'
            . 'margin: 0 auto 5mm auto;'
            . 'padding: ' . $marginMm . 'mm;'
            . 'box-shadow: 0 0 4px rgba(0, 0, 0, 0.5);'
            . 'page-break-after: always;'
            . '}'
            . '.title { font-size: var(--fs-title-size); color: var(--fs-title-color); font-weight: bold;'
            . ' margin-bottom: 3mm; }'
            . 'h2.title { font-size: calc(var(--fs-title-size) - 2px); margin-bottom: 2mm; }'
            . 'h3.title { font-size: calc(var(--fs-title-size) - 4px); margin-bottom: 2mm; }'
            . '.text { margin-bottom: 2mm; line-height: 1.4; }'
            . '.shade-box { background: var(--fs-shade); padding: 4px 8px; font-weight: bold; }'
            . '.table-list { width: 100%; border-collapse: collapse; margin-bottom: 3mm; }'
            . '.table-list thead th { background: var(--fs-shade); padding: 5px 7px; }'
            . '.table-list td { padding: 5px 7px; border-bottom: 1px solid var(--fs-shade); }'
            . '.table-list tbody tr:nth-child(even) { background: var(--fs-shade); }'
            . '.table-dual { width: 100%; border-collapse: collapse; margin-bottom: 2mm; }'
            . '.table-dual td { padding: 3px 6px; }'
            . '.table-dual td:first-child { background: var(--fs-shade); font-weight: bold; width: 40%; }'
            . '.columns { display: flex; gap: 5mm; margin-bottom: 2mm; }'
            . '.columns > div { flex: 1; min-width: 0; }'
            . '.company-header { display: flex; gap: 5mm; align-items: flex-start; margin-bottom: 5mm; }'
            . '.company-header .company-data { flex: 1; }'
            . '.company-header img { max-height: 30mm; max-width: 60mm; }'
            . '.document-header { display: flex; justify-content: space-between; align-items: flex-start;'
            . ' gap: 5mm; margin-bottom: 10mm; }'
            . '.document-header .header-logo img { max-height: 22mm; max-width: 70mm; }'
            . '.document-header .company-name { font-size: calc(var(--fs-title-size) + 4px); }'
            . '.table-parallel { width: 100%; border-collapse: collapse; margin-bottom: 3mm; }'
            . '.table-parallel td { padding: 3px 6px; vertical-align: top; width: 50%; }'
            . 'hr { border: 0; border-top: 1px solid #333; margin: 2mm 0 4mm 0; }'
            . '.page-footer { position: absolute; left: ' . $marginMm . 'mm; right: ' . $marginMm . 'mm;'
            . ' bottom: 10mm; display: flex; justify-content: space-between;'
            . ' font-size: calc(var(--fs-font-size) - 2px); }'
            . '.watermark-text { position: absolute; top: 50%; left: 50%; width: 90%;'
            . ' transform: translate(-50%, -50%) rotate(-35deg); text-align: center;'
            . ' font-size: calc(var(--fs-title-size) + 4px); font-weight: bold; opacity: 0.5;'
            . ' pointer-events: none; }'
            . '.text-left { text-align: left; }'
            . '.text-center { text-align: center; }'
            . '.text-right { text-align: right; }'
            . '.text-justify { text-align: justify; }'
            . '.font-bold { font-weight: bold; }'
            . '.font-big { font-size: calc(var(--fs-font-size) + 2px); }'
            . '.font-small { font-size: calc(var(--fs-font-size) - 2px); }'
            . '.nowrap { white-space: nowrap; }'
            . '.mx-auto { margin-left: auto; margin-right: auto; }'
            . '.mb-0 { margin-bottom: 0; } .mb-2 { margin-bottom: 2mm; } .mb-5 { margin-bottom: 5mm; }'
            . '.mt-0 { margin-top: 0; } .mt-2 { margin-top: 2mm; } .mt-5 { margin-top: 5mm; }'
            . '.w-25 { width: 25%; } .w-50 { width: 50%; } .w-75 { width: 75%; } .w-100 { width: 100%; }'
            . '@page { size: A4 ' . $orientation . '; margin: 0; }'
            . '@media print {'
            . 'body { background: #fff; padding: 0; }'
            . '.page { margin: 0 auto; box-shadow: none; }'
            . '}';
    }
}
