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

/**
 * Description of ListAsiento
 *
 * @author carlos
 */
class ListAsiento extends ExtendedController\ListController
{

    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);
    }

    public function privateCore(&$response, $user)
    {
        parent::privateCore($response, $user);
    }

    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'Asientos';
        $pagedata['icon'] = 'fa-balance-scale';
        $pagedata['menu'] = 'contabilidad';

        return $pagedata;
    }

    protected function createViews()
    {
        $className = $this->getClassName();
        $index = $this->addView('FacturaScripts\Core\Model\Asiento', $className);
        $this->addSearchFields($index, ['CAST(numero AS VARCHAR)', 'concepto']);

        $this->addOrderBy($index, 'numero', 'number');
        $this->addOrderBy($index, 'fecha', 'date', 2); /// forzamos el orden por defecto fecha desc
    }
}
