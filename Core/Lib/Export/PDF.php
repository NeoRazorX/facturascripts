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

use FacturaScripts\Core\Base\ExtensionsTrait;
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\Model\Asiento;
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\PurchaseDocument;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Template\PdfEngine;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\AttachedFile;
use tFPDF;

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
    protected $orientation;

    /** @var tFPDF */
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

        // no soportado

        $this->pipeFalse('addHtmlAfter', $html);

        return $this;
    }

    public function addImage(string $filePath): self
    {
        if (false === $this->pipeFalse('addImageBefore', $filePath)) {
            return $this;
        }

        $this->pdf->Image($filePath);

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

        $this->checkOptions($options);

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

        $this->checkOptions($options);

        // si la cabecera está vacía, la generamos a partir de la primera fila
        if (empty($header)) {
            $header = array_keys($rows[0]);
        }

        // Calcula el ancho disponible de la página
        $anchoPagina = $this->pdf->GetPageWidth() - (2 * $this->pdf->GetX()); // 2 * margen izquierdo

        // Calcula el ancho igual para todas las columnas
        $anchoColumna = $anchoPagina / count($header);

        // Crea la tabla
        $this->pdf->SetFont($this->font_family, 'B', 11);
        $this->pdf->SetFillColor(150, 150, 150); // Establece el color de fondo de las celdas de la cabecera
        $this->pdf->SetTextColor(255); // Establece el color del texto de la cabecera

        // Imprime las cabeceras con el ancho ajustado
        foreach ($header as $cabecera) {
            $this->pdf->Cell($anchoColumna, 15, $cabecera, 1, 0, 'C', 1);
        }

        $this->pdf->Ln(); // Salta a la siguiente línea

        // Restablece el color de fondo, el color del texto y el tipo de letra
        $this->pdf->SetFillColor(224, 224, 224);
        $this->pdf->SetTextColor(0);
        $this->pdf->SetFont($this->font_family, $this->font_weight, $this->font_size);

        // Imprime las filas con el ancho ajustado
        foreach ($rows as $fila) {
            foreach ($fila as $columna) {
                $this->pdf->Cell($anchoColumna, 15, $columna, 1);
            }
            $this->pdf->Ln(); // Salta a la siguiente línea
        }

        $this->pdf->Ln(); // Salta a la siguiente línea

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

        // si no termina en salto de línea, lo añadimos
        if (substr($text, -1) !== "\n") {
            $text .= "\n";
        }

        $this->checkOptions($options);

        // cambiamos el tamaño de la fuente
        $this->pdf->SetFont($this->font_family, $options['font-weight'], $options['font-size']);

        $this->pdf->MultiCell(0, $options['font-size'] + 5, $text, 0, $options['align']);

        // volvemos al tamaño de fuente por defecto
        $this->pdf->SetFont($this->font_family, $this->font_weight, $this->font_size);

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
            $this->pdf = new tFPDF($this->orientation, 'pt', $this->size);
            $this->pdf->SetTitle($this->title);
            $this->pdf->SetAuthor(Session::user()->nick);
            $this->pdf->SetCreator('FacturaScripts');

            // añadimos las fuentes
            $this->loadFonts();

            // establece la fuente por defecto
            $this->pdf->SetFont($this->font_family, $this->font_weight, $this->font_size);

            $this->pdf->AddPage();
            return $this;
        }

        $this->pdf->AddPage($this->orientation, $this->size);

        $this->pipeFalse('newPageAfter');

        return $this;
    }

    public function output(): string
    {
        if (null === $this->pdf) {
            $this->newPage();
        }

        return $this->pdf->output('', 'S');
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
        $this->addText($this->trans('accounting-entry') . ': ' . $model->numero);
        $this->addText($this->trans('date') . ': ' . $model->fecha);
        $this->addText("\n");

        // líneas
        $header = ['account', 'description', 'debit', 'credit'];
        $rows = [];
        foreach ($model->getLines() as $line) {
            $rows[] = [
                $line->codsubcuenta,
                $line->getSubcuenta()->descripcion,
                $line->debe,
                $line->haber,
            ];
        }
        $this->addTable($rows, $header);
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
        $this->addText($this->trans($doc->modelClassName() . '-min') . ': ' . $doc->codigo);
        $this->addText($this->trans('date') . ': ' . $doc->fecha);
        $this->addText($this->trans('supplier') . ': ' . $doc->nombre);
        $this->addText("\n");

        // líneas
        $header = ['reference', 'description', 'quantity', 'price'];
        $rows = [];
        foreach ($doc->getLines() as $line) {
            $rows[] = [
                $line->referencia,
                $line->descripcion,
                $line->cantidad,
                $line->pvpunitario,
            ];
        }
        $this->addTable($rows, $header);

        // totales
        $this->addText("\n");
        $header = ['base', 'tax', 'total'];
        $rows = [$doc->neto, $doc->totaliva, $doc->total];
        $this->addTable([$rows], $header);

        if ($doc->observaciones) {
            $this->addText($doc->observaciones);
        }
    }

    protected function addSalesDocument(SalesDocument $doc, array $options = []): void
    {
        // cabecera
        $this->addCompanyHeader($doc->idempresa);
        $this->addText($this->trans($doc->modelClassName() . '-min') . ': ' . $doc->codigo);
        $this->addText($this->trans('date') . ': ' . $doc->fecha);
        $this->addText($this->trans('customer') . ': ' . $doc->nombrecliente);
        $this->addText("\n");

        // líneas
        $header = [
            $this->trans('reference'),
            $this->trans('description'),
            $this->trans('quantity'),
            $this->trans('price')
        ];
        $rows = [];
        foreach ($doc->getLines() as $line) {
            $rows[] = [
                $line->referencia,
                $line->descripcion,
                $line->cantidad,
                $line->pvpunitario,
            ];
        }
        $this->addTable($rows, $header);

        // totales
        $this->addText("\n");
        $header = [
            $this->trans('net'),
            $this->trans('tax'),
            $this->trans('total')
        ];
        $rows = [$doc->neto, $doc->totaliva, $doc->total];
        $this->addTable([$rows], $header);

        if ($doc->observaciones) {
            $this->addText($doc->observaciones);
        }
    }

    protected function checkOptions(array &$options): void
    {
        switch ($options['align'] ?? '') {
            case 'C':
            case 'center':
                $options['align'] = 'C';
                break;

            case 'R':
            case 'right':
                $options['align'] = 'R';
                break;

            default:
                $options['align'] = 'left';
                break;
        }

        if (isset($options['font-size'])) {
            $options['font-size'] = (int)$options['font-size'];
        } else {
            $options['font-size'] = $this->font_size;
        }

        switch ($options['font-weight'] ?? '') {
            case 'B':
            case 'bold':
                $options['font-weight'] = 'B';
                break;

            case 'I':
            case 'italic':
                $options['font-weight'] = 'I';
                break;

            case 'U':
            case 'underline':
                $options['font-weight'] = 'U';
                break;

            default:
                $options['font-weight'] = '';
                break;
        }
    }

    protected function loadFonts(): void
    {
        if (false === $this->pipeFalse('loadFontsBefore')) {
            return;
        }

        $this->pdf->AddFont($this->font_family, '', 'DejaVuSans.ttf', true);
        $this->pdf->AddFont($this->font_family, 'B', 'DejaVuSans-Bold.ttf', true);

        $this->pipeFalse('loadFontsAfter');
    }
}
