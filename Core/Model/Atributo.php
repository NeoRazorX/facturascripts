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
 * Un atributo para artículos.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Atributo
{

    use Base\ModelTrait {
        save as private traitSave;
    }

    /**
     * Clave primaria.
     *
     * @var string
     */
    public $codatributo;

    /**
     * Nombre del atributo
     *
     * @var string
     */
    public $nombre;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'atributos';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'codatributo';
    }

    /**
     * Obtener los atributos de un código de atributo
     *
     * @return AtributoValor[]
     */
    public function valores()
    {
        $valor0 = new AtributoValor();

        return $valor0->allFromAtributo($this->codatributo);
    }

    /**
     * Obtener atributo por nombre
     *
     * @param string $nombre
     * @param bool   $minusculas
     *
     * @return Atributo|bool
     */
    public function getByNombre($nombre, $minusculas = false)
    {
        $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE nombre = ' . $this->dataBase->var2str($nombre) . ';';
        if ($minusculas) {
            $sql = 'SELECT * FROM ' . $this->tableName()
                . ' WHERE lower(nombre) = ' . $this->dataBase->var2str(mb_strtolower($nombre, 'UTF8') . ';');
        }

        $data = $this->dataBase->select($sql);

        if (!empty($data)) {
            return new self($data[0]);
        }

        return false;
    }

    /**
     * Almacena los datos del modelo en la base de datos.
     *
     * @return bool
     */
    public function save()
    {
        $this->nombre = self::noHtml($this->nombre);

        return $this->traitSave();
    }
}
