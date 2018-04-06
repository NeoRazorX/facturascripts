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

use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Controller to list the items in the ApiKey model
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class ListApiKey extends ExtendedController\ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'api-keys';
        $pageData['icon'] = 'fa-key';
        $pageData['menu'] = 'admin';

        return $pageData;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $className = $this->getClassName();
        $this->addView('ApiKey', $className);
        $this->addSearchFields($className, ['descripcion', 'apikey', 'nick']);

        $this->addOrderBy($className, 'apikey', 'api-key', 1);
        $this->addOrderBy($className, 'descripcion', 'description');
        $this->addOrderBy($className, 'nick', 'nick');

        $this->addFilterCheckbox($className, 'enabled', 'enabled', 'enabled');
    }
}
