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

/**
 * Una dirección de un proveedor. Puede tener varias.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class DireccionProveedor
{

    use Base\ModelTrait {
        save as private saveTrait;
    }

    /**
     * Clave primaria.
     * @var
     */
    public $id;

    /**
     * Código del proveedor asociado.
     * @var
     */
    public $codproveedor;

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
     * TRUE -> dirección principal
     * @var
     */
    public $direccionppal;

    /**
     * TODO
     * @var
     */
    public $descripcion;

    /**
     * Fecha de la última modificación.
     * @var
     */
    public $fecha;

    /**
     * DireccionProveedor constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->init(__CLASS__, 'dirproveedores', 'id');
        if (is_null($data) || empty($data)) {
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
        $this->id = null;
        $this->codproveedor = null;
        $this->codpais = null;
        $this->apartado = null;
        $this->provincia = null;
        $this->ciudad = null;
        $this->codpostal = null;
        $this->direccion = null;
        $this->direccionppal = true;
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
        if ($this->direccionppal) {
            $sql = 'UPDATE ' . $this->tableName() . ' SET direccionppal = false'
                . ' WHERE codproveedor = ' . $this->var2str($this->codproveedor) . ';';
        }

        if ($this->exists()) {
            return $this->saveUpdateCon($sql);
        }
        return $this->saveInsertCon($sql);
    }

    /**
     * TODO
     *
     * @param string $codprov
     *
     * @return array
     */
    public function allFromProveedor($codprov)
    {
        $dirlist = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codproveedor = ' . $this->var2str($codprov)
            . ' ORDER BY id DESC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $dirlist[] = new DireccionProveedor($d);
            }
        }

        return $dirlist;
    }

    /**
     * Aplica algunas correcciones a la tabla.
     */
    public function fixDb()
    {
        $sql = 'DELETE FROM ' . $this->tableName()
            . ' WHERE codproveedor NOT IN (SELECT codproveedor FROM proveedores);';
        $this->dataBase->exec($sql);
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
        $sql .= 'UPDATE ' . $this->tableName() . ' SET codproveedor = ' . $this->var2str($this->codproveedor)
            . ', codpais = ' . $this->var2str($this->codpais)
            . ', apartado = ' . $this->var2str($this->apartado)
            . ', provincia = ' . $this->var2str($this->provincia)
            . ', ciudad = ' . $this->var2str($this->ciudad)
            . ', codpostal = ' . $this->var2str($this->codpostal)
            . ', direccion = ' . $this->var2str($this->direccion)
            . ', direccionppal = ' . $this->var2str($this->direccionppal)
            . ', descripcion = ' . $this->var2str($this->descripcion)
            . ', fecha = ' . $this->var2str($this->fecha)
            . '  WHERE id = ' . $this->var2str($this->id) . ';';

        return $this->dataBase->exec($sql);
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
        $sql .= 'INSERT INTO ' . $this->tableName() . ' (codproveedor,codpais,apartado,provincia,ciudad,
            codpostal,direccion,direccionppal,descripcion,fecha) VALUES (' . $this->var2str($this->codproveedor)
            . ',' . $this->var2str($this->codpais)
            . ',' . $this->var2str($this->apartado)
            . ',' . $this->var2str($this->provincia)
            . ',' . $this->var2str($this->ciudad)
            . ',' . $this->var2str($this->codpostal)
            . ',' . $this->var2str($this->direccion)
            . ',' . $this->var2str($this->direccionppal)
            . ',' . $this->var2str($this->descripcion)
            . ',' . $this->var2str($this->fecha) . ');';

        if ($this->dataBase->exec($sql)) {
            $this->id = $this->dataBase->lastval();
            return true;
        }
        return false;
    }
}
