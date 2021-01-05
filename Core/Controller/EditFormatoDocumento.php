<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Controller to edit a single item from the FormaPago model
 *
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 */
class EditFormatoDocumento extends EditController
{

    /**
     * Returns the model name.
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'FormatoDocumento';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'printing-format';
        $data['icon'] = 'fas fa-print';
        return $data;
    }

    /**
     * 
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $mvn = $this->getMainViewName();

        switch ($viewName) {
            case $mvn:
                parent::loadData($viewName, $view);
                /// disable company column if there is only one company
                if ($this->empresa->count() < 2) {
                    $view->disableColumn('company');
                }
                $this->loadLogoWidget($view);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    /**
     * 
     * @param BaseView $view
     */
    protected function loadLogoWidget(&$view)
    {
        $columnLogo = $view->columnForName('logo');
        if ($columnLogo) {
            $images = $this->codeModel->all('attached_files', 'idfile', 'filename', true, [
                new DataBaseWhere('mimetype', 'image/gif,image/jpeg,image/png', 'IN')
            ]);
            $columnLogo->widget->setValuesFromCodeModel($images);
        }
    }
}
