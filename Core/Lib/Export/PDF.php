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
use FPDF;

class PDF extends PdfEngine
{
    use ExtensionsTrait;

    /** @var string */
    protected $file_name;

    /** @var string */
    protected $orientation;

    /** @var FPDF */
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
    }

    public function addCompanyHeader(int $idempresa): void
    {
        if (!$this->show_header) {
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
    }

    public function addHtml(string $html): self
    {
        if (empty($html)) {
            return $this;
        }

        if (false === $this->pipeFalse('addHtml', $html)) {
            return $this;
        }

        return $this;
    }

    public function addImage(string $filePath): self
    {
        if (false === $this->pipeFalse('addImage', $filePath)) {
            return $this;
        }

        $this->pdf->Image($filePath);

        return $this;
    }

    public function addModel(ModelClass $model): self
    {
        if (false === $this->pipeFalse('addModel', $model)) {
            return $this;
        }

        $this->newPage();

        switch ($model->modelClassName()) {
            default:
                $this->addText($model->modelClassName() . ': ' . $model->primaryDescription());
                break;

            case 'AlbaranCliente':
            case 'FacturaCliente':
            case 'PedidoCliente':
            case 'PresupuestoCliente':
                $this->addSalesDocument($model);
                break;

            case 'AlbaranProveedor':
            case 'FacturaProveedor':
            case 'PedidoProveedor':
            case 'PresupuestoProveedor':
                $this->addPurchaseDocument($model);
                break;

            case 'Asiento':
                $this->addAccountingEntry($model);
                break;
        }

        return $this;
    }

    public function addModelList(array $list, array $header = [], array $options = []): self
    {
        if (empty($list)) {
            return $this;
        }

        if (false === $this->pipeFalse('addModelList', $list, $header, $options)) {
            return $this;
        }

        return $this;
    }

    public function addTable(array $rows, array $header = [], array $options = []): self
    {
        if (empty($rows)) {
            return $this;
        }

        if (false === $this->pipeFalse('addTable', $rows, $header, $options)) {
            return $this;
        }

        // Calcula el ancho disponible de la página
        $anchoPagina = $this->pdf->GetPageWidth() - (2 * $this->pdf->GetX()); // 2 * margen izquierdo

        // Calcula el ancho igual para todas las columnas
        $anchoColumna = $anchoPagina / count($header);

        // Crea la tabla
        $this->pdf->SetFont('Arial', 'B', 12);
        $this->pdf->SetFillColor(150, 150, 150); // Establece el color de fondo de las celdas de la cabecera
        $this->pdf->SetTextColor(255); // Establece el color del texto de la cabecera

        // Imprime las cabeceras con el ancho ajustado
        foreach ($header as $cabecera) {
            $this->pdf->Cell($anchoColumna, 10, $cabecera, 1, 0, 'C', 1);
        }

        $this->pdf->Ln(); // Salta a la siguiente línea

        // Restablece el color de fondo y el color del texto
        $this->pdf->SetFillColor(224, 224, 224);
        $this->pdf->SetTextColor(0);

        // Imprime las filas con el ancho ajustado
        foreach ($rows as $fila) {
            foreach ($fila as $columna) {
                $this->pdf->Cell($anchoColumna, 10, $columna, 1);
            }
            $this->pdf->Ln(); // Salta a la siguiente línea
        }

        return $this;
    }

    public function addText(string $text, array $options = []): self
    {
        if (empty($text)) {
            return $this;
        }

        if (false === $this->pipeFalse('addText', $text, $options)) {
            return $this;
        }

        // si no termina en salto de línea, lo añadimos
        if (substr($text, -1) !== "\n") {
            $text .= "\n";
        }

        $this->pdf->Write(10, $text);

        return $this;
    }

    public static function create(string $size = 'a4', string $orientation = 'portrait'): self
    {
        return new self($size, $orientation);
    }

    public function newPage(): self
    {
        if (false === $this->pipeFalse('newPage')) {
            return $this;
        }

        if (null === $this->pdf) {
            $this->pdf = new FPDF($this->orientation, 'pt', $this->size);
            $this->pdf->SetTitle($this->title);
            $this->pdf->SetAuthor(Session::user()->nick);
            $this->pdf->SetCreator('FacturaScripts');
            $this->pdf->SetFont('Arial', '', 10);
            $this->pdf->AddPage();
            return $this;
        }

        $this->pdf->AddPage($this->orientation, $this->size);
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

    protected function addAccountingEntry(Asiento $model): void
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

    protected function addPurchaseDocument(PurchaseDocument $doc): void
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

    protected function addSalesDocument(SalesDocument $doc): void
    {
        // cabecera
        $this->addCompanyHeader($doc->idempresa);
        $this->addText($this->trans($doc->modelClassName() . '-min') . ': ' . $doc->codigo);
        $this->addText($this->trans('date') . ': ' . $doc->fecha);
        $this->addText($this->trans('customer') . ': ' . $doc->nombrecliente);
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
}
