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

use FacturaScripts\Core\Translator;
use FacturaScripts\Dinamic\Model\Asiento;

/**
 * Description of AccountingFooterHTML
 *
 * @author Carlos Garcia Gomez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 *
 * @deprecated replaced by Core/lib/AjaxForms/AccountingFooterHTML
 */
class AccountingFooterHTML
{
    public static function apply(Asiento &$model, array $formData)
    {
    }

    public static function render(Asiento $model): string
    {
        $i18n = new Translator();
        return '<div class="container-fluid">'
            . '<div class="form-row align-items-center mt-3">'
            . static::newSubaccount($i18n, $model)
            . static::moveBtn($i18n, $model)
            . static::importe($i18n, $model)
            . static::descuadre($i18n, $model)
            . '</div>'
            . '<div class="form-row mt-3">'
            . static::deleteBtn($i18n, $model)
            . '<div class="col-sm"></div>'
            . static::saveBtn($i18n, $model)
            . '</div>'
            . '</div>';
    }

    protected static function deleteBtn(Translator $i18n, Asiento $model): string
    {
        if (false === $model->exists() || false === $model->editable) {
            return '';
        }

        $lockBtn = '';
        if ($model->editable) {
            $lockBtn .= '<div class="col-sm-3 col-md-2">'
                . '<button type="button" class="btn btn-block btn-warning btn-spin-action mb-3" onclick="return accEntryFormSave(\'lock-doc\', \'0\');">'
                . '<i class="fas fa-lock fa-fw"></i> ' . $i18n->trans('lock-entry') . '</button>'
                . '</div>';
        }

        return '<div class="col-sm-3 col-md-2">'
            . '<button type="button" class="btn btn-block btn-danger btn-spin-action mb-3" data-toggle="modal" data-target="#deleteDocModal">'
            . '<i class="fas fa-trash-alt fa-fw"></i> ' . $i18n->trans('delete') . '</button>'
            . '</div>'
            . $lockBtn
            . '<div class="modal fade" id="deleteDocModal" tabindex="-1" aria-hidden="true">'
            . '<div class="modal-dialog">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title"></h5>'
            . '<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
            . '<span aria-hidden="true">&times;</span>'
            . '</button>'
            . '</div>'
            . '<div class="modal-body text-center">'
            . '<i class="fas fa-trash-alt fa-3x"></i>'
            . '<h5 class="mt-3 mb-1">' . $i18n->trans('confirm-delete') . '</h5>'
            . '<p class="mb-0">' . $i18n->trans('are-you-sure') . '</p>'
            . '</div>'
            . '<div class="modal-footer">'
            . '<button type="button" class="btn btn-secondary" data-dismiss="modal">' . $i18n->trans('cancel') . '</button>'
            . '<button type="button" class="btn btn-danger btn-spin-action" onclick="return accEntryFormSave(\'delete-doc\', \'0\');">' . $i18n->trans('delete') . '</button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    /**
     * Render the unbalance value
     *
     * @param Translator $i18n
     * @param Asiento $model
     *
     * @return string
     */
    protected static function descuadre(Translator $i18n, Asiento $model): string
    {
        $unbalance = isset($model->debe, $model->haber) ? round($model->debe - $model->haber, FS_NF0) : 0.0;
        if (empty($unbalance)) {
            return '';
        }

        return '<div class="col-sm-3 col-md-2 mb-3">'
            . '<div class="input-group">'
            . '<div class="input-group-prepend"><span class="input-group-text text-danger">' . $i18n->trans('unbalance') . '</span></div>'
            . '<input type="number" value="' . $unbalance . '" class="form-control" step="any" readonly>'
            . '</div></div>';
    }

    /**
     * Render the amount field
     *
     * @param Translator $i18n
     * @param Asiento $model
     *
     * @return string
     */
    protected static function importe(Translator $i18n, Asiento $model): string
    {
        return '<div class="col-sm-3 col-md-2 mb-3">'
            . '<div class="input-group">'
            . '<div class="input-group-prepend"><span class="input-group-text">' . $i18n->trans('amount') . '</span></div>'
            . '<input type="number" value="' . $model->importe . '" class="form-control" step="any" tabindex="-1" readonly>'
            . '</div></div>';
    }

    protected static function newSubaccount(Translator $i18n, Asiento $model): string
    {
        if (false === $model->editable) {
            return '<div class="col-sm"></div>';
        }

        return '<div class="col-sm-6 col-md-2 mb-3">'
            . '<div class="input-group">'
            . '<input type="text" class="form-control" maxlength="15" autocomplete="off" placeholder="' . $i18n->trans('subaccount')
            . '" id="new_subaccount" name="new_subaccount" onchange="return newLineAction(this.value);"/>'
            . '<div class="input-group-append"><button class="btn btn-info" type="button" title="' . $i18n->trans('subaccounts') . '"'
            . ' onclick="$(\'#findSubaccountModal\').modal(); $(\'#findSubaccountInput\').focus();"><i class="fas fa-book"></i></button></div>'
            . '</div>'
            . '</div>'
            . '<div class="col-sm">'
            . '<p class="text-muted">' . $i18n->trans('account-dot-code') . '</p>'
            . '</div>';
    }

    protected static function saveBtn(Translator $i18n, Asiento $model): string
    {
        if (false === $model->editable) {
            return '<div class="col-sm-3 col-md-2">'
                . '<button type="button" class="btn btn-block btn-warning btn-spin-action mb-3" onclick="return accEntryFormSave(\'unlock-doc\', \'0\');">'
                . '<i class="fas fa-lock-open fa-fw"></i> ' . $i18n->trans('unlock-entry') . '</button>'
                . '</div>';
        }

        return '<div class="col-sm-3 col-md-2">'
            . '<button type="button" class="btn btn-block btn-primary btn-spin-action mb-3" load-after="true" onclick="return accEntryFormSave(\'save-doc\', \'0\');">'
            . '<i class="fas fa-save fa-fw"></i> ' . $i18n->trans('save') . '</button>'
            . '</div>';
    }

    protected static function moveBtn(Translator $i18n, Asiento $model): string
    {
        if (false === $model->editable) {
            return '';
        }

        return '<div class="col-sm-auto">'
            . '<button type="button" class="btn btn-light mb-3" id="sortableBtn">'
            . '<i class="fas fa-arrows-alt-v fa-fw"></i> ' . $i18n->trans('move-lines')
            . '</button>'
            . '</div>';
    }
}
