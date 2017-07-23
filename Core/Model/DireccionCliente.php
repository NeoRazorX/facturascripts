<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2014-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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

use FacturaScripts\Core\Base\Model;

/**
 * Una dirección de un cliente. Puede tener varias.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class DireccionCliente
{
    use Model;

    /**
     * Clave primaria.
     * @var
     */
    public $id;

    /**
     * Código del cliente asociado.
     * @var
     */
    public $codcliente;
    /**
     * TODO
     * @var
     */
    public $codpais;
    /**
     * TODO
     * @var
     */
    public $apartado;
    /**
     * TODO
     * @var
     */
    public $provincia;
    /**
     * TODO
     * @var
     */
    public $ciudad;
    /**
     * TODO
     * @var
     */
    public $codpostal;
    /**
     * TODO
     * @var
     */
    public $direccion;

    /**
     * TRUE -> esta dirección es la principal para envíos.
     * @var
     */
    public $domenvio;

    /**
     * TRUE -> esta dirección es la principal para facturación.
     * @var
     */
    public $domfacturacion;
    /**
     * TODO
     * @var
     */
    public $descripcion;

    /**
     * Fecha de última modificación.
     * @var
     */
    public $fecha;

    /**
     * DireccionCliente constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->init(__CLASS__, 'dirclientes', 'id');
        $this->clear();
        if (is_array($data) && !empty($data)) {
            $this->loadFromData($data);
        }
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->id = null;
        $this->codcliente = null;
        $this->codpais = null;
        $this->apartado = null;
        $this->provincia = null;
        $this->ciudad = null;
        $this->codpostal = null;
        $this->direccion = null;
        $this->domenvio = true;
        $this->domfacturacion = true;
        $this->descripcion = 'Principal';
        $this->fecha = date('d-m-Y');
    }

    /**
     * Almacena los datos del modelo en la base de datos.
     * @return bool
     */
    public function save()
    {
        $this->apartado = static::noHtml($this->apartado);
        $this->ciudad = static::noHtml($this->ciudad);
        $this->codpostal = static::noHtml($this->codpostal);
        $this->descripcion = static::noHtml($this->descripcion);
        $this->direccion = static::noHtml($this->direccion);
        $this->provincia = static::noHtml($this->provincia);

        /// actualizamos la fecha de modificación
        $this->fecha = date('d-m-Y');

        /// ¿Desmarcamos las demás direcciones principales?
        $sql = '';
        if ($this->domenvio) {
            $sql .= 'UPDATE ' . $this->tableName() . ' SET domenvio = false'
                . ' WHERE codcliente = ' . $this->var2str($this->codcliente) . ';';
        }
        if ($this->domfacturacion) {
            $sql .= 'UPDATE ' . $this->tableName() . ' SET domfacturacion = false'
                . ' WHERE codcliente = ' . $this->var2str($this->codcliente) . ';';
        }

        if ($this->exists()) {
            return $this->saveUpdateCon($sql);
        }

        return $this->saveInsertCon($sql);
    }

    /**
     * TODO
     *
     * @param string $cod
     *
     * @return array
     */
    public function allFromCliente($cod)
    {
        $dirlist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codcliente = ' . $this->var2str($cod)
            . ' ORDER BY id DESC;';

        $data = $this->database->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $dirlist[] = new DireccionCliente($d);
            }
        }

        return $dirlist;
    }

    /**
     * Aplica algunas correcciones a la tabla.
     */
    public function fixDb()
    {
        $sql = 'DELETE FROM ' . $this->tableName() . ' WHERE codcliente NOT IN (SELECT codcliente FROM clientes);';
        $this->database->exec($sql);
    }

    /**
     * Actualiza los datos del modelo en la base de datos.
     *
     * @param string $sql
     *
     * @return bool
     */
    private function saveUpdateCon($sql)
    {
        $sql .= 'UPDATE ' . $this->tableName() . ' SET codcliente = ' . $this->var2str($this->codcliente)
            . ', codpais = ' . $this->var2str($this->codpais)
            . ', apartado = ' . $this->var2str($this->apartado)
            . ', provincia = ' . $this->var2str($this->provincia)
            . ', ciudad = ' . $this->var2str($this->ciudad)
            . ', codpostal = ' . $this->var2str($this->codpostal)
            . ', direccion = ' . $this->var2str($this->direccion)
            . ', domenvio = ' . $this->var2str($this->domenvio)
            . ', domfacturacion = ' . $this->var2str($this->domfacturacion)
            . ', descripcion = ' . $this->var2str($this->descripcion)
            . ', fecha = ' . $this->var2str($this->fecha)
            . '  WHERE id = ' . $this->var2str($this->id) . ';';

        return $this->database->exec($sql);
    }

    /**
     * Inserta los datos del modelo en la base de datos.
     *
     * @param string $sql
     *
     * @return bool
     */
    private function saveInsertCon($sql)
    {
        $sql .= 'INSERT INTO ' . $this->tableName() . ' (codcliente,codpais,apartado,provincia,ciudad,codpostal,
            direccion,domenvio,domfacturacion,descripcion,fecha) VALUES (' . $this->var2str($this->codcliente)
            . ', ' . $this->var2str($this->codpais)
            . ', ' . $this->var2str($this->apartado)
            . ', ' . $this->var2str($this->provincia)
            . ', ' . $this->var2str($this->ciudad)
            . ', ' . $this->var2str($this->codpostal)
            . ', ' . $this->var2str($this->direccion)
            . ', ' . $this->var2str($this->domenvio)
            . ', ' . $this->var2str($this->domfacturacion)
            . ', ' . $this->var2str($this->descripcion)
            . ', ' . $this->var2str($this->fecha) . ');';

        if ($this->database->exec($sql)) {
            $this->id = $this->database->lastval();
            return true;
        }
        return false;
    }
}
