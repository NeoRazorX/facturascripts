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
        $pagedata['title'] = 'document-states';
        $pagedata['icon'] = 'fa-tags';
        $pagedata['menu'] = 'admin';

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addView('EstadoDocumento', 'ListEstadoDocumento', 'states', 'fa-tags');
        $this->addSearchFields('ListEstadoDocumento', ['nombre']);

        $this->addOrderBy('ListEstadoDocumento', 'idestado', 'id');
        $this->addOrderBy('ListEstadoDocumento', 'nombre', 'name');

        $this->addFilterSelect('ListEstadoDocumento', 'tipodoc', 'estados_documentos', 'tipodoc', 'tipodoc');
        $this->addFilterSelect('ListEstadoDocumento', 'generadoc', 'estados_documentos', 'generadoc', 'generadoc');
        $this->addFilterSelect('ListEstadoDocumento', 'actualizastock', 'estados_documentos', 'actualizastock', 'actualizastock');
        $this->addFilterCheckbox('ListEstadoDocumento', 'predeterminado', 'default');
        $this->addFilterCheckbox('ListEstadoDocumento', 'editable', 'editable');
    }
}
