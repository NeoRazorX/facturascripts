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
namespace FacturaScripts\Core\Model\Join;

use FacturaScripts\Core\Model\Base\JoinModel;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\Tarifa;
use FacturaScripts\Dinamic\Model\Variante;

/**
 * Description of TarifaProducto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 * 
 * @property string $codtarifa
 * @property float  $coste
 * @property string $descripcion
 * @property int    $idproducto
 * @property int    $idvariante
 * @property float  $margen
 * @property float  $precio
 * @property string $referencia
 * @property float  $stockfis
 */
class TarifaProducto extends JoinModel
{

    /**
     *
     * @var Tarifa[]
     */
    private static $rates = [];

    /**
     * 
     * @param array $data
     */
    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->setMasterModel(new Producto());

        /// needed dependency
        new Variante();
    }

    /**
     * 
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $name === 'preciotarifa' ? $this->priceInRate() : parent::__get($name);
    }

    /**
     * 
     * @return Tarifa
     */
    public function getRate()
    {
        if (isset(self::$rates[$this->codtarifa])) {
            return self::$rates[$this->codtarifa];
        }

        $rate = new Tarifa();
        if ($rate->loadFromCode($this->codtarifa)) {
            self::$rates[$this->codtarifa] = $rate;
        }

        return $rate;
    }

    /**
     * 
     * @return float
     */
    public function priceInRate()
    {
        return $this->getRate()->apply((float) $this->coste, (float) $this->precio);
    }

    /**
     * 
     * @return mixed
     */
    public function primaryColumnValue()
    {
        return $this->idproducto;
    }

    /**
     * 
     * @return array
     */
    protected function getFields(): array
    {
        return [
            'codtarifa' => 'tarifas.codtarifa',
            'coste' => 'variantes.coste',
            'descripcion' => 'productos.descripcion',
            'idproducto' => 'productos.idproducto',
            'idvariante' => 'variantes.idvariante',
            'margen' => 'variantes.margen',
            'precio' => 'variantes.precio',
            'referencia' => 'variantes.referencia',
            'stockfis' => 'variantes.stockfis'
        ];
    }

    /**
     * 
     * @return string
     */
    protected function getSQLFrom(): string
    {
        return 'tarifas, variantes LEFT JOIN productos'
            . ' ON variantes.idproducto = productos.idproducto';
    }

    /**
     * 
     * @return array
     */
    protected function getTables(): array
    {
        return ['productos', 'tarifas', 'variantes'];
    }
}
