<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib\Export;

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Dinamic\Model\Contacto;
use Symfony\Component\HttpFoundation\Response;

/**
 * PDF export data.
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Carlos Jiménez Gómez         <carlos@evolunext.es>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class PDFExport extends PDFDocument implements ExportInterface
{

    const LIST_LIMIT = 500;

    /**
     * Adds a new page with the document data.
     *
     * @param BusinessDocument $model
     */
    public function generateBusinessDocPage($model)
    {
        $this->newPage();
        $this->insertHeader($model->idempresa);
        $this->insertBusinessDocHeader($model);
        $this->insertBusinessDocBody($model);
        $this->insertBusinessDocFooter($model);
    }

    /**
     * Adds a new page with a table listing the models data.
     *
     * @param mixed                         $model
     * @param Base\DataBase\DataBaseWhere[] $where
     * @param array                         $order
     * @param int                           $offset
     * @param array                         $columns
     * @param string                        $title
     */
    public function generateListModelPage($model, $where, $order, $offset, $columns, $title = '')
    {
        $orientation = 'portrait';
        $tableCols = [];
        $tableColsTitle = [];
        $tableOptions = ['cols' => [], 'shadeCol' => [0.95, 0.95, 0.95], 'shadeHeadingCol' => [0.95, 0.95, 0.95]];
        $tableData = [];
        $longTitles = [];

        /// turns widget columns into needed arrays
        $this->setTableColumns($columns, $tableCols, $tableColsTitle, $tableOptions);
        if (count($tableCols) > 5) {
            $orientation = 'landscape';
            $this->removeLongTitles($longTitles, $tableColsTitle);
        }

        $this->newPage($orientation);
        $tableOptions['width'] = $this->tableWidth;
        $this->insertHeader();

        $cursor = $model->all($where, $order, $offset, self::LIST_LIMIT);
        if (empty($cursor)) {
            $this->pdf->ezTable($tableData, $tableColsTitle, '', $tableOptions);
        }
        while (!empty($cursor)) {
            $tableData = $this->getTableData($cursor, $tableCols, $tableOptions);
            $this->removeEmptyCols($tableData, $tableColsTitle);
            $this->pdf->ezTable($tableData, $tableColsTitle, $title, $tableOptions);

            /// Advance within the results
            $offset += self::LIST_LIMIT;
            $cursor = $model->all($where, $order, $offset, self::LIST_LIMIT);
        }

        $this->newLongTitles($longTitles);
        $this->insertFooter();
    }

    /**
     * Adds a new page with the model data.
     *
     * @param mixed  $model
     * @param array  $columns
     * @param string $title
     */
    public function generateModelPage($model, $columns, $title = '')
    {
        $this->newPage();
        $this->insertHeader();

        $tableCols = [];
        $tableColsTitle = [];
        $tableOptions = [
            'width' => $this->tableWidth,
            'showHeadings' => 0,
            'shaded' => 0,
            'lineCol' => [1, 1, 1],
            'cols' => [],
        ];

        /// Get the columns
        $this->setTableColumns($columns, $tableCols, $tableColsTitle, $tableOptions);

        $tableDataAux = [];
        foreach ($tableColsTitle as $key => $colTitle) {
            $value = null;
            if (isset($model->{$key})) {
                $value = $model->{$key};
            }

            if (is_bool($value)) {
                $txt = $this->i18n->trans($value ? 'yes' : 'no');
                $tableDataAux[] = ['key' => $colTitle, 'value' => $txt];
            } elseif ($value !== null && $value !== '') {
                $value = is_string($value) ? Base\Utils::fixHtml($value) : $value;
                $tableDataAux[] = ['key' => $colTitle, 'value' => $value];
            }
        }

        $this->pdf->ezText("\n" . $title . "\n", self::FONT_SIZE + 6);
        $this->newLine();

        $this->insertParalellTable($tableDataAux, '', $tableOptions);
        $this->insertFooter();
    }

    /**
     * Adds a new page with the table.
     *
     * @param array $headers
     * @param array $rows
     */
    public function generateTablePage($headers, $rows)
    {
        $orientation = (count($headers) > 5) ? 'landscape' : 'portrait';
        $tableOptions = ['width' => $this->tableWidth];

        $this->newPage($orientation);
        $this->insertHeader();
        $this->pdf->ezTable($rows, $headers, '', $tableOptions);
        $this->insertFooter();
    }

    /**
     * Return the full document.
     *
     * @return mixed
     */
    public function getDoc()
    {
        if ($this->pdf === null) {
            $this->newPage();
            $this->pdf->ezText('');
        }

        return $this->pdf->ezStream(['Content-Disposition' => 'doc_' . mt_rand(1, 999999) . '.pdf']);
    }

    /**
     * Blank document.
     */
    public function newDoc()
    {
        ;
    }

    /**
     * Set headers and output document content to response.
     *
     * @param Response $response
     */
    public function show(Response &$response)
    {
        $response->headers->set('Content-type', 'application/pdf');
        $response->setContent($this->getDoc());
    }

    /**
     * Generate the body of the page with the model data.
     *
     * @param mixed $model
     */
    protected function insertBusinessDocBody($model)
    {
        $headers = [
            'reference' => $this->i18n->trans('reference') . ' - ' . $this->i18n->trans('description'),
            'quantity' => $this->i18n->trans('quantity'),
            'price' => $this->i18n->trans('price'),
            'discount' => $this->i18n->trans('discount'),
            'tax' => $this->i18n->trans('tax'),
            'surcharge' => $this->i18n->trans('surcharge'),
            'irpf' => $this->i18n->trans('irpf'),
            'total' => $this->i18n->trans('total'),
        ];
        $tableData = [];
        foreach ($model->getlines() as $line) {
            $tableData[] = [
                'reference' => Base\Utils::fixHtml($line->referencia . " - " . $line->descripcion),
                'quantity' => $this->numberTools->format($line->cantidad),
                'price' => $this->numberTools->format($line->pvpunitario),
                'discount' => $this->numberTools->format($line->dtopor),
                'tax' => $this->numberTools->format($line->iva),
                'surcharge' => $this->numberTools->format($line->recargo),
                'irpf' => $this->numberTools->format($line->irpf),
                'total' => $this->numberTools->format($line->pvptotal),
            ];
        }

        $this->removeEmptyCols($tableData, $headers, $this->numberTools->format(0));
        $tableOptions = [
            'cols' => [
                'quantity' => ['justification' => 'right'],
                'price' => ['justification' => 'right'],
                'discount' => ['justification' => 'right'],
                'tax' => ['justification' => 'right'],
                'surcharge' => ['justification' => 'right'],
                'irpf' => ['justification' => 'right'],
                'total' => ['justification' => 'right'],
            ],
            'shadeCol' => [0.95, 0.95, 0.95],
            'shadeHeadingCol' => [0.95, 0.95, 0.95],
            'width' => $this->tableWidth
        ];
        $this->pdf->ezTable($tableData, $headers, '', $tableOptions);
    }

    /**
     * Inserts the footer of the page with the model data.
     *
     * @param BusinessDocument $model
     */
    protected function insertBusinessDocFooter($model)
    {
        if (!empty($model->observaciones)) {
            $this->newPage();
            $this->pdf->ezText($this->i18n->trans('notes') . "\n", self::FONT_SIZE);
            $this->newLine();
            $this->pdf->ezText(Base\Utils::fixHtml($model->observaciones) . "\n", self::FONT_SIZE);
        }

        $this->newPage();
        $headers = [
            'currency' => $this->i18n->trans('currency'),
            'net' => $this->i18n->trans('net'),
            'taxes' => $this->i18n->trans('taxes'),
            'totalSurcharge' => $this->i18n->trans('surcharge'),
            'totalIrpf' => $this->i18n->trans('irpf'),
            'total' => $this->i18n->trans('total'),
        ];
        $rows = [
            [
                'currency' => $this->getDivisaName($model->coddivisa),
                'net' => $this->numberTools->format($model->neto),
                'taxes' => $this->numberTools->format($model->totaliva),
                'totalSurcharge' => $this->numberTools->format($model->totalrecargo),
                'totalIrpf' => $this->numberTools->format($model->totalirpf),
                'total' => $this->numberTools->format($model->total),
            ]
        ];
        $this->removeEmptyCols($rows, $headers, $this->numberTools->format(0));
        $tableOptions = [
            'cols' => [
                'net' => ['justification' => 'right'],
                'taxes' => ['justification' => 'right'],
                'totalSurcharge' => ['justification' => 'right'],
                'totalIrpf' => ['justification' => 'right'],
                'total' => ['justification' => 'right'],
            ],
            'shadeCol' => [0.95, 0.95, 0.95],
            'shadeHeadingCol' => [0.95, 0.95, 0.95],
            'width' => $this->tableWidth
        ];
        $this->pdf->ezTable($rows, $headers, '', $tableOptions);
    }

    /**
     * Inserts the header of the page with the model data.
     *
     * @param BusinessDocument $model
     */
    protected function insertBusinessDocHeader($model)
    {
        $headerData = [
            'title' => $this->i18n->trans('delivery-note'),
            'subject' => $this->i18n->trans('customer'),
            'fieldName' => 'nombrecliente'
        ];

        if (isset($model->codproveedor)) {
            $headerData['subject'] = $this->i18n->trans('supplier');
            $headerData['fieldName'] = 'nombre';
        }

        switch ($model->modelClassName()) {
            case 'FacturaProveedor':
            case 'FacturaCliente':
                $headerData['title'] = $this->i18n->trans('invoice');
                break;

            case 'PedidoProveedor':
            case 'PedidoCliente':
                $headerData['title'] = $this->i18n->trans('order');
                break;

            case 'PresupuestoProveedor':
            case 'PresupuestoCliente':
                $headerData['title'] = $this->i18n->trans('estimation');
                break;
        }

        $this->pdf->ezText("\n" . $headerData['title'] . ' ' . $model->codigo . "\n", self::FONT_SIZE + 6);
        $this->newLine();

        $tableData = [
            ['key' => $this->i18n->trans('date'), 'value' => $model->fecha],
            ['key' => $headerData['subject'], 'value' => Base\Utils::fixHtml($model->{$headerData['fieldName']})],
            ['key' => $this->i18n->trans('cifnif'), 'value' => $model->cifnif],
        ];

        if (!empty($model->direccion)) {
            $tableData[] = ['key' => $this->i18n->trans('address'), 'value' => $this->combineAddress($model)];
        }

        $tableOptions = [
            'width' => $this->tableWidth,
            'showHeadings' => 0,
            'shaded' => 0,
            'lineCol' => [1, 1, 1],
            'cols' => [],
        ];
        $this->insertParalellTable($tableData, '', $tableOptions);
        $this->pdf->ezText('');

        if (!empty($model->idcontactoenv)) {
            $this->insertBusinessDocShipping($model);
        }
    }

    /**
     * Inserts the address of delivery with the model data.
     *
     * @param BusinessDocument $model
     */
    private function insertBusinessDocShipping($model)
    {
        $this->pdf->ezText("\n" . $this->i18n->trans('shipping-address') . "\n", self::FONT_SIZE + 6);
        $this->newLine();

        $contacto = new Contacto();
        if ($contacto->loadFromCode($model->idcontactoenv)) {
            $name = Base\Utils::fixHtml($contacto->nombre) . ' ' . Base\Utils::fixHtml($contacto->apellidos);
            $tableData = [
                ['key' => $this->i18n->trans('name'), 'value' => $name],
                ['key' => $this->i18n->trans('address'), 'value' => $this->combineAddress($contacto)],
            ];

            $tableOptions = [
                'width' => $this->tableWidth,
                'showHeadings' => 0,
                'shaded' => 0,
                'lineCol' => [1, 1, 1],
                'cols' => [],
            ];
            $this->insertParalellTable($tableData, '', $tableOptions);
            $this->pdf->ezText('');
        }
    }

    /**
     * Combine address if the parameters don´t empty
     *
     * @param BusinessDocument|Contacto $model
     *
     * @return string
     */
    private function combineAddress($model): string
    {
        $completeAddress = Base\Utils::fixHtml($model->direccion);
        $completeAddress .= isset($model->apartado) ? ', ' . $this->i18n->trans('box') . ' ' . $model->apartado : '';
        $completeAddress .= isset($model->codpostal) ? ', ' . $model->codpostal : '';
        $completeAddress .= isset($model->ciudad) ? ', ' . Base\Utils::fixHtml($model->ciudad) : '';
        $completeAddress .= isset($model->provincia) ? ' (' . Base\Utils::fixHtml($model->provincia) . ')' : '';
        $completeAddress .= isset($model->codpais) ? ' ' . $this->getCountryName($model->codpais) : ',';

        return $completeAddress;
    }
}
