<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib\PDF;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Dinamic\Model\AttachedFile;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Dinamic\Model\CuentaBanco;
use FacturaScripts\Dinamic\Model\CuentaBancoCliente;
use FacturaScripts\Dinamic\Model\Divisa;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\FormaPago;
use FacturaScripts\Dinamic\Model\FormatoDocumento;
use FacturaScripts\Dinamic\Model\Impuesto;
use FacturaScripts\Dinamic\Model\Pais;
use FacturaScripts\Dinamic\Model\ReciboCliente;

/**
 * PDF document data.
 *
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 */
abstract class PDFDocument extends PDFCore
{

    /**
     *
     * @var array
     */
    protected $lineHeaders;

    /**
     *
     * @var FormatoDocumento
     */
    protected $format;

    public function __construct()
    {
        parent::__construct();
        $this->lineHeaders = [
            'referencia' => ['type' => 'text', 'title' => $this->i18n->trans('reference') . ' - ' . $this->i18n->trans('description')],
            'cantidad' => ['type' => 'number', 'title' => $this->i18n->trans('quantity')],
            'pvpunitario' => ['type' => 'number', 'title' => $this->i18n->trans('price')],
            'dtopor' => ['type' => 'percentage', 'title' => $this->i18n->trans('dto')],
            'dtopor2' => ['type' => 'percentage', 'title' => $this->i18n->trans('dto-2')],
            'pvptotal' => ['type' => 'number', 'title' => $this->i18n->trans('net')],
            'iva' => ['type' => 'percentage', 'title' => $this->i18n->trans('tax')],
            'recargo' => ['type' => 'percentage', 'title' => $this->i18n->trans('re')],
            'irpf' => ['type' => 'percentage', 'title' => $this->i18n->trans('irpf')]
        ];
    }

    /**
     * Combine address if the parameters don´t empty
     *
     * @param BusinessDocument|Contacto $model
     *
     * @return string
     */
    protected function combineAddress($model): string
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
     * 
     * @param ReciboCliente $receipt
     *
     * @return string
     */
    protected function getBankData($receipt): string
    {
        $paymentMethod = new FormaPago();
        if (!$paymentMethod->loadFromCode($receipt->codpago)) {
            return '-';
        }

        $cuentaBancoCli = new CuentaBancoCliente();
        $where = [new DataBaseWhere('codcliente', $receipt->codcliente)];
        if ($paymentMethod->domiciliado && $cuentaBancoCli->loadFromCode('', $where)) {
            return $paymentMethod->descripcion . ' : ' . $cuentaBancoCli->getIban(true);
        }

        $cuentaBanco = new CuentaBanco();
        if (!empty($paymentMethod->codcuentabanco) && $cuentaBanco->loadFromCode($paymentMethod->codcuentabanco)) {
            return $paymentMethod->descripcion . ' : ' . $cuentaBanco->getIban(true);
        }

        return $paymentMethod->descripcion;
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
     */
    protected function getTaxesRows($model)
    {
        $eud = $model->getEUDiscount();

        $subtotals = [];
        foreach ($model->getLines() as $line) {
            if (empty($line->pvptotal) || $line->suplido) {
                continue;
            }

            $key = $line->codimpuesto . '_' . $line->iva . '_' . $line->recargo;
            if (!isset($subtotals[$key])) {
                $subtotals[$key] = [
                    'tax' => $key,
                    'taxbase' => 0,
                    'taxp' => $line->iva . '%',
                    'taxamount' => 0,
                    'taxsurchargep' => $line->recargo . '%',
                    'taxsurcharge' => 0
                ];

                $impuesto = new Impuesto();
                if (!empty($line->codimpuesto) && $impuesto->loadFromCode($line->codimpuesto)) {
                    $subtotals[$key]['tax'] = $impuesto->descripcion;
                }
            }

            $subtotals[$key]['taxbase'] += $line->pvptotal * $eud;
            $subtotals[$key]['taxamount'] += $line->pvptotal * $eud * $line->iva / 100;
            $subtotals[$key]['taxsurcharge'] += $line->pvptotal * $eud * $line->recargo / 100;
        }

        /// irpf
        foreach ($model->getLines() as $line) {
            if (empty($line->irpf)) {
                continue;
            }

            $key = 'irpf_' . $line->irpf;
            if (!isset($subtotals[$key])) {
                $subtotals[$key] = [
                    'tax' => $this->i18n->trans('irpf') . ' ' . $line->irpf . '%',
                    'taxbase' => 0,
                    'taxp' => $line->irpf . '%',
                    'taxamount' => 0,
                    'taxsurchargep' => 0,
                    'taxsurcharge' => 0
                ];
            }

            $subtotals[$key]['taxbase'] += $line->pvptotal * $eud;
            $subtotals[$key]['taxamount'] -= $line->pvptotal * $eud * $line->irpf / 100;
        }

        /// round
        foreach ($subtotals as $key => $value) {
            $subtotals[$key]['taxbase'] = $this->numberTools->format($value['taxbase']);
            $subtotals[$key]['taxamount'] = $this->numberTools->format($value['taxamount']);
            $subtotals[$key]['taxsurcharge'] = $this->numberTools->format($value['taxsurcharge']);
        }

        return $subtotals;
    }

    /**
     * Generate the body of the page with the model data.
     *
     * @param BusinessDocument $model
     */
    protected function insertBusinessDocBody($model)
    {
        $headers = [];
        $tableOptions = [
            'cols' => [],
            'shadeCol' => [0.95, 0.95, 0.95],
            'shadeHeadingCol' => [0.95, 0.95, 0.95],
            'width' => $this->tableWidth
        ];

        /// fill headers and options with the line headers information
        foreach ($this->lineHeaders as $key => $value) {
            $headers[$key] = $value['title'];
            if (\in_array($value['type'], ['number', 'percentage'], true)) {
                $tableOptions['cols'][$key] = ['justification' => 'right'];
            }
        }

        $tableData = [];
        foreach ($model->getlines() as $line) {
            $data = [];
            foreach ($this->lineHeaders as $key => $value) {
                if ($key === 'referencia') {
                    $data[$key] = empty($line->{$key}) ? Utils::fixHtml($line->descripcion) : Utils::fixHtml($line->{$key} . " - " . $line->descripcion);
                } elseif ($value['type'] === 'percentage') {
                    $data[$key] = $this->numberTools->format($line->{$key}) . '%';
                } elseif ($value['type'] === 'number') {
                    $data[$key] = $this->numberTools->format($line->{$key});
                } else {
                    $data[$key] = $line->{$key};
                }
            }

            $tableData[] = $data;
        }

        $this->removeEmptyCols($tableData, $headers, $this->numberTools->format(0));
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
            $this->pdf->ezText($this->i18n->trans('observations') . "\n", self::FONT_SIZE);
            $this->newLine();
            $this->pdf->ezText(Utils::fixHtml($model->observaciones) . "\n", self::FONT_SIZE);
        }

        $this->newPage();

        /// subtotals
        $headers = [
            'currency' => $this->i18n->trans('currency'),
            'subtotal' => $this->i18n->trans('subtotal'),
            'dto' => $this->i18n->trans('global-dto'),
            'dto-2' => $this->i18n->trans('global-dto-2'),
            'net' => $this->i18n->trans('net'),
            'taxes' => $this->i18n->trans('taxes'),
            'totalSurcharge' => $this->i18n->trans('re'),
            'totalIrpf' => $this->i18n->trans('irpf'),
            'totalSupplied' => $this->i18n->trans('supplied-amount'),
            'total' => $this->i18n->trans('total')
        ];
        $rows = [
            [
                'currency' => $this->getDivisaName($model->coddivisa),
                'subtotal' => $this->numberTools->format($model->netosindto != $model->neto ? $model->netosindto : 0),
                'dto' => $this->numberTools->format($model->dtopor1) . '%',
                'dto-2' => $this->numberTools->format($model->dtopor2) . '%',
                'net' => $this->numberTools->format($model->neto),
                'taxes' => $this->numberTools->format($model->totaliva),
                'totalSurcharge' => $this->numberTools->format($model->totalrecargo),
                'totalIrpf' => $this->numberTools->format(0 - $model->totalirpf),
                'totalSupplied' => $this->numberTools->format($model->totalsuplidos),
                'total' => $this->numberTools->format($model->total)
            ]
        ];
        $this->removeEmptyCols($rows, $headers, $this->numberTools->format(0));
        $tableOptions = [
            'cols' => [
                'subtotal' => ['justification' => 'right'],
                'dto' => ['justification' => 'right'],
                'dto-2' => ['justification' => 'right'],
                'net' => ['justification' => 'right'],
                'taxes' => ['justification' => 'right'],
                'totalSurcharge' => ['justification' => 'right'],
                'totalIrpf' => ['justification' => 'right'],
                'totalSupplied' => ['justification' => 'right'],
                'total' => ['justification' => 'right']
            ],
            'shadeCol' => [0.95, 0.95, 0.95],
            'shadeHeadingCol' => [0.95, 0.95, 0.95],
            'width' => $this->tableWidth
        ];
        $this->pdf->ezTable($rows, $headers, '', $tableOptions);

        /// receipts
        if ($model->modelClassName() === 'FacturaCliente') {
            $this->insertInvoiceReceipts($model);
        }

        /// taxes
        $taxHeaders = [
            'tax' => $this->i18n->trans('tax'),
            'taxbase' => $this->i18n->trans('tax-base'),
            'taxp' => $this->i18n->trans('percentage'),
            'taxamount' => $this->i18n->trans('amount'),
            'taxsurchargep' => $this->i18n->trans('re'),
            'taxsurcharge' => $this->i18n->trans('amount')
        ];
        $taxRows = $this->getTaxesRows($model);
        $taxTableOptions = [
            'cols' => [
                'tax' => ['justification' => 'right'],
                'taxbase' => ['justification' => 'right'],
                'taxp' => ['justification' => 'right'],
                'taxamount' => ['justification' => 'right'],
                'taxsurchargep' => ['justification' => 'right'],
                'taxsurcharge' => ['justification' => 'right']
            ],
            'shadeCol' => [0.95, 0.95, 0.95],
            'shadeHeadingCol' => [0.95, 0.95, 0.95],
            'width' => $this->tableWidth
        ];
        if (count($taxRows) > 1) {
            $this->removeEmptyCols($taxRows, $taxHeaders, $this->numberTools->format(0));
            $this->pdf->ezText("\n");
            $this->pdf->ezTable($taxRows, $taxHeaders, $this->i18n->trans('taxes'), $taxTableOptions);
        }

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
            'title' => $this->i18n->trans($model->modelClassName() . '-min'),
            'subject' => $this->i18n->trans('customer'),
            'fieldName' => 'nombrecliente'
        ];

        if (isset($model->codproveedor)) {
            $headerData['subject'] = $this->i18n->trans('supplier');
            $headerData['fieldName'] = 'nombre';
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
            ['key' => $this->i18n->trans('serie'), 'value' => $model->codserie]
        ];
        if (empty($model->cifnif)) {
            unset($tableData[3]);
        }

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
            'cols' => []
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
    protected function insertBusinessDocShipping($model)
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
                'cols' => []
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
        if ($idfile !== 0 && $logoFile->loadFromCode($idfile) && file_exists($logoFile->path)) {
            $logoSize = $this->calcImageSize($logoFile->path);
            $yPos = $this->pdf->ez['pageHeight'] - $logoSize['height'] - $this->pdf->ez['topMargin'];
            $this->addImageFromAttachedFile($logoFile, $xPos, $yPos, $logoSize['width'], $logoSize['height']);
        } else {
            $logoPath = \FS_FOLDER . '/Dinamic/Assets/Images/horizontal-logo.png';
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
        if (false === $company->loadFromCode($code)) {
            return;
        }

        $size = \mb_strlen($company->nombre) > 20 ? self::FONT_SIZE + 2 : self::FONT_SIZE + 7;
        $this->pdf->ezText(Utils::fixHtml($company->nombre), $size, ['justification' => 'right']);
        $address = $company->direccion;
        $address .= empty($company->codpostal) ? "\n" : "\n" . $company->codpostal . ', ';
        $address .= empty($company->ciudad) ? '' : $company->ciudad;
        $address .= empty($company->provincia) ? '' : ' (' . $company->provincia . ') ' . $this->getCountryName($company->codpais);

        $contactData = [];
        foreach (['telefono1', 'telefono2', 'email', 'web'] as $field) {
            if (!empty($company->{$field})) {
                $contactData[] = $company->{$field};
            }
        }

        $lineText = $company->cifnif . ' - ' . Utils::fixHtml($address) . "\n\n" . implode(' · ', $contactData);
        $this->pdf->ezText($lineText, self::FONT_SIZE, ['justification' => 'right']);

        $idlogo = $this->format->idlogo ?? $company->idlogo;
        $this->insertCompanyLogo($idlogo);
    }

    /**
     * 
     * @param FacturaCliente $invoice
     */
    protected function insertInvoiceReceipts($invoice)
    {
        $receipts = $invoice->getReceipts();
        if (count($receipts) > 0) {
            $this->newPage();

            $headers = [
                'numero' => $this->i18n->trans('receipt'),
                'bank' => $this->i18n->trans('payment-method'),
                'importe' => $this->i18n->trans('amount'),
                'vencimiento' => $this->i18n->trans('expiration')
            ];
            $rows = [];
            foreach ($receipts as $receipt) {
                $paylink = $receipt->url('pay');
                $rows[] = [
                    'numero' => $receipt->numero,
                    'bank' => empty($paylink) ? $this->getBankData($receipt) : '<c:alink:' . $paylink . '>'
                    . $this->i18n->trans('pay') . '</c:alink>',
                    'importe' => $this->numberTools->format($receipt->importe),
                    'vencimiento' => $receipt->pagado ? $this->i18n->trans('paid') : $receipt->vencimiento
                ];
            }
            $tableOptions = [
                'cols' => [
                    'numero' => ['justification' => 'center'],
                    'bank' => ['justification' => 'center'],
                    'importe' => ['justification' => 'right'],
                    'vencimiento' => ['justification' => 'right']
                ],
                'shadeCol' => [0.95, 0.95, 0.95],
                'shadeHeadingCol' => [0.95, 0.95, 0.95],
                'width' => $this->tableWidth
            ];
            $this->pdf->ezTable($rows, $headers, '', $tableOptions);
        }
    }
}
