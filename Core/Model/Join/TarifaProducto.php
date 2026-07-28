<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Template\JoinModel;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\Tarifa;
use FacturaScripts\Dinamic\Model\Variante;

/**
 * Modelo auxiliar para obtener los precios de los productos según la tarifa.
 * Combina cada tarifa con todas las variantes y sus productos, y expone el
 * atributo calculado preciotarifa con el precio resultante de aplicar la
 * tarifa a cada variante.
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * @property string $codtarifa
 * @property float $coste
 * @property string $descripcion
 * @property int $idproducto
 * @property int $idvariante
 * @property float $margen
 * @property float $precio
 * @property string $referencia
 * @property float $stockfis
 */
class TarifaProducto extends JoinModel
{
    /** @var Tarifa[] Caché de tarifas por código, compartida entre instancias. */
    private static $rates = [];

    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->setMasterModel(new Producto());

        // dependencia necesaria: fuerza la carga de la clase Variante
        // para que esté disponible en Dinamic
        new Variante();
    }

    /** El atributo preciotarifa no viene de la consulta: se calcula al leerlo. */
    public function __get($name)
    {
        return $name === 'preciotarifa' ? $this->priceInRate() : parent::__get($name);
    }

    /** Devuelve la tarifa de esta línea, cacheada por código. */
    public function getRate(): Tarifa
    {
        if (isset(self::$rates[$this->codtarifa])) {
            return self::$rates[$this->codtarifa];
        }

        $rate = new Tarifa();
        if ($rate->load($this->codtarifa)) {
            self::$rates[$this->codtarifa] = $rate;
        }

        return $rate;
    }

    public function id()
    {
        return $this->idproducto;
    }

    /**
     * Calcula el precio de la variante tras aplicar la tarifa. Si encuentra la
     * variante, aplica la tarifa sobre ella y su producto; si no, aplica la
     * tarifa directamente sobre el coste y el precio de esta línea.
     */
    public function priceInRate(): float
    {
        // intentamos obtener la variante para aplicar mejor la tarifa
        $variant = new Variante();
        if ($variant->loadWhereEq('referencia', $this->referencia)) {
            $product = $variant->getProducto();
            return $this->getRate()->applyTo($variant, $product);
        }

        return $this->getRate()->apply((float)$this->coste, (float)$this->precio);
    }

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
            'stockfis' => 'variantes.stockfis',
            'preciotarifa' => 0,
        ];
    }

    protected function getSQLFrom(): string
    {
        return 'tarifas, variantes LEFT JOIN productos'
            . ' ON variantes.idproducto = productos.idproducto';
    }

    protected function getTables(): array
    {
        return ['productos', 'tarifas', 'variantes'];
    }
}
