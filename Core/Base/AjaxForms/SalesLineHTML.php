<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Base\AjaxForms;

use FacturaScripts\Core\Base\Contract\SalesLineModInterface;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\Base\SalesDocumentLine;
use FacturaScripts\Dinamic\Model\Stock;
use FacturaScripts\Dinamic\Model\Variante;

/**
 * Description of SalesLineHTML
 *
 * @author Carlos Garcia Gomez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class SalesLineHTML
{
    use CommonLineHTML;

    /** @var array */
    private static $deletedLines = [];

    /** @var SalesLineModInterface[] */
    private static $mods = [];

    /** @var int */
    private static $num = 0;

    public static function addMod(SalesLineModInterface $mod)
    {
        self::$mods[] = $mod;
    }

    /**
     * @param SalesDocument $model
     * @param SalesDocumentLine[] $lines
     * @param array $formData
     */
    public static function apply(SalesDocument &$model, array &$lines, array $formData)
    {
        // update or remove lines
        $rmLineId = $formData['action'] === 'rm-line' ? $formData['selectedLine'] : 0;
        foreach ($lines as $key => $value) {
            if ($value->idlinea === (int)$rmLineId || false === isset($formData['cantidad_' . $value->idlinea])) {
                self::$deletedLines[] = $value->idlinea;
                unset($lines[$key]);
                continue;
            }

            self::applyToLine($formData, $value, $value->idlinea);
        }

        // new lines
        for ($num = 1; $num < 1000; $num++) {
            if (isset($formData['cantidad_n' . $num]) && $rmLineId !== 'n' . $num) {
                $newLine = isset($formData['referencia_n' . $num]) ?
                    $model->getNewProductLine($formData['referencia_n' . $num]) : $model->getNewLine();
                $idNewLine = 'n' . $num;
                self::applyToLine($formData, $newLine, $idNewLine);
                $lines[] = $newLine;
            }
        }

        // add new line
        if ($formData['action'] === 'add-product' || $formData['action'] === 'fast-product') {
            $lines[] = $model->getNewProductLine($formData['selectedLine']);
        } elseif ($formData['action'] === 'fast-line') {
            $newLine = self::getFastLine($model, $formData);
            if ($newLine) {
                $lines[] = $newLine;
            }
        } elseif ($formData['action'] === 'new-line') {
            $lines[] = $model->getNewLine();
        }

        // mods
        foreach (self::$mods as $mod) {
            $mod->apply($model, $lines, $formData);
        }
    }

    public static function assets()
    {
        // mods
        foreach (self::$mods as $mod) {
            $mod->assets();
        }
    }

    public static function getDeletedLines(): array
    {
        return self::$deletedLines;
    }

    /**
     * @param SalesDocumentLine[] $lines
     * @param SalesDocument $model
     *
     * @return array
     */
    public static function map(array $lines, SalesDocument $model): array
    {
        $map = [];
        foreach ($lines as $line) {
            self::$num++;
            $idlinea = $line->idlinea ?? 'n' . self::$num;

            // codimpuesto
            $map['iva_' . $idlinea] = $line->iva;

            // total
            $map['linetotal_' . $idlinea] = $line->pvptotal * (100 + $line->iva + $line->recargo - $line->irpf) / 100;
        }

        // mods
        foreach (self::$mods as $mod) {
            foreach ($mod->map($lines, $model) as $key => $value) {
                $map[$key] = $value;
            }
        }

        return $map;
    }

    /**
     * @param SalesDocumentLine[] $lines
     * @param SalesDocument $model
     *
     * @return string
     */
    public static function render(array $lines, SalesDocument $model): string
    {
        $i18n = new Translator();
        $html = '';
        foreach ($lines as $line) {
            $html .= self::renderLine($i18n, $line, $model);
        }
        if (empty($html)) {
            $html .= '<div class="container-fluid"><div class="form-row table-warning"><div class="col p-3 text-center">'
                . $i18n->trans('new-invoice-line-p') . '</div></div></div>';
        }
        return empty($model->codcliente) ? '' : self::renderTitles($i18n, $model) . $html;
    }

    public static function renderLine(Translator $i18n, SalesDocumentLine $line, SalesDocument $model): string
    {
        self::$num++;
        $idlinea = $line->idlinea ?? 'n' . self::$num;
        return '<div class="container-fluid"><div class="form-row align-items-center border-bottom pb-3 pb-lg-0">'
            . self::renderField($i18n, $idlinea, $line, $model, 'referencia')
            . self::renderField($i18n, $idlinea, $line, $model, 'descripcion')
            . self::renderField($i18n, $idlinea, $line, $model, 'cantidad')
            . self::renderNewFields($i18n, $idlinea, $line, $model)
            . self::renderField($i18n, $idlinea, $line, $model, 'pvpunitario')
            . self::renderField($i18n, $idlinea, $line, $model, 'dtopor')
            . self::renderField($i18n, $idlinea, $line, $model, 'codimpuesto')
            . self::renderField($i18n, $idlinea, $line, $model, '_total')
            . self::renderExpandButton($i18n, $idlinea, $model, 'salesFormAction')
            . '</div>' . self::renderLineModal($i18n, $line, $idlinea, $model) . '</div>';
    }

    private static function applyToLine(array $formData, SalesDocumentLine &$line, string $id)
    {
        $line->orden = (int)$formData['orden_' . $id];
        $line->cantidad = (float)$formData['cantidad_' . $id];
        $line->dtopor = (float)$formData['dtopor_' . $id];
        $line->dtopor2 = (float)$formData['dtopor2_' . $id];
        $line->descripcion = $formData['descripcion_' . $id];
        $line->irpf = (float)($formData['irpf_' . $id] ?? '0');
        $line->iva = (float)($formData['iva_' . $id] ?? '0');
        $line->recargo = (float)($formData['recargo_' . $id] ?? '0');
        $line->suplido = (bool)($formData['suplido_' . $id] ?? '0');
        $line->pvpunitario = (float)$formData['pvpunitario_' . $id];

        // mods
        foreach (self::$mods as $mod) {
            $mod->applyToLine($formData, $line, $id);
        }
    }

    private static function cantidad(Translator $i18n, string $idlinea, SalesDocumentLine $line, SalesDocument $model, string $jsFunc): string
    {
        if (false === $model->editable) {
            return '<div class="col-sm-2 col-lg-1 order-3">'
                . '<div class="d-lg-none mt-2 small">' . $i18n->trans('quantity') . '</div>'
                . '<div class="input-group input-group-sm">'
                . self::cantidadServido($i18n, $line, $model)
                . '<input type="number" class="form-control border-0" value="' . $line->cantidad . '" disabled=""/>'
                . '</div>'
                . '</div>';
        }

        return '<div class="col-sm-2 col-lg-1 order-3">'
            . '<div class="d-lg-none mt-2 small">' . $i18n->trans('quantity') . '</div>'
            . '<div class="input-group input-group-sm">'
            . self::cantidadServido($i18n, $line, $model)
            . '<input type="number" name="cantidad_' . $idlinea . '" value="' . $line->cantidad
            . '" class="form-control border-0 doc-line-qty" onkeyup="return ' . $jsFunc . '(\'recalculate-line\', \'0\');"/>'
            . self::cantidadStock($i18n, $line, $model)
            . '</div>'
            . '</div>';
    }

    private static function cantidadServido(Translator $i18n, SalesDocumentLine $line, SalesDocument $model): string
    {
        $html = '';
        if (empty($line->referencia) || $line->modelClassName() === 'LineaFacturaCliente') {
            return $html;
        }

        if (false === $model->editable) {
            $html .= '<div class="input-group-prepend" title="' . $i18n->trans('quantity-served') . '">';
            $html .= $line->servido == $line->cantidad ?
                '<span class="input-group-text text-success rounded-0">' . $line->servido . '</span>' :
                '<span class="input-group-text text-warning rounded-0">' . $line->servido . '</span>';
            $html .= '</div>';
        }
        return $html;
    }

    private static function cantidadStock(Translator $i18n, SalesDocumentLine $line, SalesDocument $model): string
    {
        $html = '';
        if (empty($line->referencia) || $line->modelClassName() === 'LineaFacturaCliente' || false === $model->editable) {
            return $html;
        }

        $product = $line->getProducto();
        if ($product->nostock || $product->ventasinstock) {
            return $html;
        }

        // buscamos el stock de este producto en este almacén
        $stock = new Stock();
        $where = [
            new DataBaseWhere('codalmacen', $model->codalmacen),
            new DataBaseWhere('referencia', $line->referencia)
        ];
        $stock->loadFromCode('', $where);

        switch ($line->actualizastock) {
            case -1:
            case -2:
                $html = $stock->disponible > 0 ?
                    '<span class="input-group-text text-success rounded-0">' . $stock->disponible . '</span>' :
                    '<span class="input-group-text text-danger rounded-0">' . $stock->disponible . '</span>';
                break;

            default:
                $html = $line->cantidad <= $stock->cantidad ?
                    '<span class="input-group-text text-success rounded-0">' . $stock->cantidad . '</span>' :
                    '<span class="input-group-text text-danger rounded-0">' . $stock->cantidad . '</span>';
                break;
        }

        if (empty($html)) {
            return $html;
        }

        return '<div class="input-group-prepend" title="' . $i18n->trans('stock') . '">' . $html . '</div>';
    }

    private static function getFastLine(SalesDocument $model, array $formData): ?SalesDocumentLine
    {
        if (empty($formData['fastli'])) {
            return $model->getNewLine();
        }

        $variantModel = new Variante();
        $whereBarcode = [new DataBaseWhere('codbarras', $formData['fastli'])];
        foreach ($variantModel->all($whereBarcode) as $variante) {
            return $model->getNewProductLine($variante->referencia);
        }

        ToolBox::i18nLog()->warning('product-not-found', ['%ref%' => $formData['fastli']]);
        return null;
    }

    private static function precio(Translator $i18n, string $idlinea, SalesDocumentLine $line, SalesDocument $model, string $jsFunc): string
    {
        if (false === $model->editable) {
            return '<div class="col-sm col-lg-1 order-4">'
                . '<span class="d-lg-none small">' . $i18n->trans('price') . '</span>'
                . '<input type="number" value="' . $line->pvpunitario . '" class="form-control form-control-sm border-0" disabled/>'
                . '</div>';
        }

        $attributes = 'name="pvpunitario_' . $idlinea . '" onkeyup="return ' . $jsFunc . '(\'recalculate-line\', \'0\');"';
        return '<div class="col-sm col-lg-1 order-4">'
            . '<span class="d-lg-none small">' . $i18n->trans('price') . '</span>'
            . '<input type="number" ' . $attributes . ' value="' . $line->pvpunitario . '" class="form-control form-control-sm border-0"/>'
            . '</div>';
    }

    private static function renderField(Translator $i18n, string $idlinea, SalesDocumentLine $line, SalesDocument $model, string $field): ?string
    {
        foreach (self::$mods as $mod) {
            $html = $mod->renderField($i18n, $idlinea, $line, $model, $field);
            if ($html !== null) {
                return $html;
            }
        }

        switch ($field) {
            case '_total':
                return self::lineTotal($i18n, $idlinea, $line, $model, 'salesLineTotalWithTaxes');

            case 'cantidad':
                return self::cantidad($i18n, $idlinea, $line, $model, 'salesFormActionWait');

            case 'codimpuesto':
                return self::codimpuesto($i18n, $idlinea, $line, $model, 'salesFormAction');

            case 'descripcion':
                return self::descripcion($i18n, $idlinea, $line, $model);

            case 'dtopor':
                return self::dtopor($i18n, $idlinea, $line, $model, 'salesFormActionWait');

            case 'dtopor2':
                return self::dtopor2($i18n, $idlinea, $line, $model, 'dtopor2', 'salesFormActionWait');

            case 'irpf':
                return self::irpf($i18n, $idlinea, $line, $model, 'salesFormAction');

            case 'pvpunitario':
                return self::precio($i18n, $idlinea, $line, $model, 'salesFormActionWait');

            case 'recargo':
                return self::recargo($i18n, $idlinea, $line, $model, 'salesFormActionWait');

            case 'referencia':
                return self::referencia($i18n, $idlinea, $line, $model);

            case 'suplido':
                return self::suplido($i18n, $idlinea, $line, $model, 'salesFormAction');
        }

        return null;
    }

    private static function renderLineModal(Translator $i18n, SalesDocumentLine $line, string $idlinea, SalesDocument $model): string
    {
        return '<div class="modal fade" id="lineModal-' . $idlinea . '" tabindex="-1" aria-labelledby="lineModal-' . $idlinea . 'Label" aria-hidden="true">'
            . '<div class="modal-dialog modal-dialog-centered">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title">'
            . $line->referencia
            . '</h5>'
            . '<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
            . '<span aria-hidden="true">&times;</span>'
            . '</button>'
            . '</div>'
            . '<div class="modal-body">'
            . '<div class="form-row">'
            . self::renderField($i18n, $idlinea, $line, $model, 'recargo')
            . self::renderField($i18n, $idlinea, $line, $model, 'irpf')
            . '</div>'
            . '<div class="form-row">'
            . self::renderField($i18n, $idlinea, $line, $model, 'suplido')
            . self::renderField($i18n, $idlinea, $line, $model, 'dtopor2')
            . '</div>'
            . '<div class="form-row">'
            . self::renderNewModalFields($i18n, $idlinea, $line, $model)
            . '</div>'
            . '</div>'
            . '<div class="modal-footer">'
            . '<button type="button" class="btn btn-secondary" data-dismiss="modal">'
            . $i18n->trans('close')
            . '</button>'
            . '<button type="button" class="btn btn-primary" data-dismiss="modal">'
            . $i18n->trans('accept')
            . '</button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    private static function renderNewFields(Translator $i18n, string $idlinea, SalesDocumentLine $line, SalesDocument $model): string
    {
        // cargamos los nuevos campos
        $newFields = [];
        foreach (self::$mods as $mod) {
            foreach ($mod->newFields() as $field) {
                if (false === in_array($field, $newFields)) {
                    $newFields[] = $field;
                }
            }
        }

        // renderizamos los campos
        $html = '';
        foreach ($newFields as $field) {
            foreach (self::$mods as $mod) {
                $fieldHtml = $mod->renderField($i18n, $idlinea, $line, $model, $field);
                if ($fieldHtml !== null) {
                    $html .= $fieldHtml;
                    break;
                }
            }
        }
        return $html;
    }

    private static function renderNewModalFields(Translator $i18n, string $idlinea, SalesDocumentLine $line, SalesDocument $model): string
    {
        // cargamos los nuevos campos
        $newFields = [];
        foreach (self::$mods as $mod) {
            foreach ($mod->newModalFields() as $field) {
                if (false === in_array($field, $newFields)) {
                    $newFields[] = $field;
                }
            }
        }

        // renderizamos los campos
        $html = '';
        foreach ($newFields as $field) {
            foreach (self::$mods as $mod) {
                $fieldHtml = $mod->renderField($i18n, $idlinea, $line, $model, $field);
                if ($fieldHtml !== null) {
                    $html .= $fieldHtml;
                    break;
                }
            }
        }
        return $html;
    }

    private static function renderNewTitles(Translator $i18n, SalesDocument $model): string
    {
        // cargamos los nuevos campos
        $newFields = [];
        foreach (self::$mods as $mod) {
            foreach ($mod->newTitles() as $field) {
                if (false === in_array($field, $newFields)) {
                    $newFields[] = $field;
                }
            }
        }

        // renderizamos los campos
        $html = '';
        foreach ($newFields as $field) {
            foreach (self::$mods as $mod) {
                $fieldHtml = $mod->renderTitle($i18n, $model, $field);
                if ($fieldHtml !== null) {
                    $html .= $fieldHtml;
                    break;
                }
            }
        }
        return $html;
    }

    private static function renderTitle(Translator $i18n, SalesDocument $model, string $field): ?string
    {
        foreach (self::$mods as $mod) {
            $html = $mod->renderTitle($i18n, $model, $field);
            if ($html !== null) {
                return $html;
            }
        }

        switch ($field) {
            case '_actionsButton':
                return self::titleActionsButton($model);

            case '_total':
                return self::titleTotal($i18n);

            case 'cantidad':
                return self::titleCantidad($i18n);

            case 'codimpuesto':
                return self::titleCodimpuesto($i18n);

            case 'descripcion':
                return self::titleDescripcion($i18n);

            case 'dtopor':
                return self::titleDtopor($i18n);

            case 'pvpunitario':
                return self::titlePrecio($i18n);

            case 'referencia':
                return self::titleReferencia($i18n);
        }

        return null;
    }

    private static function renderTitles(Translator $i18n, SalesDocument $model): string
    {
        return '<div class="container-fluid d-none d-lg-block"><div class="form-row border-bottom">'
            . self::renderTitle($i18n, $model, 'referencia')
            . self::renderTitle($i18n, $model, 'descripcion')
            . self::renderTitle($i18n, $model, 'cantidad')
            . self::renderNewTitles($i18n, $model)
            . self::renderTitle($i18n, $model, 'pvpunitario')
            . self::renderTitle($i18n, $model, 'dtopor')
            . self::renderTitle($i18n, $model, 'codimpuesto')
            . self::renderTitle($i18n, $model, '_total')
            . self::renderTitle($i18n, $model, '_actionsButton')
            . '</div></div>';
    }
}
