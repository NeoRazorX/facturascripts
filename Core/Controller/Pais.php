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
 * Description of Paises
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class Pais extends Base\ListController
{

    public function __construct(&$cache, &$i18n, &$miniLog, &$response, $user, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $response, $user, $className);

        $this->icon = "fa-address-card";
        $this->title = "Países";

        $this->fields = [
            ['label' => 'Cod. País', 'field' => 'codpais', 'display' => 'left'],
            ['label' => 'Bultos', 'field' => 'nombre', 'display' => 'left'],
            ['label' => 'Cod. Iso', 'field' => 'codiso', 'display' => 'center'],
            ['label' => 'Validar Prov', 'field' => 'validarprov', 'display' => 'center'],
            ['label' => 'Bandera', 'field' => 'bandera', 'display' => 'none']
        ];

        $this->addOrderBy('codpais');
        $this->addOrderBy('nombre');

        $this->addFilterCheckbox('validarprov', 'Validar Provincia', 'validarprov');
    }

    /**
     * TODO
     */
    public function publicCore()
    {
        parent::publicCore();
    }

    /**
     * TODO
     */
    public function privateCore()
    {
        parent::privateCore();

        // Load data with estructure data
        $where = $this->getWhere();
        $order = $this->getOrderBy($this->selectedOrderBy);
        $model = new Model\Pais();
        $this->count = $model->count($where);
        if ($this->count > 0) {
            $this->cursor = $model->all($where, $order);
        }
    }

    protected function getWhere()
    {
        $result = parent::getWhere();

        if ($this->query != '') {
            $fields = "nombre|codpais|codiso";
            $result[] = new Base\DataBase\DatabaseWhere($fields, $this->query, "LIKE");
        }
        return $result;
    }
}
