<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model;

use FacturaScripts\Dinamic\Model\Agente as DinAgente;
use FacturaScripts\Dinamic\Model\Cliente as DinCliente;
use FacturaScripts\Dinamic\Model\Producto as DinProducto;

/**
 * List of a sellers commissions.
 *
 * @author Artex Trading s.a. <jcuello@artextrading.com>
 */
class Comision extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * code of agent.
     *
     * @var string
     */
    public $codagente;

    /**
     * code of customer.
     *
     * @var string
     */
    public $codcliente;

    /**
     * code of family.
     *
     * @var string
     */
    public $codfamilia;

    /**
     * Primary Key
     *
     * @var int
     */
    public $idcomision;

    /**
     * Link to company model
     *
     * @var int
     */
    public $idempresa;

    /**
     * code of product.
     *
     * @var int
     */
    public $idproducto;

    /**
     * Commission percentage.
     *
     * @var float
     */
    public $porcentaje;

    /**
     *
     * @var int
     */
    public $prioridad;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->porcentaje = 0.00;
        $this->prioridad = 0;
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        new DinAgente();
        new DinCliente();
        new DinProducto();

        return parent::install();
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idcomision';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'comisiones';
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        if (empty($this->idempresa)) {
            $this->idempresa = $this->toolBox()->appSettings()->get('default', 'idempresa');
        }

        return parent::test();
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'ListAgente?activetab=List')
    {
        return parent::url($type, $list);
    }
}
