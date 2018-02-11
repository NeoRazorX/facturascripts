<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController;
use FacturaScripts\Core\Model;

/**
 * Controller to edit a single item from the Asiento model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 * @author Fco Antonio Moreno Pérez <famphuelva@gmail.com>
 * @author PC REDNET S.L. <luismi@pcrednet.com>
 */
class EditAsiento extends ExtendedController\PanelController
{
    public function __construct(&$cache, &$i18n, $className)
    {
        parent::__construct($cache, $i18n, $className);
        $this->setTemplate('AccountingEntry');
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addEditView('\FacturaScripts\Dinamic\Model\Asiento', 'EditAsiento', 'accounting-entry', 'fa-balance-scale');
        $this->addGridView('\FacturaScripts\Dinamic\Model\Partida', 'EditPartida', 'accounting-items');
    }

    /**
     * Load data view procedure
     *
     * @param string                      $keyView
     * @param ExtendedController\BaseView $view
     */
    protected function loadData($keyView, $view)
    {
        switch ($keyView) {
            case 'EditAsiento':
                $code = $this->request->get('code');
                $view->loadData($code);
                break;

            case 'EditPartida':
            case 'ListPartida':
                $idasiento = $this->getViewModelValue('EditAsiento', 'idasiento');
                if (!empty($idasiento)) {
                    $where = [new DataBaseWhere('idasiento', $idasiento)];
                    $orderby = ['idpartida' => 'ASC'];
                    $view->loadData(false, $where, $orderby);
                }
                break;
        }
    }

    /**
     * Run the actions that alter data before reading it
     *
     * @param BaseView $view
     * @param string   $action
     *
     * @return bool
     */
    protected function execPreviousAction($view, $action): bool
    {
        switch ($action) {
            case 'account-data':
                $this->setTemplate(false);
                $code = $this->request->get('code', '');
                $result = $this->getAccountData($code);
                $this->response->setContent(json_encode($result, JSON_FORCE_OBJECT));
                return false;

            case 'clone':
                return true; // TODO

            case 'lock':
                return true; // TODO

            default:
                return parent::execPreviousAction($view, $action);
        }
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'accounting-entry';
        $pagedata['menu'] = 'accounting';
        $pagedata['icon'] = 'fa-balance-scale';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    private function getAccountData($code): array
    {
        $result = [
            'description' => '',
            'balance' => 0.00,
            'detail' => []
        ];

        if (!empty($code)) {
            $accountingEntry = new Model\Partida();
            if ($accountingEntry->loadFromCode($code)) {
                $account = new Model\Subcuenta();
                $account->loadFromCode($accountingEntry->idsubcuenta);
                $result['description'] = $account->descripcion;

                $balance = new Model\SubcuentaSaldo();
                if ($balance->loadFromCode($accountingEntry->idsubcuenta)) {
                    $result['balance'] = $balance->saldo;

                    for ($i = 1; $i < 13; ++$i) {
                        $field = 'saldo_' . strval($i);
                        $result['detail'][] = $balance->{$field};
                    }
                }
            }
        }
        return $result;
    }
}
