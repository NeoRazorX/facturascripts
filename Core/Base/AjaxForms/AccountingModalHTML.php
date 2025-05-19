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
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Translator;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\Subcuenta;

/**
 * Description of AccountingModalHTML
 *
 * @author Carlos Garcia Gomez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 *
 * @deprecated replaced by Core/Lib/AjaxForms/AccountingModalHTML
 */
class AccountingModalHTML
{
    /**
     * @var string
     */
    protected static $orden;

    /**
     * @var string
     */
    protected static $query;

    public static function apply(Asiento &$model, array $formData)
    {
        self::$orden = $formData['fp_orden'] ?? 'ref_asc';
        self::$query = isset($formData['fp_query']) ? Tools::noHtml(mb_strtolower($formData['fp_query'], 'UTF8')) : '';
    }

    public static function render(Asiento $model): string
    {
        $i18n = new Translator();
        return static::modalSubaccount($i18n, $model);
    }

    public static function renderSubaccountList(Asiento $model): string
    {
        $tbody = '';
        $i18n = new Translator();
        foreach (static::getSubaccounts($model) as $subaccount) {
            $cssClass = $subaccount->saldo > 0 ? 'table-success clickableRow' : 'clickableRow';
            $onclick = '$(\'#findSubaccountModal\').modal(\'hide\');'
                . ' return newLineAction(\'' . $subaccount->codsubcuenta . '\');';

            $tbody .= '<tr class="' . $cssClass . '" onclick="' . $onclick . '">'
                . '<td><b>' . $subaccount->codsubcuenta . '</b> ' . $subaccount->descripcion . '</td>'
                . '<td class="text-right">' . Tools::money($subaccount->saldo) . '</td>'
                . '</tr>';
        }

        if (empty($tbody)) {
            $tbody .= '<tr class="table-warning"><td colspan="3">' . $i18n->trans('no-data') . '</td></tr>';
        }

        return '<table class="table table-hover mb-0">'
            . '<thead>'
            . '<tr>'
            . '<th>' . $i18n->trans('subaccount') . '</th>'
            . '<th class="text-right">' . $i18n->trans('balance') . '</th>'
            . '</tr>'
            . '</thead>'
            . '<tbody>' . $tbody . '</tbody>'
            . '</table>';
    }

    /**
     * @param Asiento $model
     *
     * @return Subcuenta[]
     */
    protected static function getSubaccounts(Asiento $model): array
    {
        $subaccount = new Subcuenta();
        if (empty($model->codejercicio)) {
            $model->setDate($model->fecha);
        }
        $where = [new DataBaseWhere('codejercicio', $model->codejercicio)];
        if (self::$query) {
            $where[] = new DataBaseWhere('descripcion|codsubcuenta', self::$query, 'XLIKE');
        }

        switch (self::$orden) {
            case 'desc_asc':
                $order = ['descripcion' => 'ASC'];
                break;

            case 'saldo_desc':
                $order = ['saldo' => 'DESC'];
                break;

            default:
                $order = ['codsubcuenta' => 'ASC'];
                break;
        }

        return $subaccount->all($where, $order);
    }

    protected static function modalSubaccount(Translator $i18n, Asiento $model): string
    {
        return '<div class="modal" id="findSubaccountModal" tabindex="-1" aria-hidden="true">'
            . '<div class="modal-dialog modal-xl">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title"><i class="fas fa-book fa-fw"></i> ' . $i18n->trans('subaccounts') . '</h5>'
            . '<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
            . '<span aria-hidden="true">&times;</span>'
            . '</button>'
            . '</div>'
            . '<div class="modal-body">'
            . '<div class="form-row">'
            . '<div class="col-sm">'
            . '<div class="input-group">'
            . '<input type="text" name="fp_query" class="form-control" id="findSubaccountInput" placeholder="' . $i18n->trans('search')
            . '" onkeyup="return findSubaccountSearch(\'find-subaccount\', \'0\', this);"/>'
            . '<div class="input-group-apend">'
            . '<button class="btn btn-primary" type="button" onclick="return accEntryFormAction(\'find-subaccount\', \'0\');"'
            . ' data-loading-text="<span class=\'spinner-border spinner-border-sm\' role=\'status\' aria-hidden=\'true\'></span>"><i class="fas fa-search"></i></button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . static::orden($i18n)
            . '</div>'
            . '</div>'
            . '<div class="table-responsive" id="findSubaccountList">' . static::renderSubaccountList($model) . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    protected static function orden(Translator $i18n): string
    {
        return '<div class="col-sm">'
            . '<div class="input-group">'
            . '<div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-sort-amount-down-alt"></i></span></div>'
            . '<select name="fp_orden" class="form-control" onchange="return accEntryFormAction(\'find-subaccount\', \'0\');">'
            . '<option value="code_asc">' . $i18n->trans('code') . '</option>'
            . '<option value="desc_asc">' . $i18n->trans('description') . '</option>'
            . '<option value="saldo_desc">' . $i18n->trans('balance') . '</option>'
            . '</select>'
            . '</div>'
            . '</div>';
    }
}
