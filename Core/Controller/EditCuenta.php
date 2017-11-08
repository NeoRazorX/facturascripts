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
use FacturaScripts\Core\Model;

/**
 * Controlador para la edición de un registro del modelo Fabricante
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 * @author PC REDNET S.L. <luismi@pcrednet.com>
 */
class EditCuenta extends ExtendedController\PanelController
{

   /**
    * Procedimiento para insertar vistas en el controlador
    */
   protected function createViews()
   {
      $this->addEditView('FacturaScripts\Core\Model\Cuenta', 'EditCuenta', 'account');
      $this->addListView('FacturaScripts\Core\Model\Subcuenta', 'ListSubcuenta', 'subaccounts');
   }

   /**
    * Devuele el campo $fieldName del modelo Cuenta
    *
    * @param string $fieldName
    *
    * @return string|boolean
    */
   private function getCuentaFieldValue($fieldName)
   {
      $model = $this->views['EditCuenta']->getModel();
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
         case 'EditCuenta':
            $value = $this->request->get('code');
            $view->loadData($value);
            break;

         case 'ListSubcuenta':
            $where = [new DataBase\DataBaseWhere('idcuenta', $this->getCuentaFieldValue('idcuenta'))];
            $view->loadData($where);
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
      $pagedata['title'] = 'accounts';
      $pagedata['menu'] = 'accounting';
      $pagedata['icon'] = 'fa-bar-chart';
      $pagedata['showonmenu'] = false;

      return $pagedata;
   }
}
