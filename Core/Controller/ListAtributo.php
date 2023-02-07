<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
    public function getPageData(): array
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
        $this->createViewsAttributes();
        $this->createViewsValues();
    }

    protected function createViewsAttributes(string $viewName = 'ListAtributo'): void
    {
        $this->addView($viewName, 'Atributo', 'attributes', 'fas fa-tshirt');
        $this->addSearchFields($viewName, ['nombre', 'codatributo']);
        $this->addOrderBy($viewName, ['codatributo'], 'code');
        $this->addOrderBy($viewName, ['nombre'], 'name');
    }

    protected function createViewsValues(string $viewName = 'ListAtributoValor'): void
    {
        $this->addView($viewName, 'AtributoValor', 'values', 'fas fa-list');
        $this->addSearchFields($viewName, ['valor', 'codatributo']);
        $this->addOrderBy($viewName, ['codatributo', 'orden', 'valor'], 'sort', 2);
        $this->addOrderBy($viewName, ['codatributo', 'valor'], 'value');
    }
}
