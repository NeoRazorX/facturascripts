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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Dinamic\Model\AttachedFile;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Dinamic\Model\Divisa;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\FormatoDocumento;
use FacturaScripts\Dinamic\Model\Pais;

/**
 * PDF document data.
 *
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 */
class PDFDocument extends PDFCore
{

    /**
     *
     * @var FormatoDocumento
     */
    protected $format;

    /**
     * Combine address if the parameters don´t empty
     *
     * @param BusinessDocument|Contacto $model
     *
     * @return string
     */
    private function combineAddress($model): string
    {
        $completeAddress = Utils::fixHtml($model->direccion);
        $completeAddress .= empty($model->apartado) ? '' : ', ' . $this->i18n->trans('box') . ' ' . $model->apartado;
        $completeAddress .= empty($model->codpostal) ? '' : "\n" . $model->codpostal;
        $completeAddress .= empty($model->ciudad) ? '' : ', ' . Utils::fixHtml($model->ciudad);
        $completeAddress .= empty($model->provincia) ? '' : ' (' . Utils::fixHtml($model->provincia) . ')';
        $completeAddress .= empty($model->codpais) ? '' : ', ' . $this->getCountryName($model->codpais);
        return $completeAddress;
    }

    /**
     * Gets the name of the country with that code.
     *
     * @param string $code
     *
     * @return string
     */
    protected function getCountryName($code): string
    {
        if (empty($code)) {
            return '';
        }

        $country = new Pais();
        return $country->loadFromCode($code) ? Utils::fixHtml($country->nombre) : '';
    }

    /**
     * Gets the name of an specify divisa
     *
     * @param string $code
     *
     * @return string
     */
    protected function getDivisaName($code): string
    {
        if (empty($code)) {
            return '';
        }

        $divisa = new Divisa();
        return $divisa->loadFromCode($code) ? $divisa->descripcion : '';
    }

    /**
     * 
     * @param BusinessDocument $model
     *
     * @return FormatoDocumento
     */
    protected function getDocumentFormat($model)
    {
        $documentFormat = new FormatoDocumento();
        $where = [
            new DataBaseWhere('tipodoc', $model->modelClassName()),
            new DataBaseWhere('idempresa', $model->idempresa)
        ];
        foreach ($documentFormat->all($where, ['codserie' => 'DESC']) as $format) {
            if ($format->codserie == $model->codserie || null === $format->codserie) {
                return $format;
            }
        }

        return $documentFormat;
    }

    /**
     * Generate the body of the page with the model data.
     *
     * @param BusinessDocument $model
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
                'reference' => Utils::fixHtml($line->referencia . " - " . $line->descripcion),
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
            $this->pdf->ezText(Utils::fixHtml($model->observaciones) . "\n", self::FONT_SIZE);
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

        if (!empty($this->format->texto)) {
            $this->pdf->ezText("\n" . Utils::fixHtml($this->format->texto), self::FONT_SIZE);
        }
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

        if (!empty($this->format->titulo)) {
            $headerData['title'] = Utils::fixHtml($this->format->titulo);
        }

        $this->pdf->ezText("\n" . $headerData['title'] . ': ' . $model->codigo . "\n", self::FONT_SIZE + 6);
        $this->newLine();

        $subject = $model->getSubject();
        $tipoidfiscal = empty($subject->tipoidfiscal) ? $this->i18n->trans('cifnif') : $subject->tipoidfiscal;
        $tableData = [
            ['key' => $this->i18n->trans('date'), 'value' => $model->fecha],
            ['key' => $headerData['subject'], 'value' => Utils::fixHtml($model->{$headerData['fieldName']})],
            ['key' => $this->i18n->trans('number'), 'value' => $model->numero],
            ['key' => $tipoidfiscal, 'value' => $model->cifnif],
            ['key' => $this->i18n->trans('serie'), 'value' => $model->codserie],
        ];

        if (!empty($model->direccion)) {
            $tableData[] = ['key' => $this->i18n->trans('address'), 'value' => $this->combineAddress($model)];
        }

        /// rectified invoice?
        if (isset($model->codigorect) && !empty($model->codigorect)) {
            array_unshift($tableData, ['key' => $this->i18n->trans('original'), 'value' => $model->codigorect]);
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

        if (!empty($model->idcontactoenv) && $model->idcontactoenv != $model->idcontactofact) {
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
            $name = Utils::fixHtml($contacto->nombre) . ' ' . Utils::fixHtml($contacto->apellidos);
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
     * Inserts company logo to PDF document or dies with a message to try to solve the problem.
     * 
     * @param int $idfile
     */
    protected function insertCompanyLogo($idfile = 0)
    {
        if (!\function_exists('imagecreatefromstring')) {
            die('ERROR: function imagecreatefromstring() not found. '
                . ' Do you have installed php-gd package and enabled support to allow us render images? .'
                . 'Note that the package name can differ between operating system or PHP version.');
        }

        $xPos = $this->pdf->ez['leftMargin'];

        $logoFile = new AttachedFile();
        if ($idfile !== 0 && $logoFile->loadFromCode($idfile)) {
            $logoSize = $this->calcImageSize($logoFile->path);
            $yPos = $this->pdf->ez['pageHeight'] - $logoSize['height'] - $this->pdf->ez['topMargin'];
            $this->addImageFromAttachedFile($logoFile, $xPos, $yPos, $logoSize['width'], $logoSize['height']);
        } else {
            $logoPath = \FS_FOLDER . '/Core/Assets/Images/horizontal-logo.png';
            $logoSize = $this->calcImageSize($logoPath);
            $yPos = $this->pdf->ez['pageHeight'] - $logoSize['height'] - $this->pdf->ez['topMargin'];
            $this->addImageFromFile($logoPath, $xPos, $yPos, $logoSize['width'], $logoSize['height']);
        }

        /// add some margin
        $this->pdf->y -= 20;
    }

    /**
     * Insert footer details.
     */
    protected function insertFooter()
    {
        $now = $this->i18n->trans('generated-at', ['%when%' => date('d-m-Y H:i')]);
        $this->pdf->addText($this->tableWidth + self::CONTENT_X, self::FOOTER_Y, self::FONT_SIZE, $now, 0, 'right');
    }

    /**
     * Insert header details.
     *
     * @param int $idempresa
     */
    protected function insertHeader($idempresa = null)
    {
        if ($this->insertedHeader) {
            return;
        }

        $this->insertedHeader = true;

        $code = $idempresa ?? AppSettings::get('default', 'idempresa', '');
        $company = new Empresa();
        if ($company->loadFromCode($code)) {
            $this->pdf->ezText($company->nombre, self::FONT_SIZE + 9, ['justification' => 'right']);
            $address = $company->direccion;
            $address .= empty($company->codpostal) ? '' : "\n" . $company->codpostal . ', ';
            $address .= empty($company->ciudad) ? '' : $company->ciudad;
            $address .= empty($company->provincia) ? '' : ' (' . $company->provincia . ') ' . $this->getCountryName($company->codpais);
            $contactData = empty($company->telefono1) ? '' : $company->telefono1 . ' ';
            $contactData .= empty($company->telefono2) ? '' : $company->telefono2 . ' ';
            $contactData .= empty($company->email) ? '' : $company->email . ' ';
            $contactData .= empty($company->web) ? '' : $company->web;
            $lineText = $company->cifnif . ' - ' . $address . "\n" . $contactData;
            $this->pdf->ezText($lineText . "\n", self::FONT_SIZE, ['justification' => 'right']);

            $this->insertCompanyLogo($company->idlogo);
        }
    }
}
