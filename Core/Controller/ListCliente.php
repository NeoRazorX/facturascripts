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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\ExtendedController;
use FacturaScripts\Core\Model;

/**
 * Controlador para la lista de clientes
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ListCliente extends ExtendedController\ListController
{
    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);

        $this->addOrderBy('nombre', 'name');
        $this->addOrderBy('fecha', 'date');
        $this->addOrderBy('codcliente', 'code');

        $this->addFilterSelect('provincia', 'clientes', '', 'codprovincia');
        $this->addFilterSelect('ciudad', 'clientes');
        $this->addFilterSelect('grupo', 'clientes', '', 'codgrupo');

        $this->addFilterCheckbox('debaja', 'De baja');

        $this->model = new Model\Cliente();
    }

    public function privateCore(&$response, $user)
    {
        parent::privateCore($response, $user);
    }

    protected function getWhere()
    {
        $result = parent::getWhere();

        if ($this->query != '') {
            $fields = "nombre|razonsocial|codcliente";
            $result[] = new DataBaseWhere($fields, $this->query, "LIKE");
        }

        return $result;
    }

    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'Clientes';
        $pagedata['icon'] = 'fa-users';
        $pagedata['menu'] = 'ventas';

        return $pagedata;
    }
}
