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

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Model;

/**
 * Controlador de ejemplo para la implantación de ListController
 * como controlador y vista genérica para los modelos
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class Ejemplo extends Base\ListController
{

    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);

        $this->icon = "fa-address-card";

        $this->fields = [
            ['label' => 'Codigo', 'field' => 'codcliente', 'display' => 'left'],
            ['label' => 'Nombre', 'field' => 'nombre', 'display' => 'left'],
            ['label' => 'Razón Social', 'field' => 'razonsocial', 'display' => 'left'],
            ['label' => 'id. Fiscal', 'field' => 'cifnif', 'display' => 'left'],
            ['label' => 'Teléfono', 'field' => 'telefono1', 'display' => 'left'],
            ['label' => 'Mail', 'field' => 'email', 'display' => 'left']
        ];

        $this->addOrderBy('codcliente');
        $this->addOrderBy('nombre');

        $this->addFilterSelect('codgrupo', 'gruposclientes');
        $this->addFilterCheckbox('debaja', 'De baja', '', TRUE);
        $this->addFilterDatePicker('fechabaja', 'Fec. Baja');
    }

    public function publicCore(&$response)
    {
        parent::publicCore($response);
    }

    public function privateCore(&$response, $user)
    {
        parent::privateCore($response, $user);

        // Load data with estructure data
        $where = $this->getWhere();
        $order = $this->getOrderBy($this->selectedOrderBy);
        $modelAux = new Model\GrupoClientes();   //solo para asegurar que existe la tabla
        $model = new Model\Cliente();                            // CAMBIAR POR EL MODELO A PROBAR
        $this->count = $model->count($where);
        if ($this->count > 0) {
            $this->cursor = $model->all($where, $order);
        }
    }

    protected function getWhere()
    {
        $result = parent::getWhere();

        if ($this->query != '') {
            $fields = "nombre|razonsocial|cifnif|codcliente";
            $result[] = new Base\DataBase\DatabaseWhere($fields, $this->query, "LIKE");
        }
        return $result;
    }
}
