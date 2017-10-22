<?php
/*
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

    public $id;
    public $apikey;
    public $descripcion;
    public $enabled;
    public $f_alta;
    public $f_baja;
    public $nick;

    public function tableName()
    {
        return 'fs_api_keys';
    }

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
