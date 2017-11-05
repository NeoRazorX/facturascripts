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

use FacturaScripts\Core\Base\ExtendedController;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DivisaTools;
use FacturaScripts\Core\Model;

/**
 * Description of EditCliente
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class EditCliente extends ExtendedController\PanelController
{

    /**
     * Clase para formatear monedas
     *
     * @var DivisaTools
     */
    private static $divisaTools;

    /**
     * Constructor de la clase
     */
    public function __construct($cache, $i18n, $miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);

        if (!isset(self::$divisaTools)) {
            self::$divisaTools = new DivisaTools();
        }
    }

    /**
     * Procedimiento para insertar vistas en el controlador
     */
    protected function createViews()
    {
        $this->addEditView('FacturaScripts\Core\Model\Cliente', 'EditCliente', 'customer');
        $this->addEditListView('FacturaScripts\Core\Model\DireccionCliente', 'EditDireccionCliente', 'addresses', 'fa-road');
        $this->addListView('FacturaScripts\Core\Model\Cliente', 'ListCliente', 'same-group');
    }

    /**
     * Devuele el campo $fieldName del modelo Cliente
     *
     * @param string $fieldName
     *
     * @return string|boolean
     */
    private function getClienteFieldValue($fieldName)
    {
        $model = $this->views['EditCliente']->getModel();
        return $model->{$fieldName};
    }

    /**
     * Procedimiento encargado de cargar los datos a visualizar
     *
     * @param string $keyView
     * @param ExtendedController\EditView $view
     */
    protected function loadData($keyView, $view)
    {
        switch ($keyView) {
            case 'EditCliente':
                $value = $this->request->get('code');
                $view->loadData($value);
                break;

            case 'EditDireccionCliente':
                $where = [new DataBase\DataBaseWhere('codcliente', $this->getClienteFieldValue('codcliente'))];
                $view->loadData($where);
                break;

            case 'ListCliente':
                $codgroup = $this->getClienteFieldValue('codgrupo');

                if (!empty($codgroup)) {
                    $where = [new DataBase\DataBaseWhere('codgrupo', $codgroup)];
                    $view->loadData($where);
                }
                break;
        }
    }

    /**
     * Devuelve los datos básicos de la página
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'customer';
        $pagedata['icon'] = 'fa-users';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    public function calcClientDeliveryNotes($view)
    {
        $where = [];
        $where[] = new DataBase\DataBaseWhere('codcliente', $this->getClienteFieldValue('codcliente'));
        $where[] = new DataBase\DataBaseWhere('ptefactura', TRUE);

        $totalModel = Model\TotalModel::all('albaranescli', $where, ['total' => 'SUM(total)'], '')[0];
        return self::$divisaTools->format($totalModel->totals['total'], 2);
    }

    public function calcClientInvoicePending($view)
    {
        $where = [];
        $where[] = new DataBase\DataBaseWhere('codcliente', $this->getClienteFieldValue('codcliente'));
        $where[] = new DataBase\DataBaseWhere('estado', 'Pagado', '<>');

        $totalModel = Model\TotalModel::all('reciboscli', $where, ['total' => 'SUM(importe)'], '')[0];
        return self::$divisaTools->format($totalModel->totals['total'], 2);
    }
}
