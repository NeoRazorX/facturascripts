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
        $options = array_merge([
            'color1' => '#2770CA',
            'color2' => '#FFFFFF',
            'color3' => '#F1F1F1',
            'fontcolor' => '#000000',
            'fontsize' => '12px',
            'titlefontsize' => '18px',
        ], $options);

        // the html2pdf margin is applied outside the page, so the visible sheet is the inner area
        $width = $orientation === PDFBuilder::ORIENTATION_LANDSCAPE ? self::A4_HEIGHT_MM : self::A4_WIDTH_MM;
        $height = $orientation === PDFBuilder::ORIENTATION_LANDSCAPE ? self::A4_WIDTH_MM : self::A4_HEIGHT_MM;
        $innerWidth = $width - $marginMm * 2;
        $innerHeight = $height - $marginMm * 2;

        return ':root {'
            . '--fs-color1: ' . $options['color1'] . ';'
            . '--fs-color2: ' . $options['color2'] . ';'
            . '--fs-color3: ' . $options['color3'] . ';'
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
            . 'width: ' . $innerWidth . 'mm;'
            . 'min-height: ' . $innerHeight . 'mm;'
            . 'background: #fff;'
            . 'margin: 0 auto 5mm auto;'
            . 'box-shadow: 0 0 4px rgba(0, 0, 0, 0.5);'
            . 'page-break-after: always;'
            . '}'
            . '.title { font-size: var(--fs-title-size); color: var(--fs-color1); font-weight: bold; }'
            . 'h2.title { font-size: calc(var(--fs-title-size) - 2px); }'
            . 'h3.title { font-size: calc(var(--fs-title-size) - 4px); }'
            . '.text { margin-bottom: 2mm; }'
            . '.primary-box { background: var(--fs-color1); color: var(--fs-color2); padding: 4px 8px;'
            . ' text-transform: uppercase; font-weight: bold; }'
            . '.seccondary-box { background: var(--fs-color3); padding: 4px 8px; text-transform: uppercase;'
            . ' font-weight: bold; }'
            . '.table-list { width: 100%; border-collapse: collapse; margin-bottom: 2mm; }'
            . '.table-list thead th { background: var(--fs-color1); color: var(--fs-color2); padding: 4px 6px; }'
            . '.table-list td { padding: 4px 6px; }'
            . '.table-list tbody tr:nth-child(even) { background: var(--fs-color3); }'
            . '.table-dual { width: 100%; border-collapse: collapse; margin-bottom: 2mm; }'
            . '.table-dual td { padding: 3px 6px; }'
            . '.table-dual td:first-child { background: var(--fs-color3); font-weight: bold; width: 40%; }'
            . '.columns { display: flex; gap: 5mm; margin-bottom: 2mm; }'
            . '.columns > div { flex: 1; min-width: 0; }'
            . '.company-header { display: flex; gap: 5mm; align-items: flex-start; margin-bottom: 2mm; }'
            . '.company-header .company-data { flex: 1; }'
            . '.company-header img { max-height: 30mm; max-width: 60mm; }'
            . '.text-left { text-align: left; }'
            . '.text-center { text-align: center; }'
            . '.text-right { text-align: right; }'
            . '.font-bold { font-weight: bold; }'
            . '.nowrap { white-space: nowrap; }'
            . '@media print {'
            . 'body { background: #fff; padding: 0; }'
            . '.page { margin: 0 auto; box-shadow: none; }'
            . '}';
    }
}
