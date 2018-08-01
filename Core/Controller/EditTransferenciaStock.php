<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Lib\ExtendedController;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\TransferenciaStock;
use FacturaScripts\Dinamic\Model\LineaTransferenciaStock;

/**
 * Controller to edit a transfer of stock
 *
 * @author Cristo M. Estévez Hernández <cristom.estevez@gmail.com>
 * @author Rafael San José Tovar <rafael.sanjose@x-netdigital.com>
 */
class EditTransferenciaStock extends ExtendedController\PanelController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'transfers-stock';
        $pagedata['menu'] = 'warehouse';
        $pagedata['icon'] = 'fa-copy';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addEditView('EditTransferenciaStock', 'TransferenciaStock', 'transfer-head');
        $this->addEditListView('EditLineaTransferenciaStock', 'LineaTransferenciaStock', 'lines-transfer');
    }

    /**
     * Run the controller before actions
     *
     * @param string $action
     */
    protected function execPreviousAction($action)
    {
        $code = $this->request->get('code');
        $headTrans = New TransferenciaStock();
        if (!isset($code) || !$headTrans->loadFromCode($code)) {
            return parent::execPreviousAction($action);
        }

        $data = $this->request->request->all();
        if (isset($data['idproducto'])) {
            $idproducto = $data['idproducto'];
            $idvariante = $data['idvariante'];
            $cantidad = $data['cantidad'];
        }

        $lineaStock = New LineaTransferenciaStock();
        $lineFound = isset($data['idlinea']) && $lineaStock->loadFromCode($data['idlinea']);

        $origen = $headTrans->codalmacenorigen;
        $destino = $headTrans->codalmacendestino;
        if ($lineFound) {
            $idproducto = $lineaStock->idproducto;
            $idvariante = $lineaStock->idvariante;
            if ($action == 'delete') {
                $cantidad = $lineaStock->cantidad;
            } else {
                $cantidad = $data['cantidad'] - $lineaStock->cantidad;
            }
        }

        if (isset($cantidad) && ($cantidad < 0)) {
            $cantidad = -$cantidad;
            $tmp = $origen;
            $origen = $destino;
            $destino = $tmp;
        }

        switch ($action) {
            case 'delete':
                // if $idproducto exists, we can delete a line
                if (isset($idproducto) && isset($idvariante) && isset($cantidad)) {
                    $lineaStock->updateStock($destino, $origen, $idproducto, $idvariante, $cantidad);
                    break;
                }

                // If we delete the document, first delete each line
                $lines = $lineaStock->all([new DataBaseWhere('idtrans', $code)]);
                foreach ($lines as $line) {
                    $lineaStock->updateStock($destino, $origen, $line->idproducto, $line->idvariante, $line->cantidad);
                }

                break;
            case 'save':
                $lineaStock->updateStock($origen, $destino, $idproducto, $idvariante, $cantidad);
                break;
        }

        return parent::execPreviousAction($action);
    }

    /**
     * Load view data procedure
     *
     * @param string                      $viewName
     * @param ExtendedController\EditView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'EditTransferenciaStock':
                $code = $this->request->get('code');
                $view->loadData($code);
                break;

            case 'EditLineaTransferenciaStock':
                $idtransferencia = $this->getViewModelValue('EditTransferenciaStock', 'idtrans');
                $where = [new DataBaseWhere('idtrans', $idtransferencia)];
                $view->loadData('', $where, [], 0, 0);
                break;
        }
    }
}
