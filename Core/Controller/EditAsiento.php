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
        $this->addEditView('Asiento', 'EditAsiento', 'accounting-entry', 'fa-balance-scale');
        $this->addGridView('EditAsiento', 'Partida', 'EditPartida', 'accounting-items');
        $this->setTemplate('EditAsiento');
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
                $source = $this->request->get('source', '');
                $result = $this->getAccountData($exercise, $subaccount, $source);
                $this->response->setContent(json_encode($result, JSON_FORCE_OBJECT));
                return false;

            case 'delete-document':
                return true; // TODO: Uncomplete

            case 'clone':
                return true; // TODO: Uncomplete

            case 'lock':
                return true; // TODO: Uncomplete

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
     * Returns VAT data for an id VAT
     *
     * @param string $idVAT
     * @return array
     */
    private function getVATDetaill($idVAT): array
    {
        $result = [];
        if (!empty($idVAT)) {
            $vat = new Model\Impuesto();
            if ($vat->loadFromCode($idVAT)) {
                $result['vat'] = $vat->iva;
                $result['surcharge'] = $vat->recargo;
            }
        }

        return $result;
    }

    /**
     * Load total data from subaccount
     *
     * @param string $exercise
     * @param string $codeSubAccount
     * @param string $source
     * @return array
     */
    private function getAccountData($exercise, $codeSubAccount, $source): array
    {
        $result = [
            'subaccount' => $codeSubAccount,
            'description' => '',
            'source' => $source,
            'vat' => [],
            'balance' => 0.00,
            'detail' => [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00]
        ];

        if (empty($exercise) or empty($codeSubAccount)) {
            return $result;
        }

        $where = [
            new DataBaseWhere('codsubcuenta', $codeSubAccount),
            new DataBaseWhere('codejercicio', $exercise)
        ];

        $subAccount = new Model\Subcuenta();
        if ($subAccount->loadFromCode('', $where)) {
            $balance = new Model\SubcuentaSaldo();

            $result['description'] = $subAccount->descripcion;
            $result['vat'] = $this->getVatDetaill($subAccount->codimpuesto);
            $result['balance'] = $balance->setSubAccountBalance($subAccount->idsubcuenta, $result['detail']);
        }

        // Return account data
        return $result;
    }
}
