<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2017  Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
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
 * Una provincia.
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class Provincia
{
    use Base\ModelTrait;

    /**
     * Identificar del registro.
     *
     * @var string
     */
    public $id;

    /**
     * Código de país asociado a la provincia.
     *
     * @var string
     */
    public $codpais;

    /**
     * Nombre de la provincia
     *
     * @var string
     */
    public $provincia;

    /**
     * Código 'normalizado' en España para identificar a las provincias
     * @url: https://es.wikipedia.org/wiki/Provincia_de_España#Denominaci.C3.B3n_y_lista_de_las_provincias
     *
     * @var string
     */
    public $codisoprov;

    /**
     * Código postal asociado a la provincia
     * @url: https://upload.wikimedia.org/wikipedia/commons/5/5c/2_digit_postcode_spain.png
     *
     * @var string
     */
    public $codpostal2d;

    /**
     * Latitud asociada al lugar
     *
     * @var float
     */
    public $latitud;

    /**
     * Longitud asociada al lugar
     *
     * @var float
     */
    public $longitud;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'provincias';
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
     * Esta función es llamada al crear la tabla del modelo. Devuelve el SQL
     * que se ejecutará tras la creación de la tabla. útil para insertar valores
     * por defecto.
     *
     * @return string
     */
    public function install()
    {
        // TODO: Load from CSV realpath('Core/Model/DefaultData/ES-provincias.csv')
        return '';
    }

    /**
     * Devuelve las provincias asociadas a un país ordenas alfabeticamente.
     *
     * @param $codpais
     *
     * @return self[]
     */
    public function getProvincias($codpais)
    {
        $list = [];

        $query = 'SELECT * FROM ' . self::tableName()
            . ' WHERE codpais = ' . $this->var2str($codpais)
            . ' ORDER BY lower(provincia) ASC';

        $data = $this->dataBase->select($query);
        if (!empty($data)) {
            foreach ($data as $d) {
                $list[] = new self($d);
            }
        }

        return $list;
    }

    /**
     * Devuelve un array con las combinaciones que contienen $query en su provincia
     * o codisoprov.
     *
     * @param string $search
     * @param int    $offset
     *
     * @return self[]
     */
    public function search($search, $offset = 0)
    {
        $list = [];
        $search = mb_strtolower(self::noHtml($search), 'UTF8');

        $query = 'SELECT * FROM ' . self::tableName() . ' WHERE ';
        if (is_numeric($search)) {
            $query .= "codpostal2d LIKE '%" . $search . "%'";
        } else {
            $search = str_replace(' ', '%', $search);
            $query .= "lower(provincia) LIKE '%" . $search . "%' OR lower(codisoprov) LIKE '%" . $search . "%'";
        }
        $query .= ' ORDER BY lower(provincia) ASC';

        $data = $this->dataBase->selectLimit($query, FS_ITEM_LIMIT, $offset);
        if (!empty($data)) {
            foreach ($data as $d) {
                $list[] = new self($d);
            }
        }

        return $list;
    }
}
