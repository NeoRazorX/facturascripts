<?php
/**
 * This file is part of FacturaScripts
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
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\ExtendedController;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Model;

/**
 * Controlador para la edición de un registro del modelo EditCuentaEspecial
 *
 * @author Artex Trading sa <jferrer@artextrading.com>
 */
class EditCuentaEspecial extends ExtendedController\EditController
{
   
    /**
     * Devuelve el nombre del modelo
     */
    public function getModelName()
    {
        return 'FacturaScripts\Core\Model\CuentaEspecial';
    }

    /**
     * Devuelve los datos básicos de la página
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'special-account';
        $pagedata['icon'] = 'fa-newspaper-o';
        $pagedata['menu'] = 'accounting';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}