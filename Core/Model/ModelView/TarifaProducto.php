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
namespace FacturaScripts\Core\Model\ModelView;

use FacturaScripts\Core\Model\Base\ModelView;
use FacturaScripts\Dinamic\Model\Producto;

/**
 * Description of TarifaProducto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 * 
 * @property string $aplicar
 * @property float  $coste
 * @property int    $idproducto
 * @property bool   $maxpvp
 * @property bool   $mincoste
 * @property float  $precio
 * @property float  $valorx
 * @property float  $valory
 */
class TarifaProducto extends ModelView
{

    /**
     * 
     * @param array $data
     */
    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->setMasterModel(new Producto());
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
     * @return float
     */
    public function priceInRate()
    {
        $finalPrice = 0.0;

        $cost = (float) $this->coste;
        $price = (float) $this->precio;
        $valuex = (float) $this->valorx;
        $valuey = (float) $this->valory;

        switch ($this->aplicar) {
            case 'coste':
                $finalPrice += $cost + ($cost * $valuex / 100) + $valuey;
                break;

            case 'pvp':
                $finalPrice += $price - ($price * $valuex / 100) - $valuey;
                break;
        }

        if ($this->maxpvp && $finalPrice > $price) {
            return $price;
        } elseif ($this->mincoste && $finalPrice < $cost) {
            return $cost;
        }

        return $finalPrice > 0 ? $finalPrice : 0.0;
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
            'aplicar' => 'tarifas.aplicar',
            'codtarifa' => 'tarifas.codtarifa',
            'coste' => 'variantes.coste',
            'descripcion' => 'productos.descripcion',
            'idproducto' => 'productos.idproducto',
            'idvariante' => 'variantes.idvariante',
            'maxpvp' => 'tarifas.maxpvp',
            'mincoste' => 'tarifas.mincoste',
            'precio' => 'variantes.precio',
            'referencia' => 'variantes.referencia',
            'valorx' => 'tarifas.valorx',
            'valory' => 'tarifas.valory',
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
