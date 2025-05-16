<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\AjaxForms;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Impuestos;
use FacturaScripts\Core\DataSrc\Retenciones;
use FacturaScripts\Core\DataSrc\Series;
use FacturaScripts\Core\Lib\ProductType;
use FacturaScripts\Core\Lib\RegimenIVA;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\BusinessDocumentLine;
use FacturaScripts\Core\Model\Base\TransformerDocument;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Stock;
use FacturaScripts\Dinamic\Model\Variante;

trait CommonLineHTML
{
    /** @var string */
    protected static $columnView;

    /** @var int */
    protected static $num = 0;

    /** @var int */
    protected static $numlines = 0;

    /** @var string */
    protected static $regimeniva;

    /** @var array */
    private static $variants = [];

    /** @var array */
    private static $stocks = [];

    private static function cantidadRestante(BusinessDocumentLine $line, TransformerDocument $model): string
    {
        if ($line->servido <= 0 || false === $model->editable) {
            return '';
        }

        $restante = $line->cantidad - $line->servido;
        return '<div class="input-group-prepend" title="' . Tools::lang()->trans('quantity-remaining') . '">'
            . '<a href="DocumentStitcher?model=' . $model->modelClassName() . '&codes=' . $model->primaryColumnValue()
            . '" class="btn btn-outline-secondary" type="button">' . $restante . '</a>'
            . '</div>';
    }

    private static function codimpuesto(string $idlinea, BusinessDocumentLine $line, TransformerDocument $model, string $jsFunc): string
    {
        // comprobamos el régimen de IVA del cliente o proveedor
        if (!isset(self::$regimeniva)) {
            self::$regimeniva = $model->getSubject()->regimeniva;
        }

        // necesitamos una opción vacía para cuando el sujeto está exento de impuestos
        $options = ['<option value="">------</option>'];
        foreach (Impuestos::all() as $imp) {
            // si el impuesto no está activo o seleccionado, lo saltamos
            if (!$imp->activo && $line->codimpuesto != $imp->codimpuesto) {
                continue;
            }

            $options[] = $line->codimpuesto == $imp->codimpuesto ?
                '<option value="' . $imp->codimpuesto . '" selected>' . $imp->descripcion . '</option>' :
                '<option value="' . $imp->codimpuesto . '">' . $imp->descripcion . '</option>';
        }

        // solamente se puede cambiar el impuesto si el documento es editable,
        // el sujeto no está exento de impuestos, la serie tiene impuestos
        // y la línea no tiene suplidos
        $editable = $model->editable && self::$regimeniva != RegimenIVA::TAX_SYSTEM_EXEMPT
            && false == Series::get($model->codserie)->siniva && false == $line->suplido;

        $attributes = $editable ?
            'name="codimpuesto_' . $idlinea . '" onchange="return ' . $jsFunc . '(\'recalculate-line\', \'0\');"' :
            'disabled=""';
        return '<div class="col-sm col-lg-1 order-6">'
            . '<div class="d-lg-none mt-3 small">' . Tools::lang()->trans('tax') . '</div>'
            . '<select ' . $attributes . ' class="form-select form-select-sm border-0">' . implode('', $options) . '</select>'
            . '<input type="hidden" name="iva_' . $idlinea . '" value="' . $line->iva . '"/>'
            . '</div>';
    }

    private static function descripcion(string $idlinea, BusinessDocumentLine $line, TransformerDocument $model): string
    {
        $attributes = $model->editable ? 'name="descripcion_' . $idlinea . '"' : 'disabled=""';

        $rows = 0;
        foreach (explode("\n", $line->descripcion) as $desLine) {
            $rows += mb_strlen($desLine) < 90 ? 1 : ceil(mb_strlen($desLine) / 90);
        }

        $columnMd = empty($line->referencia) ? 12 : 8;
        $columnSm = empty($line->referencia) ? 10 : 8;
        return '<div class="col-sm-' . $columnSm . ' col-md-' . $columnMd . ' col-lg order-2">'
            . '<div class="d-lg-none mt-3 small">' . Tools::lang()->trans('description') . '</div>'
            . '<textarea ' . $attributes . ' class="form-control form-control-sm border-0 doc-line-desc" rows="' . $rows . '">'
            . $line->descripcion . '</textarea></div>';
    }

    private static function dtopor(string $idlinea, BusinessDocumentLine $line, TransformerDocument $model, string $jsFunc): string
    {
        $attributes = $model->editable ?
            'name="dtopor_' . $idlinea . '" min="0" max="100" step="1" onkeyup="return ' . $jsFunc . '(\'recalculate-line\', \'0\', event);"' :
            'disabled=""';
        return '<div class="col-sm col-lg-1 order-5">'
            . '<div class="d-lg-none mt-3 small">' . Tools::lang()->trans('percentage-discount') . '</div>'
            . '<input type="number" ' . $attributes . ' value="' . $line->dtopor . '" class="form-control form-control-sm text-lg-center border-0"/>'
            . '</div>';
    }

    private static function dtopor2(string $idlinea, BusinessDocumentLine $line, TransformerDocument $model, string $field, string $jsFunc): string
    {
        $attributes = $model->editable ?
            'name="' . $field . '_' . $idlinea . '" min="0" max="100" step="1" onkeyup="return ' . $jsFunc . '(\'recalculate-line\', \'0\', event);"' :
            'disabled=""';
        return '<div class="col-6">'
            . '<div class="mb-2">' . Tools::lang()->trans('percentage-discount') . ' 2'
            . '<input type="number" ' . $attributes . ' value="' . $line->{$field} . '" class="form-control"/>'
            . '</div>'
            . '</div>';
    }

    private static function excepcioniva(string $idlinea, BusinessDocumentLine $line, TransformerDocument $model, string $field, string $jsFunc): string
    {
        $attributes = $model->editable ?
            'name="excepcioniva_' . $idlinea . '" onchange="return ' . $jsFunc . '(\'recalculate-line\', \'0\');"' :
            'disabled=""';

        $options = '<option value="" selected>------</option>';
        $product = $line->getProducto();
        $excepcionIva = empty($line->idlinea) && empty($line->{$field}) ? $product->{$field} : $line->{$field};

        foreach (RegimenIVA::allExceptions() as $key => $value) {
            $selected = $excepcionIva === $key ? 'selected' : '';
            $options .= '<option value="' . $key . '" ' . $selected . '>' . Tools::lang()->trans($value) . '</option>';
        }

        return '<div class="col-6">'
            . '<div class="mb-2">' . Tools::lang()->trans('vat-exception')
            . '<select ' . $attributes . ' class="form-select">' . $options . '</select>'
            . '</div>'
            . '</div>';
    }

    private static function genericBool(string $idlinea, BusinessDocumentLine $line, TransformerDocument $model, string $field, string $label): string
    {
        $attributes = $model->editable ? 'name="' . $field . '_' . $idlinea . '"' : 'disabled=""';
        $options = $line->{$field} ?
            ['<option value="0">' . Tools::lang()->trans('no') . '</option>', '<option value="1" selected>' . Tools::lang()->trans('yes') . '</option>'] :
            ['<option value="0" selected>' . Tools::lang()->trans('no') . '</option>', '<option value="1">' . Tools::lang()->trans('yes') . '</option>'];
        return '<div class="col-6">'
            . '<div class="mb-2">' . Tools::lang()->trans($label)
            . '<select ' . $attributes . ' class="form-select">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    private static function irpf(string $idlinea, BusinessDocumentLine $line, TransformerDocument $model, string $jsFunc): string
    {
        $options = ['<option value="">------</option>'];
        foreach (Retenciones::all() as $ret) {
            // si la retención no está activa o seleccionada, la saltamos
            if (!$ret->activa && $line->irpf != $ret->porcentaje) {
                continue;
            }

            $options[] = $line->irpf === $ret->porcentaje ?
                '<option value="' . $ret->porcentaje . '" selected>' . $ret->descripcion . '</option>' :
                '<option value="' . $ret->porcentaje . '">' . $ret->descripcion . '</option>';
        }

        $attributes = $model->editable && false === $line->suplido ?
            'name="irpf_' . $idlinea . '" onchange="return ' . $jsFunc . '(\'recalculate-line\', \'0\', event);"' :
            'disabled=""';
        return '<div class="col-6">'
            . '<div class="mb-2"><a href="ListImpuesto?activetab=ListRetencion">' . Tools::lang()->trans('retention') . '</a>'
            . '<select ' . $attributes . ' class="form-select">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    private static function lineTotal(string $idlinea, BusinessDocumentLine $line, TransformerDocument $model, string $jsSubtotal, string $jsNeto): string
    {
        if ('subtotal' === self::$columnView) {
            $cssSubtotal = '';
            $cssNeto = 'd-none';
        } else {
            $cssSubtotal = 'd-none';
            $cssNeto = '';
        }

        $onclickSubtotal = $model->editable ?
            ' onclick="' . $jsSubtotal . '(\'' . $idlinea . '\')"' :
            '';

        $onclickNeto = $model->editable ?
            ' onclick="' . $jsNeto . '(\'' . $idlinea . '\')"' :
            '';

        $subtotal = self::subtotalValue($line, $model);
        return '<div class="col col-lg-1 order-7 columSubtotal ' . $cssSubtotal . '">'
            . '<div class="d-lg-none mt-2 small">' . Tools::lang()->trans('subtotal') . '</div>'
            . '<input type="number" name="linetotal_' . $idlinea . '"  value="' . number_format($subtotal, FS_NF0, '.', '')
            . '" class="form-control form-control-sm text-lg-end border-0"' . $onclickSubtotal . ' readonly/></div>'
            . '<div class="col col-lg-1 order-7 columNeto ' . $cssNeto . '">'
            . '<div class="d-lg-none mt-2 small">' . Tools::lang()->trans('net') . '</div>'
            . '<input type="number" name="lineneto_' . $idlinea . '"  value="' . number_format($line->pvptotal, FS_NF0, '.', '')
            . '" class="form-control form-control-sm text-lg-end border-0"' . $onclickNeto . ' readonly/></div>';
    }

    private static function loadProducts(array $lines, BusinessDocument $model): void
    {
        // cargamos las referencias
        $references = [];
        foreach ($lines as $line) {
            if (!empty($line->referencia)) {
                $references[] = $line->referencia;
            }
        }
        if (empty($references)) {
            return;
        }

        // cargamos las variantes
        $variantModel = new Variante();
        $where = [new DataBaseWhere('referencia', $references, 'IN')];
        foreach ($variantModel->all($where, [], 0, 0) as $variante) {
            self::$variants[$variante->referencia] = $variante;
        }

        // cargamos los stocks
        $stockModel = new Stock();
        $where = [
            new DataBaseWhere('codalmacen', $model->codalmacen),
            new DataBaseWhere('referencia', $references, 'IN'),
        ];
        foreach ($stockModel->all($where, [], 0, 0) as $stock) {
            self::$stocks[$stock->referencia] = $stock;
        }
    }

    private static function recargo(string $idlinea, BusinessDocumentLine $line, TransformerDocument $model, string $jsFunc): string
    {
        // comprobamos el régimen de IVA del cliente o proveedor
        if (!isset(self::$regimeniva)) {
            self::$regimeniva = $model->getSubject()->regimeniva;
        }

        // solamente se puede cambiar el recargo si el documento es editable,
        // la línea no es un suplido, la serie no es sin IVA y el régimen de IVA es recargo de equivalencia
        $editable = $model->editable
            && false === $line->suplido
            && false === Series::get($model->codserie)->siniva
            && (self::$regimeniva === RegimenIVA::TAX_SYSTEM_SURCHARGE || $model->getCompany()->regimeniva === RegimenIVA::TAX_SYSTEM_SURCHARGE);

        $attributes = $editable ?
            'name="recargo_' . $idlinea . '" min="0" max="100" step="1" onkeyup="return ' . $jsFunc . '(\'recalculate-line\', \'0\', event);"' :
            'disabled=""';
        return '<div class="col-6">'
            . '<div class="mb-2"><a href="ListImpuesto">' . Tools::lang()->trans('percentage-surcharge') . '</a>'
            . '<input type="number" ' . $attributes . ' value="' . $line->recargo . '" class="form-control"/>'
            . '</div>'
            . '</div>';
    }

    private static function referencia(string $idlinea, BusinessDocumentLine $line, TransformerDocument $model): string
    {
        $sortable = $model->editable ?
            '<input type="hidden" name="orden_' . $idlinea . '" value="' . $line->orden . '"/>' :
            '';
        $numlinea = self::$numlines > 10 ? self::$num . '. ' : '';

        if (empty($line->referencia)) {
            return '<div class="col-sm-2 col-lg-1 order-1">' . $sortable . '<div class="small text-break">' . $numlinea . '</div></div>';
        }

        $link = isset(self::$variants[$line->referencia]) ?
            $numlinea . '<a href="' . self::$variants[$line->referencia]->url() . '" target="_blank">' . $line->referencia . '</a>' :
            $line->referencia;

        return '<div class="col-sm-2 col-lg-1 order-1">'
            . '<div class="small text-break"><div class="d-lg-none mt-2 text-truncate">' . Tools::lang()->trans('reference') . '</div>'
            . $sortable . $link . '<input type="hidden" name="referencia_' . $idlinea . '" value="' . $line->referencia . '"/>'
            . '</div>'
            . '</div>';
    }

    private static function renderExpandButton(string $idlinea, TransformerDocument $model, string $jsName): string
    {
        if ($model->editable) {
            return '<div class="col-auto order-9">'
                . '<button type="button" data-bs-toggle="modal" data-bs-target="#lineModal-' . $idlinea . '" class="btn btn-sm btn-light me-2" title="'
                . Tools::lang()->trans('more') . '"><i class="fa-solid fa-ellipsis-h"></i></button>'
                . '<button class="btn btn-sm btn-danger btn-spin-action" type="button" title="' . Tools::lang()->trans('delete') . '"'
                . ' onclick="return ' . $jsName . '(\'rm-line\', \'' . $idlinea . '\');">'
                . '<i class="fa-solid fa-trash-alt"></i></button>'
                . '</div>';
        }

        return '<div class="col-auto order-9"><button type="button" data-bs-toggle="modal" data-bs-target="#lineModal-'
            . $idlinea . '" class="btn btn-sm btn-outline-secondary" title="'
            . Tools::lang()->trans('more') . '"><i class="fa-solid fa-ellipsis-h"></i></button></div>';
    }

    private static function subtotalValue(BusinessDocumentLine $line, TransformerDocument $model): float
    {
        if ($model->subjectColumn() === 'codcliente'
            && $model->getCompany()->regimeniva === RegimenIVA::TAX_SYSTEM_USED_GOODS
            && $line->getProducto()->tipo === ProductType::SECOND_HAND) {
            $profit = $line->pvpunitario - $line->coste;
            $tax = $profit * ($line->iva + $line->recargo - $line->irpf) / 100;
            return ($line->coste + $profit + $tax) * $line->cantidad;
        }

        return $line->pvptotal * (100 + $line->iva + $line->recargo - $line->irpf) / 100;
    }

    private static function suplido(string $idlinea, BusinessDocumentLine $line, TransformerDocument $model, string $jsFunc): string
    {
        $attributes = $model->editable ?
            'name="suplido_' . $idlinea . '" onchange="return ' . $jsFunc . '(\'recalculate-line\', \'0\', event);"' :
            'disabled=""';
        $options = $line->suplido ?
            ['<option value="0">' . Tools::lang()->trans('no') . '</option>', '<option value="1" selected>' . Tools::lang()->trans('yes') . '</option>'] :
            ['<option value="0" selected>' . Tools::lang()->trans('no') . '</option>', '<option value="1">' . Tools::lang()->trans('yes') . '</option>'];
        return '<div class="col-6">'
            . '<div class="mb-2">' . Tools::lang()->trans('supplied')
            . '<select ' . $attributes . ' class="form-select">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    private static function titleActionsButton(TransformerDocument $model): string
    {
        $width = $model->editable ? 68 : 32;
        return '<div class="col-lg-auto order-8"><div style="min-width: ' . $width . 'px;"></div></div>';
    }

    private static function titleCantidad(): string
    {
        return '<div class="col-lg-1 text-end order-3">' . Tools::lang()->trans('quantity') . '</div>';
    }

    private static function titleCodimpuesto(): string
    {
        return '<div class="col-lg-1 order-6"><a href="ListImpuesto">' . Tools::lang()->trans('tax') . '</a></div>';
    }

    private static function titleDescripcion(): string
    {
        return '<div class="col-lg order-2">' . Tools::lang()->trans('description') . '</div>';
    }

    private static function titleDtopor(): string
    {
        return '<div class="col-lg-1 text-center order-5">' . Tools::lang()->trans('percentage-discount') . '</div>';
    }

    private static function titlePrecio(): string
    {
        return '<div class="col-lg-1 text-end order-4">' . Tools::lang()->trans('price') . '</div>';
    }

    private static function titleReferencia(): string
    {
        return '<div class="col-lg-1 order-1">' . Tools::lang()->trans('reference') . '</div>';
    }

    private static function titleTotal(): string
    {
        if ('subtotal' === self::$columnView) {
            $cssSubtotal = '';
            $cssNeto = 'd-none';
        } else {
            $cssSubtotal = 'd-none';
            $cssNeto = '';
        }

        return '<div class="col-lg-1 text-end order-7 columSubtotal ' . $cssSubtotal . '">' . Tools::lang()->trans('subtotal') . '</div>'
            . '<div class="col-lg-1 text-end order-7 columNeto ' . $cssNeto . '">' . Tools::lang()->trans('net') . '</div>';
    }
}
