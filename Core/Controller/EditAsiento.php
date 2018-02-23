<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addEditView('\FacturaScripts\Dinamic\Model\Asiento', 'EditAsiento', 'accounting-entry', 'fa-balance-scale');
        $this->addGridView('EditAsiento', '\FacturaScripts\Dinamic\Model\Partida', 'EditPartida', 'accounting-items');
        $this->setTemplate('AccountingEntry');
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
                $idasiento = $this->getViewModelValue('EditAsiento', 'idasiento');
                if (!empty($idasiento)) {
                    $where = [new DataBaseWhere('idasiento', $idasiento)];
                    $orderby = ['idpartida' => 'ASC'];
                    $view->loadData($where, $orderby);
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
    protected function execPreviousAction($view, $action)
    {
        switch ($action) {
            case 'account-data':
                $this->setTemplate(false);
                $subaccount = $this->request->get('codsubcuenta', '');
                $exercise = $this->request->get('codejercicio', '');
                $result = $this->getAccountData($exercise, $subaccount);
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
        $pagedata['title'] = 'accounting-entries';
        $pagedata['menu'] = 'accounting';
        $pagedata['icon'] = 'fa-balance-scale';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Run the autocomplete action.
     * Returns a JSON string for the searched values.
     *
     * @param array $data
     * @return array
     */
    protected function autocompleteAction($data): array
    {
        $results = [];
        $codeModel = new Model\CodeModel();
        foreach ($codeModel->search($data['source'], $data['field'], $data['title'], $data['term']) as $value) {
            $results[] = $value->code;
        }
        return $results;
    }

    /**
     * Load total data from subaccount
     *
     * @param string $exercise
     * @param string $subaccount
     * @return array
     */
    private function getAccountData($exercise, $subaccount): array
    {
        $result = [
            'subaccount' => $subaccount,
            'description' => '',
            'balance' => 0.00,
            'detail' => [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00]
        ];

        if (empty($exercise) or empty($subaccount)) {
            return $result;
        }

        $where = [
            new DataBaseWhere('codsubcuenta', $subaccount),
            new DataBaseWhere('codejercicio', $exercise)
        ];

        $account = new Model\Subcuenta();
        if ($account->loadFromCode(null, $where)) {
            $result['description'] = $account->descripcion;

            $where = [new DataBaseWhere('idsubcuenta', $account->idsubcuenta)];
            $balance = new Model\SubcuentaSaldo();
            foreach ($balance->all($where) as $values) {
                $result['detail'][$values->mes] = $values->saldo;
                $result['balance'] += $values->saldo;
            }
        }

        // Aply round to imports
        $result['balance'] = round($result['balance'], (int) FS_NF0);
        foreach ($result['detail'] as $key => $value) {
            $result['detail'][$key] = round($value, (int) FS_NF0);
        }

        // Return account data
        return $result;
    }
}
