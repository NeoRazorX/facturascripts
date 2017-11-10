<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2015-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * Un Valor para un atributo de artículos.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AtributoValor
{

    use Base\ModelTrait {
        save as private saveTrait;
        saveInsert as private saveInsertTrait;
    }

    /**
     * Clave primaria
     *
     * @var int
     */
    public $id;

    /**
     * Código del atributo relacionado.
     *
     * @var string
     */
    public $codatributo;

    /**
     * Valor del atributo
     *
     * @var string
     */
    public $valor;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'atributos_valores';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'id';
    }

    /**
     * Devuelve el nombre de un atributo
     *
     * @return string
     */
    public function getNombre()
    {
        $nombre = '';

        $sql = 'SELECT * FROM atributos WHERE codatributo = ' . $this->var2str($this->codatributo) . ';';
        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            $nombre = $data[0]['nombre'];
        }

        return $nombre;
    }

    /**
     * Almacena los datos del modelo en la base de datos.
     *
     * @return bool
     */
    public function save()
    {
        $this->valor = self::noHtml($this->valor);

        return $this->saveTrait();
    }

    /**
     * Selecciona todos los atributos de un código de atributo
     *
     * @param string $cod
     *
     * @return self[]
     */
    public function allFromAtributo($cod)
    {
        $lista = [];
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codatributo = ' . $this->var2str($cod)
            . ' ORDER BY valor ASC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $lista[] = new self($d);
            }
        }

        return $lista;
    }

    /**
     * Actualiza los datos del modelo en la base de datos.
     *
     * @return bool
     */
    private function saveUpdate()
    {
        $sql = 'UPDATE atributos_valores SET valor = ' . $this->var2str($this->valor)
            . ', codatributo = ' . $this->var2str($this->codatributo)
            . '  WHERE id = ' . $this->var2str($this->id) . ';';

        return $this->dataBase->exec($sql);
    }

    /**
     * Inserta los datos del modelo en la base de datos.
     *
     * @return bool
     */
    private function saveInsert()
    {
        if ($this->id === null) {
            $this->id = 1;

            $sql = 'SELECT MAX(id) AS max FROM ' . $this->tableName() . ';';
            $data = $this->dataBase->select($sql);
            if (!empty($data)) {
                $this->id = 1 + (int) $data[0]['max'];
            }
        }

        return $this->saveInsertTrait();
    }
}
