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

/**
 * Controller to list the items in the Serie model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ListSerie extends ExtendedController\ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'series';
        $pagedata['icon'] = 'fa-file-text';
        $pagedata['menu'] = 'accounting';

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $className = $this->getClassName();
        $this->addView('FacturaScripts\Core\Model\Serie', $className);
        $this->addSearchFields($className, ['descripcion', 'codserie', 'codcuenta']);

        $this->addOrderBy($className, 'codserie', 'code');
        $this->addOrderBy($className, 'descripcion', 'description');
        $this->addOrderBy($className, 'codejercicio', 'exercise');

        $this->addFilterSelect($className, 'ejercicio', 'series', '', 'codejercicio');
        $this->addFilterCheckbox($className, 'siniva', 'without-tax', 'siniva');
    }
}
