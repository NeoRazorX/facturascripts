<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use Dompdf\Dompdf;
use FacturaScripts\Core\Base\ExtensionsTrait;
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\Model\Asiento;
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\PurchaseDocument;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Template\PdfEngine;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\AttachedFile;

class PDF extends PdfEngine
{
    use ExtensionsTrait;

    /** @var string */
    protected $file_name;

    /** @var string */
    protected $font_family = 'DejaVuSans';

    /** @var int */
    protected $font_size = 10;

    /** @var string */
    protected $font_weight = '';

    /** @var string */
    protected $html = '';

    /** @var string */
    protected $orientation;

    /** @var Dompdf */
    protected $pdf;

    /** @var bool */
    protected $show_footer = true;

    /** @var bool */
    protected $show_header = true;

    /** @var string */
    protected $size;

    /** @var string */
    protected $title;

    public function __construct(string $size = 'a4', string $orientation = 'portrait')
    {
        $this->file_name = 'doc_' . date('YmdHis') . '_' . rand(1, 9999) . '.pdf';
        $this->orientation = $orientation;
        $this->size = $size;
        $this->title = 'DOC ' . date('YmdHis') . '_' . rand(1, 9999);

        $this->pipeFalse('init', $size, $orientation);
    }

    public function addCompanyHeader(int $idempresa): void
    {
        if (!$this->show_header) {
            return;
        }

        if (false === $this->pipeFalse('addCompanyHeaderBefore', $idempresa)) {
            return;
        }

        $company = Empresas::get($idempresa);

        // si la empresa tiene logo, lo añadimos
        $attFile = new AttachedFile();
        if (!empty($company->idlogo) && $attFile->loadFromCode($company->idlogo)) {
            $this->addImage($attFile->getFullPath());
        }

        $this->addText($company->nombre, ['font-size' => 16, 'font-weight' => 'bold']);
        $this->addText($company->cifnif);
        $this->addText($company->direccion . ', ' . $company->codpostal . ' ' . $company->ciudad
            . ' (' . $company->provincia . ')');

        $phones = [];
        if ($company->telefono1) {
            $phones[] = $company->telefono1;
        }
        if ($company->telefono2) {
            $phones[] = $company->telefono2;
        }

        $phoneAndEmail = '';
        if ($phones) {
            $phoneAndEmail .= $this->trans('phone') . ': ' . implode(', ', $phones);
        }
        if ($company->email) {
            $phoneAndEmail .= $this->trans('email') . ': ' . $company->email;
        }
        if ($phoneAndEmail) {
            $this->addText($phoneAndEmail);
        }

        $this->addText("\n");

        $this->pipeFalse('addCompanyHeaderAfter', $idempresa);
    }

    public function addHtml(string $html): self
    {
        if (empty($html)) {
            return $this;
        }

        if (false === $this->pipeFalse('addHtmlBefore', $html)) {
            return $this;
        }

        $this->html .= $html;

        $this->pipeFalse('addHtmlAfter', $html);

        return $this;
    }

    public function addImage(string $filePath): self
    {
        if (false === $this->pipeFalse('addImageBefore', $filePath)) {
            return $this;
        }

        $this->html .= '<img src="' . $filePath . '" />';

        $this->pipeFalse('addImageAfter', $filePath);

        return $this;
    }

    public function addModel(ModelClass $model, array $options = []): self
    {
        if (false === $this->pipeFalse('addModelBefore', $model, $options)) {
            return $this;
        }

        $this->newPage();

        switch ($model->modelClassName()) {
            default:
                $this->addDefaultModel($model, $options);
                break;

            case 'AlbaranCliente':
            case 'FacturaCliente':
            case 'PedidoCliente':
            case 'PresupuestoCliente':
                $this->addSalesDocument($model, $options);
                break;

            case 'AlbaranProveedor':
            case 'FacturaProveedor':
            case 'PedidoProveedor':
            case 'PresupuestoProveedor':
                $this->addPurchaseDocument($model, $options);
                break;

            case 'Asiento':
                $this->addAccountingEntry($model, $options);
                break;
        }

        $this->pipeFalse('addModelAfter', $model, $options);

        return $this;
    }

    public function addModelList(array $list, array $header = [], array $options = []): self
    {
        if (empty($list)) {
            return $this;
        }

        if (false === $this->pipeFalse('addModelListBefore', $list, $header, $options)) {
            return $this;
        }

        $this->newPage();

        // convertimos los datos en array
        $rows = [];
        foreach ($list as $model) {
            $rows[] = $model->toArray();
        }

        $this->addTable($rows, $header, $options);

        $this->pipeFalse('addModelListAfter', $list, $header, $options);

        return $this;
    }

    public function addTable(array $rows, array $header = [], array $options = []): self
    {
        if (empty($rows)) {
            return $this;
        }

        if (false === $this->pipeFalse('addTableBefore', $rows, $header, $options)) {
            return $this;
        }

        // si la cabecera está vacía, la generamos a partir de la primera fila
        if (empty($header)) {
            $header = array_keys($rows[0]);
        }

        $html = '<table width="100%">';

        // cabecera
        $html .= '<thead><tr>';
        foreach ($header as $key => $value) {
            $align = $options['col-align'][$key] ?? 'left';
            $style = 'text-align: ' . $align . '; background-color: #eee;';
            $html .= '<th style="' . $style . '">' . $value . '</th>';
        }
        $html .= '</tr></thead>';

        // cuerpo
        $html .= '<tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $key => $value) {
                $align = $options['col-align'][$key] ?? 'left';
                $style = 'text-align: ' . $align . '; border-bottom: 1px solid #ddd;';
                $html .= '<td style="' . $style . '">' . $value . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';

        $html .= '</table>';

        $this->html .= $html;

        $this->pipeFalse('addTableAfter', $rows, $header, $options);

        return $this;
    }

    public function addText(string $text, array $options = []): self
    {
        if (empty($text)) {
            return $this;
        }

        if (false === $this->pipeFalse('addTextBefore', $text, $options)) {
            return $this;
        }

        $align = $options['align'] ?? 'left';
        $font_size = $options['font-size'] ?? $this->font_size;
        $font_weight = $options['font-weight'] ?? $this->font_weight;
        $this->html .= '<p style="text-align: ' . $align . '; font-size: ' . $font_size
            . 'px; font-weight: ' . $font_weight . ';">' . $text . '</p>';

        $this->pipeFalse('addTextAfter', $text, $options);

        return $this;
    }

    public static function create(string $size = 'a4', string $orientation = 'portrait'): self
    {
        return new self($size, $orientation);
    }

    public function newPage(): self
    {
        if (false === $this->pipeFalse('newPageBefore')) {
            return $this;
        }

        if (null === $this->pdf) {
            $this->pdf = new Dompdf();
            $this->pdf->setPaper($this->size, $this->orientation);
            return $this;
        }

        $this->pipeFalse('newPageAfter');

        return $this;
    }

    public function output(): string
    {
        if (null === $this->pdf) {
            $this->newPage();
        }

        $this->html = '<html><head><meta charset="utf-8"><title>' . $this->title . '</title></head><body>'
            . $this->html
            . '</body></html>';

        $this->pdf->loadHtml($this->html);
        $this->pdf->render();

        return $this->pdf->output();
    }

    public function save(string $filePath): bool
    {
        if (false === $this->pipeFalse('save', $filePath)) {
            return false;
        }

        return file_put_contents($filePath, $this->output()) !== false;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        $this->file_name = Tools::slug($title, '_');

        return $this;
    }

    public function setOrientation(string $orientation): self
    {
        $this->orientation = $orientation;

        return $this;
    }

    public function setSize(string $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function showFooter(bool $show): self
    {
        $this->show_footer = $show;

        return $this;
    }

    public function showHeader(bool $show): self
    {
        $this->show_header = $show;

        return $this;
    }

    protected function addAccountingEntry(Asiento $model, array $options = []): void
    {
        // cabecera
        $this->addCompanyHeader($model->getExercise()->idempresa);
        $this->addText($this->trans('accounting-entry') . ' ' . $model->numero, [
            'font-size' => 14,
            'font-weight' => 'bold',
        ]);
        $this->addText($this->trans('date') . ': ' . $model->fecha);
        $this->addText("\n");

        // líneas
        $header = [
            'account' => $this->trans('account'),
            'description' => $this->trans('description'),
            'debit' => $this->trans('debit'),
            'credit' => $this->trans('credit')
        ];
        $rows = [];
        foreach ($model->getLines() as $line) {
            $rows[] = [
                'account' => $line->codsubcuenta,
                'description' => $line->getSubcuenta()->descripcion,
                'debit' => Tools::money($line->debe),
                'credit' => Tools::money($line->haber),
            ];
        }
        $this->addTable($rows, $header, [
            'col-align' => [
                'debit' => 'right',
                'credit' => 'right'
            ],
        ]);
    }

    protected function addDefaultModel(ModelClass $model, array $options = []): void
    {
        // si el modelo tiene idempresa, añadimos la cabecera de la empresa
        if (property_exists($model, 'idempresa')) {
            $this->addCompanyHeader($model->idempresa);
        } elseif (method_exists($model, 'getExercise')) {
            $this->addCompanyHeader($model->getExercise()->idempresa);
        }

        $this->addText($model->modelClassName() . ': ' . $model->primaryDescription(), [
            'align' => 'center',
            'font-size' => 16,
            'font-weight' => 'bold',
        ]);

        // obtenemos los datos del modelo como array
        $data = $model->toArray();

        // los imprimimos en una tabla donde la primera columna es la clave y la segunda el valor
        $rows = [];
        foreach ($data as $key => $value) {
            // excluimos la clave primaria
            if ($key === $model->primaryColumn()) {
                continue;
            }

            $rows[] = ['key' => $key, 'value' => $value];
        }

        $this->addTable($rows);
    }

    protected function addPurchaseDocument(PurchaseDocument $doc, array $options = []): void
    {
        // cabecera
        $this->addCompanyHeader($doc->idempresa);
        $this->addText($this->trans($doc->modelClassName() . '-min') . ' ' . $doc->codigo, [
            'font-size' => 14,
            'font-weight' => 'bold',
        ]);
        $this->addText($this->trans('date') . ': ' . $doc->fecha);
        $this->addText($this->trans('supplier') . ': ' . $doc->nombre);
        $this->addText("\n");

        // líneas
        $header = [
            'reference' => $this->trans('reference'),
            'description' => $this->trans('description'),
            'quantity' => $this->trans('quantity'),
            'price' => $this->trans('price')
        ];
        $rows = [];
        foreach ($doc->getLines() as $line) {
            $rows[] = [
                'reference' => $line->referencia,
                'description' => $line->descripcion,
                'quantity' => $line->cantidad,
                'price' => Tools::money($line->pvpunitario),
            ];
        }
        $this->addTable($rows, $header, [
            'col-align' => [
                'quantity' => 'right',
                'price' => 'right'
            ],
        ]);

        // totales
        $this->addText("\n");
        $header = [
            'net' => $this->trans('net'),
            'taxes' => $this->trans('taxes'),
            'total' => $this->trans('total')
        ];
        $rows = [
            'net' => Tools::money($doc->neto),
            'taxes' => Tools::money($doc->totaliva),
            'total' => Tools::money($doc->total)
        ];
        $this->addTable([$rows], $header, [
            'col-align' => [
                'net' => 'right',
                'taxes' => 'right',
                'total' => 'right'
            ],
        ]);

        if ($doc->observaciones) {
            $this->addText($doc->observaciones);
        }
    }

    protected function addSalesDocument(SalesDocument $doc, array $options = []): void
    {
        $logo = $doc->getCompany()->getLogo();
        $logo_img = $logo->exists() ?
            $logo->getFullPath() :
            Tools::folder('Dinamic/Assets/Images/horizontal-logo.png');

        // cabecera
        $html = '<table style="width: 100%; margin-bottom: 30px;">'
            . '<tr>'
            . '<td>'
            . '<h1 style="margin-bottom: 5px;">' . strtoupper($this->trans($doc->modelClassName() . '-min')) . '</h1>'
            . $this->trans('code') . ': ' . $doc->codigo . '<br>'
            . $this->trans('date') . ': ' . $doc->fecha
            . '</td>'
            . '<td style="text-align: right;">'
            . '<img src="' . $this->getImgSrc($logo_img) . '" alt="logo" style="max-width: 300px;" />'
            . '</td>'
            . '</tr>'
            . '</table>';
        $this->addHtml($html);

        // líneas
        $header = [
            'reference' => $this->trans('reference'),
            'description' => $this->trans('description'),
            'quantity' => $this->trans('quantity'),
            'price' => $this->trans('price')
        ];
        $rows = [];
        foreach ($doc->getLines() as $line) {
            $rows[] = [
                'reference' => $line->referencia,
                'description' => $line->descripcion,
                'quantity' => $line->cantidad,
                'price' => Tools::money($line->pvpunitario),
            ];
        }
        $this->addTable($rows, $header, [
            'col-align' => [
                'quantity' => 'right',
                'price' => 'right'
            ],
        ]);

        // totales
        $this->addText("\n");
        $header = [
            'net' => $this->trans('net'),
            'taxes' => $this->trans('taxes'),
            'total' => $this->trans('total')
        ];
        $rows = [
            'net' => Tools::money($doc->neto),
            'taxes' => Tools::money($doc->totaliva),
            'total' => Tools::money($doc->total)
        ];
        $this->addTable([$rows], $header, [
            'col-align' => [
                'net' => 'right',
                'taxes' => 'right',
                'total' => 'right'
            ],
        ]);

        if ($doc->observaciones) {
            $this->addText($doc->observaciones);
        }
    }

    protected function getImgSrc(string $filePath): string
    {
        return 'data:image/' . pathinfo($filePath, PATHINFO_EXTENSION) . ';base64,'
            . base64_encode(file_get_contents($filePath));
    }
}
