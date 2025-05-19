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
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\Partida;
use FacturaScripts\Dinamic\Model\Subcuenta;

/**
 * Description of SalesLineHTML
 *
 * @author Carlos Garcia Gomez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class AccountingLineHTML
{
    /** @var array */
    protected static $deletedLines = [];

    /** @var int */
    protected static $num = 0;

    /**
     * @param Asiento $model
     * @param Partida[] $lines
     * @param array $formData
     */
    public static function apply(Asiento &$model, array &$lines, array $formData): void
    {
        // update or remove lines
        $rmLineId = $formData['action'] === 'rm-line' ? $formData['selectedLine'] : 0;
        foreach ($lines as $key => $value) {
            if ($value->idpartida === (int)$rmLineId || false === isset($formData['codsubcuenta_' . $value->idpartida])) {
                self::$deletedLines[] = $value->idpartida;
                unset($lines[$key]);
                continue;
            }

            self::applyToLine($formData, $lines[$key], (string)$value->idpartida);
        }

        // new lines
        for ($num = 1; $num < 1000; $num++) {
            if (isset($formData['codsubcuenta_n' . $num]) && $rmLineId !== 'n' . $num) {
                $newLine = $model->getNewLine();
                $idNewLine = 'n' . $num;
                self::applyToLine($formData, $newLine, $idNewLine);
                $lines[] = $newLine;
            }
        }

        // Calculate model debit and credit
        static::calculateUnbalance($model, $lines);

        // add new line
        if ($formData['action'] === 'new-line' && !empty($formData['new_subaccount'])) {
            $subcuenta = static::getSubcuenta($formData['new_subaccount'], $model);
            if (false === $subcuenta->exists()) {
                Tools::log()->error('subaccount-not-found', ['%subAccountCode%' => $formData['new_subaccount']]);
                return;
            }

            $newLine = $model->getNewLine();
            $newLine->setAccount($subcuenta);
            $newLine->debe = ($model->debe < $model->haber) ? round($model->haber - $model->debe, FS_NF0) : 0.00;
            $newLine->haber = ($model->debe > $model->haber) ? round($model->debe - $model->haber, FS_NF0) : 0.00;
            $lines[] = $newLine;

            static::calculateUnbalance($model, $lines);
        }
    }

    /**
     * Returns the list of deleted lines.
     */
    public static function getDeletedLines(): array
    {
        return self::$deletedLines;
    }

    /**
     * Render the lines of the accounting entry.
     */
    public static function render(array $lines, Asiento $model): string
    {
        $html = '';
        foreach ($lines as $line) {
            $html .= static::renderLine($line, $model);
        }

        return empty($html) ?
            '<div class="alert alert-warning border-top mb-0">' . Tools::lang()->trans('new-acc-entry-line-p') . '</div>' :
            $html;
    }

    /**
     * Render one of the lines of the accounting entry
     */
    public static function renderLine(Partida $line, Asiento $model): string
    {
        static::$num++;
        $idlinea = $line->idpartida ?? 'n' . static::$num;
        $cssClass = static::$num % 2 == 0 ? 'bg-white border-top' : 'bg-light border-top';
        return '<div class="' . $cssClass . ' line ps-2 pe-2">'
            . '<div class="row g-3 align-items-end">'
            . static::subcuenta($line, $model)
            . static::debe($line, $model)
            . static::haber($line, $model)
            . static::renderExpandButton($idlinea, $model)
            . '</div>'
            . self::renderLineModal($line, $idlinea, $model)
            . '</div>';
    }

    private static function renderLineModal(Partida $line, string $idlinea, Asiento $model): string
    {
        return '<div class="modal fade" id="lineModal-' . $idlinea . '" tabindex="-1" aria-labelledby="lineModal-' . $idlinea . 'Label" aria-hidden="true">'
            . '<div class="modal-dialog modal-dialog-centered">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title">' . $line->codsubcuenta . '</h5>'
            . '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">'
            . ''
            . '</button>'
            . '</div>'
            . '<div class="modal-body">'
            . '<div class="row g-3">'
            . static::iva($line, $model)
            . static::recargo( $line, $model)
            . '</div>'
            . '<div class="row g-3">'
            . static::baseimponible($line, $model)
            . static::cifnif($line, $model)
            . '</div>'
            . '<div class="row g-3">'
            . static::documento($line, $model)
            . '</div>'
            . '</div>'
            . '<div class="modal-footer">'
            . '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">'
            . Tools::lang()->trans('close')
            . '</button>'
            . '<button type="button" class="btn btn-primary" data-bs-dismiss="modal">'
            . Tools::lang()->trans('accept')
            . '</button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    protected static function applyToLine(array &$formData, Partida &$line, string $id): void
    {
        $line->baseimponible = (float)($formData['baseimponible_' . $id] ?? '0');
        $line->cifnif = $formData['cifnif_' . $id] ?? '';
        $line->concepto = $formData['concepto_' . $id] ?? '';
        $line->codcontrapartida = $formData['codcontrapartida_' . $id] ?? '';
        $line->codsubcuenta = $formData['codsubcuenta_' . $id] ?? '';
        $line->debe = (float)($formData['debe_' . $id] ?? '0');
        $line->documento = $formData['documento_' . $id] ?? '';
        $line->haber = (float)($formData['haber_' . $id] ?? '0');

        // el iva puede llegar vacío y entonces asignamos null, o puede llegar un valor numérico y lo pasamos a float
        $line->iva = $formData['iva_' . $id] === '' ? null : (float)$formData['iva_' . $id];

        $line->orden = (int)($formData['orden_' . $id] ?? '0');
        $line->recargo = (float)($formData['recargo_' . $id] ?? '0');
    }

    /**
     * Amount base for apply tax.
     */
    protected static function baseimponible(Partida $line, Asiento $model): string
    {
        $idlinea = $line->idpartida ?? 'n' . static::$num;
        $attributes = $model->editable ? 'name="baseimponible_' . $idlinea . '"' : 'disabled';
        return '<div class="col pb-2 small">' . Tools::lang()->trans('tax-base')
            . '<input type="number" ' . $attributes . ' value="' . floatval($line->baseimponible)
            . '" class="form-control" step="any" autocomplete="off">'
            . '</div>';
    }

    public static function calculateUnbalance(Asiento &$model, array $lines): void
    {
        $model->debe = 0.0;
        $model->haber = 0.0;
        foreach ($lines as $line) {
            $model->debe += $line->debe;
            $model->haber += $line->haber;
        }
        $model->importe = max([$model->debe, $model->haber]);
    }

    protected static function cifnif(Partida $line, Asiento $model): string
    {
        $idlinea = $line->idpartida ?? 'n' . static::$num;
        $attributes = $model->editable ? 'name="cifnif_' . $idlinea . '"' : 'disabled';
        return '<div class="col pb-2 small">' . Tools::lang()->trans('cifnif')
            . '<input type="text" ' . $attributes . ' value="' . Tools::noHtml($line->cifnif)
            . '" class="form-control" maxlength="30" autocomplete="off"/>'
            . '</div>';
    }

    protected static function concepto(Partida $line, Asiento $model): string
    {
        $idlinea = $line->idpartida ?? 'n' . static::$num;
        $attributes = $model->editable
            ? 'name="concepto_' . $idlinea . '" onchange="return recalculateLine(\'recalculate\', \'' . $idlinea . '\');"'
            : 'disabled';

        return '<div class="col pb-2 small">' . Tools::lang()->trans('concept')
            . '<input type="text" ' . $attributes . ' class="form-control" value="' . Tools::noHtml($line->concepto) . '">'
            . '</div>';
    }

    protected static function contrapartida(Partida $line, Asiento $model): string
    {
        $idlinea = $line->idpartida ?? 'n' . static::$num;
        $attributes = $model->editable
            ? 'name="codcontrapartida_' . $idlinea . '" onchange="return recalculateLine(\'recalculate\', \'' . $idlinea . '\');"'
            : 'disabled';

        return '<div class="col pb-2 small">' . Tools::lang()->trans('counterpart')
            . '<input type="text" ' . $attributes . ' value="' . $line->codcontrapartida
            . '" class="form-control" maxlength="15" autocomplete="off" placeholder="' . Tools::lang()->trans('optional') . '"/>'
            . '</div>';
    }

    protected static function debe(Partida $line, Asiento $model): string
    {
        $idlinea = $line->idpartida ?? 'n' . static::$num;
        $attributes = $model->editable
            ? 'name="debe_' . $idlinea . '" step="1" onchange="return recalculateLine(\'recalculate\', \'' . $idlinea . '\');"'
            : 'disabled';

        return '<div class="col pb-2 small">' . Tools::lang()->trans('debit')
            . '<input type="number" class="form-control line-debit" ' . $attributes . ' value="' . floatval($line->debe) . '"/>'
            . '</div>';
    }

    protected static function documento(Partida $line, Asiento $model): string
    {
        $idlinea = $line->idpartida ?? 'n' . static::$num;
        $attributes = $model->editable ? 'name="documento_' . $idlinea . '"' : 'disabled';
        return '<div class="col pb-2 small">' . Tools::lang()->trans('document')
            . '<input type="text" ' . $attributes . ' value="' . Tools::noHtml($line->documento)
            . '" class="form-control" maxlength="30" autocomplete="off"/>'
            . '</div>';
    }

    protected static function getSubcuenta(string $code, Asiento $model): Subcuenta
    {
        $subcuenta = new Subcuenta();
        if (empty($code) || empty($model->codejercicio)) {
            return $subcuenta;
        }

        $where = [
            new DataBaseWhere('codejercicio', $model->codejercicio),
            new DataBaseWhere('codsubcuenta', $subcuenta->transformCodsubcuenta($code, $model->codejercicio))
        ];
        $subcuenta->loadFromCode('', $where);
        return $subcuenta;
    }

    protected static function haber(Partida $line, Asiento $model): string
    {
        $idlinea = $line->idpartida ?? 'n' . static::$num;
        $attributes = $model->editable
            ? 'name="haber_' . $idlinea . '" step="1" onchange="return recalculateLine(\'recalculate\', \'' . $idlinea . '\');"'
            : 'disabled';

        return '<div class="col pb-2 small">' . Tools::lang()->trans('credit')
            . '<input type="number" class="form-control" ' . $attributes . ' value="' . round($line->haber, FS_NF0) . '"/>'
            . '</div>';
    }

    protected static function iva(Partida $line, Asiento $model): string
    {
        // preseleccionamos el impuesto que corresponda
        $codimpuesto = null;
        foreach (Impuestos::all() as $imp) {
            if ($imp->codsubcuentarep || $imp->codsubcuentasop) {
                if (in_array($line->codsubcuenta, [$imp->codsubcuentarep, $imp->codsubcuentasop])) {
                    $codimpuesto = $imp->codimpuesto;
                    break;
                }
            }

            if ($imp->iva === $line->iva) {
                $codimpuesto = $imp->codimpuesto;
            }
        }

        $options = ['<option value="">------</option>'];
        foreach (Impuestos::all() as $imp) {
            $selected = $imp->codimpuesto === $codimpuesto ? ' selected' : '';
            $options[] = '<option value="' . $imp->iva . '"' . $selected . '>' . $imp->descripcion . '</option>';
        }

        $idlinea = $line->idpartida ?? 'n' . static::$num;
        $attributes = $model->editable ? 'name="iva_' . $idlinea . '"' : 'disabled';
        return '<div class="col pb-2 small"><a href="ListImpuesto">' . Tools::lang()->trans('vat') . '</a>'
            . '<select ' . $attributes . ' class="form-select">' . implode('', $options) . '</select>'
            . '</div>';
    }

    protected static function recargo(Partida $line, Asiento $model): string
    {
        $idlinea = $line->idpartida ?? 'n' . static::$num;
        $attributes = $model->editable ? 'name="recargo_' . $idlinea . '"' : 'disabled';
        return '<div class="col pb-2 small">' . Tools::lang()->trans('surcharge')
            . '<input type="number" ' . $attributes . ' value="' . floatval($line->recargo)
            . '" class="form-control" step="any" autocomplete="off">'
            . '</div>';
    }

    protected static function renderExpandButton(string $idlinea, Asiento $model): string
    {
        if ($model->editable) {
            return '<div class="col-sm-auto pb-1">'
                . '<button type="button" data-bs-toggle="modal" data-bs-target="#lineModal-' . $idlinea . '" class="btn btn-outline-secondary mb-1" title="'
                . Tools::lang()->trans('more') . '"><i class="fa-solid fa-ellipsis-h"></i></button>'
                . '<button class="btn btn-outline-danger btn-spin-action ms-2 mb-1" type="button" title="' . Tools::lang()->trans('delete') . '"'
                . ' onclick="return accEntryFormAction(\'rm-line\', \'' . $idlinea . '\');">'
                . '<i class="fa-solid fa-trash-alt"></i></button></div>';
        }

        return '<div class="col-sm-auto pb-1">'
            . '<button type="button" data-bs-toggle="modal" data-bs-target="#lineModal-' . $idlinea . '" class="btn btn-outline-secondary mb-1" title="'
            . Tools::lang()->trans('more') . '"><i class="fa-solid fa-ellipsis-h"></i></button></div>';
    }

    protected static function saldo(Subcuenta $subcuenta): string
    {
        return '<div class="col pb-2 small">' . Tools::lang()->trans('balance')
            . '<input type="text" class="form-control" value="' . Tools::number($subcuenta->saldo) . '" tabindex="-1" readonly>'
            . '</div>';
    }

    protected static function subcuenta(Partida $line, Asiento $model): string
    {
        $idlinea = $line->idpartida ?? 'n' . static::$num;
        $subcuenta = static::getSubcuenta($line->codsubcuenta, $model);
        if (false === $model->editable) {
            return '<div class="col pb-2 small">' . $subcuenta->descripcion
                . '<div class="input-group">'
                . '<input type="text" value="' . $line->codsubcuenta . '" class="form-control" tabindex="-1" readonly>'
                . '<a href="' . $subcuenta->url() . '" target="_blank" class="btn btn-outline-primary">'
                . '<i class="far fa-eye"></i></a>'
                . '</div>'
                . '</div>'
                . static::contrapartida($line, $model)
                . static::concepto($line, $model);
        }

        return '<div class="col pb-2 small">'
            . '<input type="hidden" name="orden_' . $idlinea . '" value="' . $line->orden . '"/>' . $subcuenta->descripcion
            . '<div class="input-group">'
            . '<input type="text" name="codsubcuenta_' . $idlinea . '" value="' . $line->codsubcuenta . '" class="form-control" tabindex="-1" readonly>'
            . '<a href="' . $subcuenta->url() . '" target="_blank" class="btn btn-outline-primary">'
            . '<i class="far fa-eye"></i></a>'
            . '</div>'
            . '</div>'
            . static::contrapartida($line, $model)
            . static::concepto($line, $model);
    }
}
