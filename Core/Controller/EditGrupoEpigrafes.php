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
 * Controller to edit a single item from the GrupoEpigrafes model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 * @author PC REDNET S.L. <luismi@pcrednet.com>
 */
class EditGrupoEpigrafes extends ExtendedController\PanelController
{
    /**
    * Load views
    */
   protected function createViews()
   {
      $this->addEditView('FacturaScripts\Core\Model\GrupoEpigrafes', 'EditGrupoEpigrafes', 'accounting-heading');
      $this->addListView('FacturaScripts\Core\Model\Epigrafe', 'ListEpigrafe', 'sub-accounts', 'fa-book');
   }
   
   /**
    * Returns the $fieldName value from the GrupoEpigrafe model
    *
    * @param string $fieldName
    *
    * @return mixed
    */
   private function getGrupoEpigrafeFieldValue($fieldName)
   {
      $model = $this->views['EditGrupoEpigrafes']->getModel();
      return $model->{$fieldName};
   }
   
   
   /**
    * Load view data
    *
    * @param string $keyView
    * @param ExtendedController\EditView $view
    */
   protected function loadData($keyView, $view)
   {
      switch ($keyView) {
         case 'EditGrupoEpigrafes':
            $value = $this->request->get('code');
            $view->loadData($value);
            break;

         case 'ListEpigrafe':
            $idgrupo = $this->getGrupoEpigrafeFieldValue('idgrupo');

            if (!empty($idgrupo)) {
               $where = [new DataBase\DataBaseWhere('idgrupo', $idgrupo)];
               $view->loadData($where);
            }
            break;
      }
   }
        
    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'epigraphs-group';
        $pagedata['menu'] = 'accounting';
        $pagedata['icon'] = 'fa-bars';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}
