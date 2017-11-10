<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * Un fabricante de artículos.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class Fabricante
{

    use Base\ModelTrait;

    /**
     * Clave primaria.
     *
     * @var string
     */
    public $codfabricante;

    /**
     * Nombre del fabricante
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
        return 'fabricantes';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'codfabricante';
    }

    /**
     * Devuelve true si no hay errores en los valores de las propiedades del modelo.
     *
     * @return bool
     */
    public function test()
    {
        $status = false;

        $this->codfabricante = self::noHtml($this->codfabricante);
        $this->nombre = self::noHtml($this->nombre);

        if (empty($this->codfabricante) || strlen($this->codfabricante) > 8) {
            $this->miniLog->alert($this->i18n->trans('code-manufacturer-valid-length'));
        } elseif (empty($this->nombre) || strlen($this->nombre) > 100) {
            $this->miniLog->alert($this->i18n->trans('manufacturer-description-not-valid'));
        } else {
            $status = true;
        }

        return $status;
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
        return 'INSERT INTO ' . $this->tableName() . " (codfabricante,nombre) VALUES ('OEM','OEM');";
    }
}
