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
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\Subcuenta;

/**
 * Description of AccountingModalHTML
 *
 * @author Carlos Garcia Gomez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
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

    public static function apply(Asiento &$model, array $formData): void
    {
        self::$orden = $formData['fp_orden'] ?? 'ref_asc';
        self::$query = isset($formData['fp_query']) ? Tools::noHtml(mb_strtolower($formData['fp_query'], 'UTF8')) : '';
    }

    public static function render(Asiento $model): string
    {
        return static::modalSubaccount($model);
    }

    public static function renderSubaccountList(Asiento $model): string
    {
        $tbody = '';
        foreach (static::getSubaccounts($model) as $subaccount) {
            $cssClass = $subaccount->saldo > 0 ? 'table-success clickableRow' : 'clickableRow';
            $onclick = '$(\'#findSubaccountModal\').modal(\'hide\');'
                . ' return newLineAction(\'' . $subaccount->codsubcuenta . '\');';

            $tbody .= '<tr class="' . $cssClass . '" onclick="' . $onclick . '">'
                . '<td><b>' . $subaccount->codsubcuenta . '</b> ' . $subaccount->descripcion . '</td>'
                . '<td class="text-end">' . Tools::money($subaccount->saldo) . '</td>'
                . '</tr>';
        }

        if (empty($tbody)) {
            $tbody .= '<tr class="table-warning"><td colspan="3">' . Tools::lang()->trans('no-data') . '</td></tr>';
        }

        return '<table class="table table-hover mb-0">'
            . '<thead>'
            . '<tr>'
            . '<th>' . Tools::lang()->trans('subaccount') . '</th>'
            . '<th class="text-end">' . Tools::lang()->trans('balance') . '</th>'
            . '</tr>'
            . '</thead>'
            . '<tbody>' . $tbody . '</tbody>'
            . '</table>';
    }

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

    protected static function modalSubaccount(Asiento $model): string
    {
        return '<div class="modal" id="findSubaccountModal" tabindex="-1" aria-hidden="true">'
            . '<div class="modal-dialog modal-xl">'
            . '<div class="modal-content">'
            . '<div class="modal-header">'
            . '<h5 class="modal-title"><i class="fa-solid fa-book fa-fw"></i> ' . Tools::lang()->trans('subaccounts') . '</h5>'
            . '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">'
            . ''
            . '</button>'
            . '</div>'
            . '<div class="modal-body">'
            . '<div class="row g-3">'
            . '<div class="col-sm">'
            . '<div class="input-group">'
            . '<input type="text" name="fp_query" class="form-control" id="findSubaccountInput" placeholder="' . Tools::lang()->trans('search')
            . '" onkeyup="return findSubaccountSearch(\'find-subaccount\', \'0\', this);"/>'
            . '<div class="input-group-apend">'
            . '<button class="btn btn-primary" type="button" onclick="return accEntryFormAction(\'find-subaccount\', \'0\');"'
            . ' data-loading-text="<span class=\'spinner-border spinner-border-sm\' role=\'status\' aria-hidden=\'true\'></span>"><i class="fa-solid fa-search"></i></button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . static::orden()
            . '</div>'
            . '</div>'
            . '<div class="table-responsive" id="findSubaccountList">' . static::renderSubaccountList($model) . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    protected static function orden(): string
    {
        return '<div class="col-sm">'
            . '<div class="input-group">'
            . '<span class="input-group-text"><i class="fa-solid fa-sort-amount-down-alt"></i></span>'
            . '<select name="fp_orden" class="form-select" onchange="return accEntryFormAction(\'find-subaccount\', \'0\');">'
            . '<option value="code_asc">' . Tools::lang()->trans('code') . '</option>'
            . '<option value="desc_asc">' . Tools::lang()->trans('description') . '</option>'
            . '<option value="saldo_desc">' . Tools::lang()->trans('balance') . '</option>'
            . '</select>'
            . '</div>'
            . '</div>';
    }
}
