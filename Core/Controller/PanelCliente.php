<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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

/**
 * Description of PanelSettings
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class PanelCliente extends ExtendedController\PanelController
{

    protected function createViews()
    {
        $this->addEditView('FacturaScripts\Core\Model\Cliente', 'EditCliente', $this->i18n->trans('customer'));
        $this->addEditListView('FacturaScripts\Core\Model\DireccionCliente', 'EditDireccionCliente', $this->i18n->trans('addresses'), 'fa-road');
        $this->addListView('FacturaScripts\Core\Model\Cliente', 'ListCliente', $this->i18n->trans('same-group'));
    }

    private function getClientFieldValue($fieldName)
    {
        $model = $this->views['EditCliente']->getModel();
        return $model->{$fieldName};
    }    
    
    protected function loadData($keyView, $view)
    {
        switch ($keyView) {
            case 'EditCliente':
                $value = $this->request->get('code');
                $view->loadData($value);
                break;

            case 'EditDireccionCliente':
                $where = [new DataBase\DataBaseWhere('codcliente', $this->getClientFieldValue('codcliente'))];
                $view->loadData($where);                
                break;
            
            case 'ListCliente':
                $codgroup = $this->getClientFieldValue('codgrupo');

                if (!empty($codgroup)) {
                    $where = [new DataBase\DataBaseWhere('codgrupo', $codgroup)];
                    $view->loadData($where);
                }
                break;
        }
    }

    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'customers';
        $pagedata['icon'] = 'fa-users';
        $pagedata['showonmenu'] = FALSE;

        return $pagedata;
    }
}
