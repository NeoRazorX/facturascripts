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

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Base\ExtendedController;

/**
 * Controlador para la edición de un registro del modelo Familia
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class EditSerie extends ExtendedController\EditController
{

    /**
     * EditFamilia constructor.
     *
     * @param Base\Cache $cache
     * @param Base\Translator $i18n
     * @param Base\MiniLog $miniLog
     * @param string $className
     */
    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);

        // Establecemos el modelo de datos
        $this->modelName = 'FacturaScripts\Core\Model\Serie';
    }

    /**
     * Devuelve los datos básicos de la página
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'series';
        $pagedata['menu'] = 'accounting';
        $pagedata['icon'] = 'fa-file-text';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}
