<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018  Carlos García Gómez      <carlos@facturascripts.com>
 * Copyright (C) 2017  Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
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
 * Controller to list the items in the EstadoDocumento model
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class ListEstadoDocumento extends ExtendedController\ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'document-status';
        $pagedata['icon'] = 'fa-tag';
        $pagedata['menu'] = 'admin';

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $className = $this->getClassName();
        $this->addView('\FacturaScripts\Dinamic\Model\EstadoDocumento', $className);
        $this->addSearchFields($className, ['nombre']);

        $this->addOrderBy($className, 'idestado', 'id');
        $this->addOrderBy($className, 'nombre', 'name');
    }
}
