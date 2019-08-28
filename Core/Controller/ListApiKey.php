<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\ExtendedController\ListController;

/**
 * Controller to list the items in the ApiKey model
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Francesc Pineda Segarra      <francesc.pineda.segarra@gmail.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class ListApiKey extends ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['submenu'] = 'control-panel';
        $data['title'] = 'api-keys';
        $data['icon'] = 'fas fa-key';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        if (!$this->toolBox()->appSettings()->get('default', 'enable_api', '')) {
            $this->toolBox()->i18nLog()->info('api-disabled');
        }

        $viewName = 'ListApiKey';
        $this->addView($viewName, 'ApiKey', 'api-keys', 'fas fa-key');
        $this->addSearchFields($viewName, ['description', 'apikey', 'nick']);
        $this->addOrderBy($viewName, ['id'], 'id');
        $this->addOrderBy($viewName, ['descripcion'], 'description');
        $this->addOrderBy($viewName, ['creationdate', 'id'], 'date', 2);

        /// filters
        $this->addFilterCheckbox($viewName, 'enabled', 'enabled', 'enabled');
    }
}
