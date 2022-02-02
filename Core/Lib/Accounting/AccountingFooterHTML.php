<?php
/**
 * Copyright (C) 2021 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Core\Lib\Accounting;

use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Dinamic\Model\Asiento;

/**
 * Description of AccountingFooterHTML
 *
 * @author Carlos Garcia Gomez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class AccountingFooterHTML
{

    /**
     * @param Asiento $model
     * @param array $formData
     */
    public static function apply(&$model, array $formData)
    {
    }

    /**
     * @param Asiento $model
     *
     * @return string
     */
    public static function render($model): string
    {
        $i18n = new Translator();
        return '<div class="container-fluid"><div class="form-row mt-3">'
            . static::newSubaccount($i18n, $model)
            . static::subaccountBtn($i18n, $model)
            . '<div class="col-sm-6 col-md"></div>'
            . static::deleteBtn($i18n, $model)
            . '<div class="col-sm-3 col-md-2">'
            . static::saveBtn($i18n, $model)
            . static::importe($i18n, $model)
            . static::descuadre($i18n, $model)
            . '</div>'
            . '</div></div>';
    }

    /**
     * @param Translator $i18n
     * @param Asiento $model
     *
     * @return string
     */
    protected static function deleteBtn(Translator $i18n, $model): string
    {
        if (false === $model->exists() || false === $model->editable) {
            return '';
        }

        $lockBtn = '';
        if ($model->editable) {
            $lockBtn .= '<div class="col-sm-3 col-md-2">'
                . '<button type="button" class="btn btn-block btn-warning mb-3" onclick="return accEntryFormSave(\'lock-doc\', \'0\');">'
                . '<i class="fas fa-lock fa-fw"></i> ' . $i18n->trans('lock-entry') . '</button>'
                . '</div>';
        }

        return '<div class="col-sm-3 col-md-2">'
            . '<button type="button" class="btn btn-block btn-danger mb-3" data-toggle="modal" data-target="#deleteDocModal">'
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
            . '<button type="button" class="btn btn-danger" onclick="return accEntryFormSave(\'delete-doc\', \'0\');">' . $i18n->trans('delete') . '</button>'
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
    protected static function descuadre(Translator $i18n, $model): string
    {
        $unbalance = isset($model->debe, $model->haber) ? $model->debe - $model->haber : 0.00;
        if (empty($unbalance)) {
            return '';
        }

        return '<div class="form-group text-danger">' . $i18n->trans('unbalance')
            . '<input type="number" value="' . $unbalance . '" class="form-control" step="any" readonly>'
            . '</div>';
    }

    /**
     * Render the amount field
     *
     * @param Translator $i18n
     * @param Asiento $model
     *
     * @return string
     */
    protected static function importe(Translator $i18n, $model): string
    {
        return false === $model->editable || empty($model->importe) ? '' : '<div class="form-group">' . $i18n->trans('amount')
            . '<input type="number" value="' . $model->importe . '" class="form-control" step="any" tabindex="-1" readonly>'
            . '</div>';
    }

    /**
     * @param Translator $i18n
     * @param Asiento $model
     *
     * @return string
     */
    protected static function newSubaccount(Translator $i18n, $model): string
    {
        if (false == $model->editable) {
            return '';
        }

        return '<div class="col-sm-3 col-md-2 mb-3">'
            . '<input type="text" class="form-control" maxlength="15" autocomplete="off" placeholder="' . $i18n->trans('subaccount')
            . '" id="new_subaccount" name="new_subaccount" onchange="return newLineAction(this.value);"/>'
            . '</div>';
    }

    /**
     * @param Translator $i18n
     * @param Asiento $model
     *
     * @return string
     */
    protected static function saveBtn(Translator $i18n, $model): string
    {
        if (false == $model->editable) {
            return '<button type="button" class="btn btn-block btn-warning mb-3" onclick="return accEntryFormSave(\'unlock-doc\', \'0\', this);">'
                . '<i class="fas fa-lock-open fa-fw"></i> ' . $i18n->trans('unlock-entry') . '</button>';
        }

        return '<button type="button" class="btn btn-block btn-primary mb-3" onclick="return accEntryFormSave(\'save-doc\', \'0\', this);">'
            . '<i class="fas fa-save fa-fw"></i> ' . $i18n->trans('save') . '</button>';
    }

    /**
     * @param Translator $i18n
     * @param Asiento $model
     *
     * @return string
     */
    protected static function subaccountBtn(Translator $i18n, $model): string
    {
        if (false == $model->editable) {
            return '';
        }

        return '<div class="col-sm-3 col-md-2">'
            . '<a href="#" class="btn btn-block btn-info mb-3" onclick="$(\'#findSubaccountModal\').modal(); $(\'#findSubaccountInput\').focus(); return false;">'
            . '<i class="fas fa-search fa-fw"></i> ' . $i18n->trans('subaccounts') . '</a>'
            . '</div>';
    }
}
