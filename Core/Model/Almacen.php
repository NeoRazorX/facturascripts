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

use FacturaScripts\Core\Base\Model;
use RuntimeException;
use Symfony\Component\Translation\Exception\InvalidArgumentException as TranslationInvalidArgumentException;

/**
 * El almacén donde están físicamente los artículos.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Almacen
{

    use Model;

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
     * Código que representa al páis donde está ubicado el almacen.
     * @var string
     */
    public $codpais;

    /**
     * Nombre de la provincia donde está ubicado el almacen.
     * @var string
     */
    public $provincia;

    /**
     * Nombre de la población donde está ubicado el almacen.
     * @var string
     */
    public $poblacion;

    /**
     * Código postal donde está ubicado el almacen.
     * @var string
     */
    public $codpostal;

    /**
     * Dirección donde está ubicado el almacen.
     * @var string
     */
    public $direccion;

    /**
     * Persona de contacto del almacen.
     * @var string
     */
    public $contacto;

    /**
     * Número de fax del almacen.
     * @var string
     */
    public $fax;

    /**
     * Número de teléfono del almacen.
     * @var string
     */
    public $telefono;

    /**
     * Todavía sin uso.
     * @var string
     */
    public $observaciones;

    /**
     * Almacen constructor.
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->init(__CLASS__, 'almacenes', 'codalmacen');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }

    /**
     * Crea la consulta necesaria para crear un nuevo almacen en la base de datos.
     * @return string
     */
    public function install()
    {
        return 'INSERT INTO ' . $this->tableName() . ' (codalmacen,nombre,poblacion,'
            . "direccion,codpostal,telefono,fax,contacto) VALUES ('ALG','ALMACEN GENERAL','','','','','','');";
    }

    /**
     * Devuelve la URL para ver/modificar los datos de este almacén
     * @return string
     */
    public function url()
    {
        if ($this->codalmacen === null) {
            return 'index.php?page=admin_almacenes';
        }

        return 'index.php?page=admin_almacenes#' . $this->codalmacen;
    }

    /**
     * Devuelve TRUE si este es almacén predeterminado de la empresa.
     * @return bool
     */
    public function isDefault()
    {
        return ( $this->codalmacen === $this->defaultItems->codAlmacen() );
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
}
