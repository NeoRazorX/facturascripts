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

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Lib\PDF\Dynamic\Blocks\DualColumnTableBlock;
use FacturaScripts\Core\Lib\PDF\Dynamic\Blocks\TextBlock;
use FacturaScripts\Core\Lib\PDF\Dynamic\Blocks\TitleBlock;
use FacturaScripts\Core\Lib\PDF\Dynamic\PDFBuilder;
use FacturaScripts\Core\Lib\PDF\Dynamic\PDFPreviewTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Empresa;

/**
 * Showcase of the dynamic PDF library: an interactive playground where the
 * same document is rebuilt live with different themes, orientation and
 * watermark, to show how malleable the builder is.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class PDFShowcase extends Controller
{
    use PDFPreviewTrait;

    const THEMES = [
        'core' => [],
        'azul' => ['titlecolor' => '#2770CA', 'shadecolor' => '#E8F0FB'],
        'verde' => ['titlecolor' => '#1E7E34', 'shadecolor' => '#E6F4EA'],
        'burdeos' => ['titlecolor' => '#8B1E3F', 'shadecolor' => '#F7E9EE'],
        'oscuro' => ['titlecolor' => '#212529', 'shadecolor' => '#DEE2E6'],
    ];

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'pdf-showcase';
        $data['icon'] = 'fa-solid fa-file-pdf';
        return $data;
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->loadPdfViewerAssets();

        if ($this->request->queryOrInput('action') === 'pdf-preview') {
            $this->pdfPreviewAction();
        }
    }

    protected function buildPdf(): PDFBuilder
    {
        $i18n = Tools::lang();
        $theme = $this->request->queryOrInput('theme', 'core');
        $orientation = $this->request->queryOrInput('orientation', PDFBuilder::ORIENTATION_PORTRAIT);
        $watermark = (bool)$this->request->queryOrInput('watermark', '0');

        $empresa = new Empresa();
        $empresa->loadWhere([]);

        $doc = PDFBuilder::create()
            ->setTitle('pdf-showcase')
            ->setOrientation($orientation)
            ->setStyleOptions(static::THEMES[$theme] ?? []);

        $this->buildCoverPage($doc);
        $doc->addPageBreak();
        $this->buildComponentsPage($doc, $empresa);

        if ($watermark) {
            $doc->addWatermarkText($i18n->trans('sketch-invoice-warning'));
        }

        return $doc->addPageFooter('FacturaScripts', $i18n->trans('generated-at', ['%when%' => Tools::dateTime()]));
    }

    protected function buildComponentsPage(PDFBuilder $doc, Empresa $empresa): void
    {
        $doc->addDocumentHeader($empresa)
            ->addTitle('Presupuesto: P-2026-0042')
            ->addHtml('<hr/>')
            ->addColumns([
                [new DualColumnTableBlock([
                    'Cliente' => 'ACME Soluciones S.L.',
                    'CIF' => 'B12345678',
                    'Dirección' => 'Calle Mayor 1, 28001 Madrid',
                ])],
                [new DualColumnTableBlock([
                    'Fecha' => Tools::date(),
                    'Validez' => '30 días',
                    'Forma de pago' => 'Transferencia',
                ])],
            ])
            ->addSpacer(3)
            ->addTable([
                ['SRV-01', 'Consultoría e implantación del ERP', '8,00', '60,00', '480,00'],
                ['LIC-02', 'Licencia anual del software', '1,00', '350,00', '350,00'],
                ['FRM-03', 'Formación del equipo (por sesión)', '3,00', '90,00', '270,00'],
                ['SOP-04', 'Soporte prioritario 12 meses', '1,00', '240,00', '240,00'],
            ], ['Ref.', 'Descripción', 'Cant.', 'Precio', 'Total'],
                ['left', 'left', 'right', 'right', 'right'])
            ->addColumns([
                [new TextBlock('Los precios no incluyen IVA. La propuesta incluye migración de datos desde el sistema anterior y acompañamiento durante el primer mes.', 'text-justify')],
                [new DualColumnTableBlock([
                    'Neto' => '1.340,00 €',
                    'IVA 21%' => '281,40 €',
                    'Total' => '1.621,40 €',
                ])],
            ], [55, 45])
            ->addSpacer(5)
            ->addTitle('Ventas por trimestre', 2)
            ->addCss('.demo-bar { background: var(--fs-shade); margin-bottom: 2mm; }'
                . '.demo-bar > div { background: var(--fs-title-color); color: #fff; padding: 2px 8px;'
                . ' font-size: calc(var(--fs-font-size) - 2px); white-space: nowrap; }')
            ->addHtml($this->barChart([
                ['T1', 34, '8.120 €'],
                ['T2', 58, '13.900 €'],
                ['T3', 41, '9.800 €'],
                ['T4', 100, '23.960 €'],
            ]))
            ->addSpacer(3)
            ->addText('Este gráfico está construido con addCss() y addHtml(): cualquier plugin puede añadir '
                . 'sus propios estilos y componentes al documento.', 'text-center font-small');
    }

    protected function buildCoverPage(PDFBuilder $doc): void
    {
        $doc->addSpacer(45)
            ->addImage(FS_FOLDER . '/Dinamic/Assets/Images/horizontal-logo.png', 70, 'center')
            ->addSpacer(10)
            ->addTitle('Catálogo de componentes PDF', 1, 'center')
            ->addText('Documento de demostración generado dinámicamente con PDFBuilder.', 'text-center')
            ->addSpacer(10)
            ->addHtml('<div class="shade-box text-center">Core/Lib/PDF/Dynamic · html2pdf.js · '
                . 'previsualización, impresión y descarga desde el navegador</div>')
            ->addSpacer(45)
            ->addColumns([
                [new TitleBlock('11', 2, 'center'), new TextBlock('bloques de contenido', 'text-center font-small')],
                [new TitleBlock('3', 2, 'center'), new TextBlock('estilos de tabla', 'text-center font-small')],
                [new TitleBlock('5', 2, 'center'), new TextBlock('temas en esta demo', 'text-center font-small')],
                [new TitleBlock('A4', 2, 'center'), new TextBlock('vertical u horizontal', 'text-center font-small')],
            ]);
    }

    protected function barChart(array $data): string
    {
        $html = '';
        foreach ($data as [$label, $percent, $value]) {
            $html .= '<div class="demo-bar"><div style="width: ' . (int)$percent . '%;">'
                . htmlspecialchars($label . ' · ' . $value, ENT_QUOTES, 'UTF-8') . '</div></div>';
        }

        return $html;
    }
}
