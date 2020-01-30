<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Description of ListIdentificadorFiscal
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ListIdentificadorFiscal extends ListController
{

    /**
     * 
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'fiscal-id';
        $data['icon'] = 'far fa-id-card';
        return $data;
    }

    protected function createViews()
    {
        $viewName = 'ListIdentificadorFiscal';
        $this->addView($viewName, 'IdentificadorFiscal', 'fiscal-id', 'far fa-id-card');
        $this->addSearchFields($viewName, ['tipoidfiscal']);
        $this->addOrderBy($viewName, ['codeid'], 'code');
        $this->addOrderBy($viewName, ['tipoidfiscal'], 'name', 1);
    }
}
