<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Impuestos;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Translator;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\Partida;
use FacturaScripts\Dinamic\Model\Subcuenta;

/**
 * Description of SalesLineHTML
 *
 * @author Carlos Garcia Gomez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 *
 * @deprecated replaced by Core/Lib/AjaxForms/AccountingLineHTML
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
    public static function apply(Asiento &$model, array &$lines, array $formData)
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
     *
     * @return array
     */
    public static function getDeletedLines(): array
    {
        return self::$deletedLines;
    }

    /**
     * Render the lines of the accounting entry.
     *
     * @param Partida[] $lines
     * @param Asiento $model
     *
     * @return string
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
     *
     * @param Partida $line
     * @param Asiento $model
     *
     * @return string
     */
    public static function renderLine(Partida $line, Asiento $model): string
    {
        static::$num++;
        $i18n = new Translator();
        $idlinea = $line->idpartida ?? 'n' . static::$num;
        $cssClass = static::$num % 2 == 0 ? 'bg-white border-top' : 'bg-light border-top';
        return '<div class="' . $cssClass . ' line pl-2 pr-2">'
            . '<div class="form-row align-items-end">'
            . static::subcuenta($i18n, $line, $model)
            . static::debe($i18n, $line, $model)
            . static::haber($i18n, $line, $model)
            . static::renderExpandButton($i18n, $idlinea, $model)
            . '</div>'
            . self::renderLineModal($i18n, $line, $idlinea, $model)
            . '</div>';
    }

    private static function renderLineModal(Translator $i18n, Partida $line, string $idlinea, Asiento $model): string
    {
        return '<div class="modal fade" id="lineModal-' . $idlinea . '" tabindex="-1" aria-labelledby="lineModal-' . $idlinea . 'Label" aria-hidden="true">'
            . '<div class="modal-dialog modal-dialog-centered">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title">' . $line->codsubcuenta . '</h5>'
            . '<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
            . '<span aria-hidden="true">&times;</span>'
            . '</button>'
            . '</div>'
            . '<div class="modal-body">'
            . '<div class="form-row">'
            . static::iva($i18n, $line, $model)
            . static::recargo($i18n, $line, $model)
            . '</div>'
            . '<div class="form-row">'
            . static::baseimponible($i18n, $line, $model)
            . static::cifnif($i18n, $line, $model)
            . '</div>'
            . '<div class="form-row">'
            . static::documento($i18n, $line, $model)
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

    /**
     * @param array $formData
     * @param Partida $line
     * @param string $id
     */
    protected static function applyToLine(array &$formData, Partida &$line, string $id)
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
     *
     * @param Translator $i18n
     * @param Partida $line
     * @param Asiento $model
     *
     * @return string
     */
    protected static function baseimponible(Translator $i18n, Partida $line, Asiento $model): string
    {
        $idlinea = $line->idpartida ?? 'n' . static::$num;
        $attributes = $model->editable ? 'name="baseimponible_' . $idlinea . '"' : 'disabled';
        return '<div class="col pb-2 small">' . $i18n->trans('tax-base')
            . '<input type="number" ' . $attributes . ' value="' . floatval($line->baseimponible)
            . '" class="form-control" step="any" autocomplete="off">'
            . '</div>';
    }

    /**
     * @param Asiento $model
     * @param Partida[] $lines
     */
    public static function calculateUnbalance(Asiento &$model, array $lines)
    {
        $model->debe = 0.0;
        $model->haber = 0.0;
        foreach ($lines as $line) {
            $model->debe += $line->debe;
            $model->haber += $line->haber;
        }
        $model->importe = max([$model->debe, $model->haber]);
    }

    /**
     * @param Translator $i18n
     * @param Partida $line
     * @param Asiento $model
     *
     * @return string
     */
    protected static function cifnif(Translator $i18n, Partida $line, Asiento $model): string
    {
        $idlinea = $line->idpartida ?? 'n' . static::$num;
        $attributes = $model->editable ? 'name="cifnif_' . $idlinea . '"' : 'disabled';
        return '<div class="col pb-2 small">' . $i18n->trans('cifnif')
            . '<input type="text" ' . $attributes . ' value="' . Tools::noHtml($line->cifnif)
            . '" class="form-control" maxlength="30" autocomplete="off"/>'
            . '</div>';
    }

    /**
     * @param Translator $i18n
     * @param Partida $line
     * @param Asiento $model
     * @return string
     */
    protected static function concepto(Translator $i18n, Partida $line, Asiento $model): string
    {
        $idlinea = $line->idpartida ?? 'n' . static::$num;
        $attributes = $model->editable
            ? 'name="concepto_' . $idlinea . '" onchange="return recalculateLine(\'recalculate\', \'' . $idlinea . '\');"'
            : 'disabled';

        return '<div class="col pb-2 small">' . $i18n->trans('concept')
            . '<input type="text" ' . $attributes . ' class="form-control" value="' . Tools::noHtml($line->concepto) . '">'
            . '</div>';
    }

    /**
     * @param Translator $i18n
     * @param Partida $line
     * @param Asiento $model
     *
     * @return string
     */
    protected static function contrapartida(Translator $i18n, Partida $line, Asiento $model): string
    {
        $idlinea = $line->idpartida ?? 'n' . static::$num;
        $attributes = $model->editable
            ? 'name="codcontrapartida_' . $idlinea . '" onchange="return recalculateLine(\'recalculate\', \'' . $idlinea . '\');"'
            : 'disabled';

        return '<div class="col pb-2 small">' . $i18n->trans('counterpart')
            . '<input type="text" ' . $attributes . ' value="' . $line->codcontrapartida
            . '" class="form-control" maxlength="15" autocomplete="off" placeholder="' . $i18n->trans('optional') . '"/>'
            . '</div>';
    }

    /**
     * @param Translator $i18n
     * @param Partida $line
     * @param Asiento $model
     *
     * @return string
     */
    protected static function debe(Translator $i18n, Partida $line, Asiento $model): string
    {
        $idlinea = $line->idpartida ?? 'n' . static::$num;
        $attributes = $model->editable
            ? 'name="debe_' . $idlinea . '" step="1" onchange="return recalculateLine(\'recalculate\', \'' . $idlinea . '\');"'
            : 'disabled';

        return '<div class="col pb-2 small">' . $i18n->trans('debit')
            . '<input type="number" class="form-control line-debit" ' . $attributes . ' value="' . floatval($line->debe) . '"/>'
            . '</div>';
    }

    /**
     * @param Translator $i18n
     * @param Partida $line
     * @param Asiento $model
     *
     * @return string
     */
    protected static function documento(Translator $i18n, Partida $line, Asiento $model): string
    {
        $idlinea = $line->idpartida ?? 'n' . static::$num;
        $attributes = $model->editable ? 'name="documento_' . $idlinea . '"' : 'disabled';
        return '<div class="col pb-2 small">' . $i18n->trans('document')
            . '<input type="text" ' . $attributes . ' value="' . Tools::noHtml($line->documento)
            . '" class="form-control" maxlength="30" autocomplete="off"/>'
            . '</div>';
    }

    /**
     * @param string $code
     * @param Asiento $model
     *
     * @return Subcuenta
     */
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

    /**
     * @param Translator $i18n
     * @param Partida $line
     * @param Asiento $model
     *
     * @return string
     */
    protected static function haber(Translator $i18n, Partida $line, Asiento $model): string
    {
        $idlinea = $line->idpartida ?? 'n' . static::$num;
        $attributes = $model->editable
            ? 'name="haber_' . $idlinea . '" step="1" onchange="return recalculateLine(\'recalculate\', \'' . $idlinea . '\');"'
            : 'disabled';

        return '<div class="col pb-2 small">' . $i18n->trans('credit')
            . '<input type="number" class="form-control" ' . $attributes . ' value="' . round($line->haber, FS_NF0) . '"/>'
            . '</div>';
    }

    /**
     * @param Translator $i18n
     * @param Partida $line
     * @param Asiento $model
     *
     * @return string
     */
    protected static function iva(Translator $i18n, Partida $line, Asiento $model): string
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
        return '<div class="col pb-2 small"><a href="ListImpuesto">' . $i18n->trans('vat') . '</a>'
            . '<select ' . $attributes . ' class="form-control">' . implode('', $options) . '</select>'
            . '</div>';
    }

    /**
     * @param Translator $i18n
     * @param Partida $line
     * @param Asiento $model
     *
     * @return string
     */
    protected static function recargo(Translator $i18n, Partida $line, Asiento $model): string
    {
        $idlinea = $line->idpartida ?? 'n' . static::$num;
        $attributes = $model->editable ? 'name="recargo_' . $idlinea . '"' : 'disabled';
        return '<div class="col pb-2 small">' . $i18n->trans('surcharge')
            . '<input type="number" ' . $attributes . ' value="' . floatval($line->recargo)
            . '" class="form-control" step="any" autocomplete="off">'
            . '</div>';
    }

    /**
     * @param Translator $i18n
     * @param string $idlinea
     * @param Asiento $model
     *
     * @return string
     */
    protected static function renderExpandButton(Translator $i18n, string $idlinea, Asiento $model): string
    {
        if ($model->editable) {
            return '<div class="col-sm-auto pb-1">'
                . '<button type="button" data-toggle="modal" data-target="#lineModal-' . $idlinea . '" class="btn btn-outline-secondary mb-1" title="'
                . $i18n->trans('more') . '"><i class="fas fa-ellipsis-h"></i></button>'
                . '<button class="btn btn-outline-danger btn-spin-action ml-2 mb-1" type="button" title="' . $i18n->trans('delete') . '"'
                . ' onclick="return accEntryFormAction(\'rm-line\', \'' . $idlinea . '\');">'
                . '<i class="fas fa-trash-alt"></i></button></div>';
        }

        return '<div class="col-sm-auto pb-1">'
            . '<button type="button" data-toggle="modal" data-target="#lineModal-' . $idlinea . '" class="btn btn-outline-secondary mb-1" title="'
            . $i18n->trans('more') . '"><i class="fas fa-ellipsis-h"></i></button></div>';
    }

    /**
     * @param Translator $i18n
     * @param Subcuenta $subcuenta
     *
     * @return string
     */
    protected static function saldo(Translator $i18n, Subcuenta $subcuenta): string
    {
        return '<div class="col pb-2 small">' . $i18n->trans('balance')
            . '<input type="text" class="form-control" value="' . Tools::number($subcuenta->saldo) . '" tabindex="-1" readonly>'
            . '</div>';
    }

    /**
     * @param Translator $i18n
     * @param Partida $line
     * @param Asiento $model
     *
     * @return string
     */
    protected static function subcuenta(Translator $i18n, Partida $line, Asiento $model): string
    {
        $idlinea = $line->idpartida ?? 'n' . static::$num;
        $subcuenta = static::getSubcuenta($line->codsubcuenta, $model);
        if (false === $model->editable) {
            return '<div class="col pb-2 small">' . $subcuenta->descripcion
                . '<div class="input-group">'
                . '<input type="text" value="' . $line->codsubcuenta . '" class="form-control" tabindex="-1" readonly>'
                . '<div class="input-group-append"><a href="' . $subcuenta->url() . '" target="_blank" class="btn btn-outline-primary">'
                . '<i class="far fa-eye"></i></a></div>'
                . '</div>'
                . '</div>'
                . static::contrapartida($i18n, $line, $model)
                . static::concepto($i18n, $line, $model);
        }

        return '<div class="col pb-2 small">'
            . '<input type="hidden" name="orden_' . $idlinea . '" value="' . $line->orden . '"/>' . $subcuenta->descripcion
            . '<div class="input-group">'
            . '<input type="text" name="codsubcuenta_' . $idlinea . '" value="' . $line->codsubcuenta . '" class="form-control" tabindex="-1" readonly>'
            . '<div class="input-group-append"><a href="' . $subcuenta->url() . '" target="_blank" class="btn btn-outline-primary">'
            . '<i class="far fa-eye"></i></a></div>'
            . '</div>'
            . '</div>'
            . static::contrapartida($i18n, $line, $model)
            . static::concepto($i18n, $line, $model);
    }
}
