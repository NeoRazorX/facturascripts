<?php
/**
 * Copyright (C) 2017 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
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
 * Modelo ApiKey para administrar los token de conexión a través de la api
 * que se generarán para sincronizar distintas aplicaciones
 * @author Joe Nilson <joenilson at gmail.com>
 */
class ApiKey
{

    use Base\ModelTrait;

    /**
     * Clave primaria. Id autoincremental
     * @var int
     */
    public $id;

    /**
     * Clave de la API
     * @var string
     */
    public $apikey;

    /**
     * Descripción
     * @var string
     */
    public $descripcion;

    /**
     * Activada/Desactivada
     * @var bool
     */
    public $enabled;

    /**
     * Fecha de alta
     * @var string
     */
    public $f_alta;

    /**
     * Fecha de baja
     * @var string
     */
    public $f_baja;

    /**
     * Nick del usuario
     * @var string
     */
    public $nick;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'fs_api_keys';
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
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->id = null;
        $this->apikey = '';
        $this->descripcion = '';
        $this->enabled = FALSE;
        $this->f_alta = \date('d-m-Y');
        $this->f_baja = null;
        $this->nick = null;
    }
}
