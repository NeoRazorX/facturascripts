<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Controller to list the items in the Atributo model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ListAtributo extends ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'warehouse';
        $data['title'] = 'attributes';
        $data['icon'] = 'fas fa-tshirt';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addView('ListAtributo', 'Atributo', 'attributes', 'fas fa-tshirt');
        $this->addSearchFields('ListAtributo', ['nombre', 'codatributo']);
        $this->addOrderBy('ListAtributo', ['codatributo'], 'code');
        $this->addOrderBy('ListAtributo', ['nombre'], 'name');

        $this->addView('ListAtributoValor', 'AtributoValor', 'values', 'fas fa-list');
        $this->addSearchFields('ListAtributoValor', ['valor', 'codatributo']);
        $this->addOrderBy('ListAtributoValor', ['codatributo', 'orden', 'valor'], 'sort', 2);
        $this->addOrderBy('ListAtributoValor', ['codatributo', 'valor'], 'value');
    }
}
