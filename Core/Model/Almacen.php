<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2016  Carlos Garcia Gomez  carlos@facturascripts.com
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
 * El almacén donde están físicamente los artículos.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class Almacen
{

    use Base\ModelTrait;
    use Base\ContactInformation;

    /**
     * Clave primaria. Varchar (4).
     * @var string
     */
    public $codalmacen;

    /**
     * Nombre del almacen.
     * @var string
     */
    public $nombre;

    /**
     * Persona de contacto del almacen.
     * @var string
     */
    public $contacto;

    /**
     * Todavía sin uso.
     * @var string
     */
    public $observaciones;

    /**
     * Almacen constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->init(__CLASS__, 'almacenes', 'codalmacen');
        if (is_null($data) || empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    /**
     * Devuelve TRUE si este es almacén predeterminado de la empresa.
     * @return bool
     */
    public function isDefault()
    {
        return ($this->codalmacen === $this->defaultItems->codAlmacen());
    }

    /**
     * Comprueba los datos del almacén, devuelve TRUE si son correctos
     * @return bool
     */
    public function test()
    {
        $status = false;

        $this->codalmacen = trim($this->codalmacen);
        $this->nombre = static::noHtml($this->nombre);
        $this->provincia = static::noHtml($this->provincia);
        $this->poblacion = static::noHtml($this->poblacion);
        $this->direccion = static::noHtml($this->direccion);
        $this->codpostal = static::noHtml($this->codpostal);
        $this->telefono = static::noHtml($this->telefono);
        $this->fax = static::noHtml($this->fax);
        $this->contacto = static::noHtml($this->contacto);

        if (!preg_match('/^[A-Z0-9]{1,4}$/i', $this->codalmacen)) {
            $this->miniLog->alert($this->i18n->trans('store-cod-invalid'));
        } elseif (!(strlen($this->nombre) > 1) && !(strlen($this->nombre) < 100)) {
            $this->miniLog->alert($this->i18n->trans('store-name-invalid'));
        } else {
            $status = true;
        }

        return $status;
    }

    /**
     * Crea la consulta necesaria para crear un nuevo almacen en la base de datos.
     * @return string
     */
    private function install()
    {
        return 'INSERT INTO ' . $this->tableName() . ' (codalmacen,nombre,poblacion,'
            . "direccion,codpostal,telefono,fax,contacto) VALUES ('ALG','ALMACEN GENERAL','','','','','','');";
    }
}
