<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Asiento;

/**
 * Description of AccountingFooterHTML
 *
 * @author Carlos Garcia Gomez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class AccountingFooterHTML
{
    public static function apply(Asiento &$model, array $formData): void
    {
    }

    public static function render(Asiento $model): string
    {
        return '<div class="container-fluid">'
            . '<div class="row g-2 align-items-center mt-3">'
            . static::newSubaccount($model)
            . static::moveBtn($model)
            . static::importe($model)
            . static::descuadre($model)
            . '</div>'
            . '<div class="row g-2 mt-3">'
            . static::deleteBtn($model)
            . '<div class="col-sm"></div>'
            . static::saveBtn($model)
            . '</div>'
            . '</div>';
    }

    protected static function deleteBtn(Asiento $model): string
    {
        if (false === $model->exists() || false === $model->editable) {
            return '';
        }

        $lockBtn = '';
        if ($model->editable) {
            $lockBtn .= '<div class="col-sm-auto">'
                . '<button type="button" class="btn w-100 btn-warning btn-spin-action mb-2" onclick="return accEntryFormSave(\'lock-doc\', \'0\');">'
                . '<i class="fa-solid fa-lock fa-fw"></i> ' . Tools::trans('lock-entry') . '</button>'
                . '</div>';
        }

        return '<div class="col-sm-auto">'
            . '<button type="button" class="btn w-100 btn-danger btn-spin-action mb-2" data-bs-toggle="modal" data-bs-target="#deleteDocModal">'
            . '<i class="fa-solid fa-trash-alt fa-fw"></i> ' . Tools::trans('delete') . '</button>'
            . '</div>'
            . $lockBtn
            . '<div class="modal fade" id="deleteDocModal" tabindex="-1" aria-hidden="true">'
            . '<div class="modal-dialog">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title"></h5>'
            . '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">'
            . ''
            . '</button>'
            . '</div>'
            . '<div class="modal-body text-center">'
            . '<i class="fa-solid fa-trash-alt fa-3x"></i>'
            . '<h5 class="mt-3 mb-1">' . Tools::trans('confirm-delete') . '</h5>'
            . '<p class="mb-0">' . Tools::trans('are-you-sure') . '</p>'
            . '</div>'
            . '<div class="modal-footer">'
            . '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' . Tools::trans('cancel') . '</button>'
            . '<button type="button" class="btn btn-danger btn-spin-action" onclick="return accEntryFormSave(\'delete-doc\', \'0\');">' . Tools::trans('delete') . '</button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    /**
     * Render the unbalance value
     */
    protected static function descuadre(Asiento $model): string
    {
        $nf0 = Tools::settings('default', 'decimals', 2);
        $unbalance = isset($model->debe, $model->haber) ? round($model->debe - $model->haber, $nf0) : 0.0;
        if (empty($unbalance)) {
            return '';
        }

        return '<div class="col-sm-6 col-md-4 col-lg-2 mb-2">'
            . '<div class="input-group">'
            . '<span class="input-group-text text-danger">' . Tools::trans('unbalance') . '</span>'
            . '<input type="number" value="' . $unbalance . '" class="form-control" step="any" readonly>'
            . '</div></div>';
    }

    /**
     * Render the amount field
     */
    protected static function importe(Asiento $model): string
    {
        return '<div class="col-sm-6 col-md-4 col-lg-2 mb-2">'
            . '<div class="input-group">'
            . '<span class="input-group-text">' . Tools::trans('amount') . '</span>'
            . '<input type="number" value="' . $model->importe . '" class="form-control" step="any" tabindex="-1" readonly>'
            . '</div></div>';
    }

    protected static function newSubaccount(Asiento $model): string
    {
        if (false === $model->editable) {
            return '<div class="col-sm"></div>';
        }

        return '<div class="col-sm-12 col-md-6 col-lg-3 col-xl-2 mb-2">'
            . '<div class="input-group">'
            . '<input type="text" class="form-control" maxlength="15" autocomplete="off" placeholder="' . Tools::trans('subaccount')
            . '" id="new_subaccount" name="new_subaccount" onchange="return newLineAction(this.value);"/>'
            . '<button class="btn btn-info" type="button" title="' . Tools::trans('subaccounts') . '"'
            . ' onclick="$(\'#findSubaccountModal\').modal(\'show\'); $(\'#findSubaccountInput\').focus();"><i class="fa-solid fa-book"></i></button>'
            . '</div>'
            . '</div>'
            . '<div class="col-sm-12 col-md-6 col-lg">'
            . '<p class="text-muted">' . Tools::trans('account-dot-code') . '</p>'
            . '</div>';
    }

    protected static function saveBtn(Asiento $model): string
    {
        if (false === $model->editable) {
            return '<div class="col-sm-auto">'
                . '<button type="button" class="btn w-100 btn-warning btn-spin-action mb-2" onclick="return accEntryFormSave(\'unlock-doc\', \'0\');">'
                . '<i class="fa-solid fa-lock-open fa-fw"></i> ' . Tools::trans('unlock-entry') . '</button>'
                . '</div>';
        }

        return '<div class="col-sm-auto">'
            . '<button type="button" class="btn w-100 btn-primary btn-spin-action mb-2" load-after="true" onclick="return accEntryFormSave(\'save-doc\', \'0\');">'
            . '<i class="fa-solid fa-save fa-fw"></i> ' . Tools::trans('save') . '</button>'
            . '</div>';
    }

    protected static function moveBtn(Asiento $model): string
    {
        if (false === $model->editable) {
            return '';
        }

        return '<div class="col-sm-auto">'
            . '<button type="button" class="btn btn-light mb-2" id="sortableBtn">'
            . '<i class="fa-solid fa-arrows-alt-v fa-fw"></i> ' . Tools::trans('move-lines')
            . '</button>'
            . '</div>';
    }
}
