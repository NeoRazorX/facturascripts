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
use FacturaScripts\Core\Base\ViewController;
use FacturaScripts\Core\Model;

/**
 * Controlador para la lista de Agentes
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ListAgente extends ViewController\ListController
{
    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);

        $this->addOrderBy('codagente', 'Código');
        $this->addOrderBy('nombre');
        $this->addOrderBy('apellidos');
        $this->addOrderBy('provincia');
        
        $this->addFilterSelect('provincia', 'agentes');
    }

    public function privateCore(&$response, $user)
    {
        parent::privateCore($response, $user);

        // Load data with estructure data
        $where = $this->getWhere();
        $order = $this->getOrderBy($this->selectedOrderBy);
        $model = new Model\Agente();
        $this->count = $model->count($where);
        if ($this->count > 0) {
            $this->cursor = $model->all($where, $order);
        }
    }

    protected function getWhere()
    {
        $result = parent::getWhere();

        if ($this->query != '') {
            $fields = "nombre|apellidos|codagente";
            $result[] = new Base\DataBase\DataBaseWhere($fields, $this->query, "LIKE");
        }
        return $result;
    }

    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'Agentes';
        $pagedata['icon'] = 'fa-user-circle-o';
        $pagedata['menu'] = 'admin';
        return $pagedata;
    }

    protected function getColumns()
    {
        return [
            ['label' => 'Codigo', 'field' => 'codagente', 'display' => 'left'],
            ['label' => 'Nombre', 'field' => 'nombre', 'display' => 'left'],
            ['label' => 'Cargo', 'field' => 'cargo', 'display' => 'left'],
            ['label' => '% Com.', 'field' => 'porcomision', 'display' => 'center'],
            ['label' => 'Dirección', 'field' => 'direccion', 'display' => 'left'],
            ['label' => 'Ciudad', 'field' => 'ciudad', 'display' => 'center'],
            ['label' => 'Cod. Postal', 'field' => 'codpostal', 'display' => 'none'],
            ['label' => 'Provincia', 'field' => 'direccion', 'display' => 'center'],
            ['label' => 'Teléfono', 'field' => 'telefono', 'display' => 'left'],
            ['label' => 'Email', 'field' => 'email', 'display' => 'left'],
            ['label' => 'Fec. Alta', 'field' => 'f_alta', 'display' => 'none'],
            ['label' => 'Fec. Baja', 'field' => 'f_baja', 'display' => 'center'],
            ['label' => 'Nacimiento', 'field' => 'f_nacimiento', 'display' => 'none'],
            ['label' => 'Seg. Social', 'field' => 'seg_social', 'display' => 'none'],
            ['label' => 'Cta. Banco', 'field' => 'banco', 'display' => 'none']
        ];        
    }
}
