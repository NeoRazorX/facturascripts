<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

/**
 * Description of admin_home
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AdminHome extends Base\Controller
{
    /**
     * AdminHome constructor.
     *
     * @param Base\Cache      $cache
     * @param Base\Translator $i18n
     * @param Base\MiniLog    $miniLog
     * @param string          $className
     */
    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);

        /// comprobamos el .htaccess
        $this->checkHtaccess();

        /// por ahora desplegamos siempre el contenido de Dinamic, para las pruebas
        $pluginManager = new Base\PluginManager();
        $pluginManager->deploy(true);

        $this->cache->clear();
    }

    /**
     * Devuelve los datos básicos de la página
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'admin';
        $pageData['title'] = 'control-panel';
        $pageData['icon'] = 'fa-wrench';

        return $pageData;
    }

    /**
     * Reestablece el .htaccess por defecto
     */
    private function checkHtaccess()
    {
        if (!file_exists(FS_FOLDER . '/.htaccess')) {
            // TODO: Don't assume that the example exists
            $txt = file_get_contents(FS_FOLDER . '/htaccess-sample');
            file_put_contents('.htaccess', $txt);
        }
    }
}
