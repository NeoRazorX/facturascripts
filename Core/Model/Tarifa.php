<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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
namespace FacturaScripts\Core\Model;

/**
 * Una tarifa para los artículos.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class Tarifa
{

    use Base\ModelTrait;

    /**
     * Clave primaria.
     * @var string
     */
    public $codtarifa;

    /**
     * Nombre de la tarifa.
     * @var string
     */
    public $nombre;

    /**
     * Fórmula a aplicar
     * @var
     */
    public $aplicar_a;

    /**
     * no vender por debajo de coste
     * @var bool
     */
    public $mincoste;

    /**
     * no vender por encima de pvp
     * @var bool
     */
    public $maxpvp;

    /**
     * Incremento porcentual o descuento
     * @var float
     */
    private $incporcentual;

    /**
     * Incremento lineal o descuento lineal
     * @var float
     */
    private $inclineal;

    /**
     * Tarifa constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->init('tarifas', 'codtarifa');
        if (empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->codtarifa = null;
        $this->nombre = null;
        $this->incporcentual = 0;
        $this->inclineal = 0;
        $this->aplicar_a = 'pvp';
        $this->mincoste = true;
        $this->maxpvp = true;
    }

    /**
     * Devuelve la url donde ver/modificar estos datos
     * @return string
     */
    public function url()
    {
        return 'index.php?page=VentasArticulos#tarifas';
    }

    /**
     * TODO
     * @return double
     */
    public function x()
    {
        if ($this->aplicar_a === 'pvp') {
            return (0 - $this->incporcentual);
        }
        return $this->incporcentual;
    }

    /**
     * TODO
     *
     * @param float $dto
     */
    public function setX($dto)
    {
        $this->incporcentual = $dto;
        if ($this->aplicar_a === 'pvp') {
            $this->incporcentual = 0 - $dto;
        }
    }

    /**
     * TODO
     * @return double
     */
    public function y()
    {
        if ($this->aplicar_a === 'pvp') {
            return (0 - $this->inclineal);
        }
        return $this->inclineal;
    }

    /**
     * TODO
     *
     * @param float $inc
     */
    public function setY($inc)
    {
        $this->inclineal = $inc;
        if ($this->aplicar_a === 'pvp') {
            $this->inclineal = 0 - $inc;
        }
    }

    /**
     * Devuelve un texto explicativo de lo que hace la tarifa
     * @return string
     */
    public function diff()
    {
        $x = $this->x();
        $y = $this->y();

        $texto = 'Precio de coste ';
        if ($this->aplicar_a === 'pvp') {
            $texto = 'Precio de venta ';
            $x = 0 - $x;
            $y = 0 - $y;
        }

        if ($x !== 0) {
            if ($x > 0) {
                $texto .= '+';
            }

            $texto .= $x . '% ';
        }

        if ($y !== 0) {
            if ($y > 0) {
                $texto .= ' +';
            }

            $texto .= $y;
        }

        return $texto;
    }

    /**
     * Rellenamos los descuentos y los datos de la tarifa de una lista de
     * artículos.
     *
     * @param array $articulos
     */
    public function setPrecios(&$articulos)
    {
        foreach ($articulos as $articulo) {
            $articulo->codtarifa = $this->codtarifa;
            $articulo->tarifa_nombre = $this->nombre;
            $articulo->tarifa_url = $this->url();
            $articulo->dtopor = 0;

            $pvp = $articulo->pvp;
            $articulo->pvp = $articulo->preciocoste() * (100 + $this->x()) / 100 + $this->y();
            if ($this->aplicar_a === 'pvp') {
                if ($this->y() === 0 && $this->x() >= 0) {
                    /// si y === 0 y x >= 0, usamos x como descuento
                    $articulo->dtopor = $this->x();
                } else {
                    $articulo->pvp = $articulo->pvp * (100 - $this->x()) / 100 - $this->y();
                }
            }

            $articulo->tarifa_diff = $this->diff();

            if ($this->mincoste) {
                if ($articulo->pvp * (100 - $articulo->dtopor) / 100 < $articulo->preciocoste()) {
                    $articulo->dtopor = 0;
                    $articulo->pvp = $articulo->preciocoste();
                    $articulo->tarifa_diff = 'Precio de coste alcanzado';
                }
            }

            if ($this->maxpvp) {
                if ($articulo->pvp * (100 - $articulo->dtopor) / 100 > $pvp) {
                    $articulo->dtopor = 0;
                    $articulo->pvp = $pvp;
                    $articulo->tarifa_diff = 'Precio de venta alcanzado';
                }
            }
        }
    }

    /**
     * TODO
     * @return string
     */
    public function getNewCodigo()
    {
        $sql = 'SELECT MAX(' . $this->dataBase->sql2Int('codtarifa') . ') as cod FROM ' . $this->tableName() . ';';
        $cod = $this->dataBase->select($sql);
        if (!empty($cod)) {
            return sprintf('%06s', 1 + (int) $cod[0]['cod']);
        }
        return '000001';
    }

    /**
     * TODO
     * @return bool
     */
    public function test()
    {
        $status = false;

        $this->codtarifa = trim($this->codtarifa);
        $this->nombre = static::noHtml($this->nombre);

        if (empty($this->codtarifa) || strlen($this->codtarifa) > 6) {
            $this->miniLog->alert('Código de tarifa no válido. Debe tener entre 1 y 6 caracteres.');
        } elseif (empty($this->nombre) || strlen($this->nombre) > 50) {
            $this->miniLog->alert('Nombre de tarifa no válido. Debe tener entre 1 y 50 caracteres.');
        } else {
            $status = true;
        }

        return $status;
    }
}
