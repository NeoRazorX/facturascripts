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

use FacturaScripts\Core\Model\ApiKey;

/**
 * Modelo para ApiKeyAccess, en esta tabla se guardan los accesos a cada tabla 
 * del sistema asociados a un idapikey
 * las opciones actuales son DELETE / GET / POST / PUT 
 * @author Joe Nilson <joenilson at gmail.com>
 */
class ApiKeyAccess {
    
    use Base\ModelTrait;
    
    public $id;
    public $idapikey;
    public $resource;
    public $allow_put;
    public $allow_get;
    public $allow_post;
    public $allow_delete;
    public function tableName()
    {
        return 'fs_api_keys_access';
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
        $this->idapikey = '';
        $this->resource = '';
        $this->allow_put = FALSE;
        $this->allow_get = FALSE;
        $this->allow_post = FALSE;
        $this->allow_delete = FALSE;
    }
}
