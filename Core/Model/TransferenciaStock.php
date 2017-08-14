<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2016-2017    Carlos Garcia Gomez  neorazorx@gmail.com
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
 * Description of transferencia_stock
 *
 * @author Carlos García Gómez
 */
class TransferenciaStock
{

    use Base\ModelTrait;

    /// clave primaria. integer
    /**
     * TODO
     * @var int
     */
    public $idtrans;

    /**
     * TODO
     * @var string
     */
    public $codalmadestino;

    /**
     * TODO
     * @var string
     */
    public $codalmaorigen;

    /**
     * TODO
     * @var string
     */
    public $fecha;

    /**
     * TODO
     * @var string
     */
    public $hora;

    /**
     * TODO
     * @var string
     */
    public $usuario;

    public function tableName()
    {
        return 'transstock';
    }

    public function primaryColumn()
    {
        return 'idtrans';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->idtrans = null;
        $this->codalmadestino = null;
        $this->codalmaorigen = null;
        $this->fecha = date('d-m-Y');
        $this->hora = date('H:i:s');
        $this->usuario = null;
    }

    /**
     * Devuelve la url donde ver/modificar estos datos
     * @return string
     */
    public function url()
    {
        return 'index.php?page=EditarTransferenciaStock&id=' . $this->idtrans;
    }

    /**
     * TODO
     * @return bool
     */
    public function test()
    {
        if ($this->codalmadestino === $this->codalmaorigen) {
            $this->miniLog->alert('El almacén de orígen y de destino no puede ser el mismo.');
            return false;
        }
        return true;
    }
}
