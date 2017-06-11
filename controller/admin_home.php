<?php
namespace FacturaScripts\controller;
/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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

use FacturaScripts\Base\fs_controller;
use FacturaScripts\Base\fs_plugin_manager;

/**
 * Description of admin_home
 *
 * @author Carlos García Gómez
 */
class admin_home extends fs_controller {

    public function __construct($folder = '', $className = __CLASS__) {
        parent::__construct($folder, $className);

        if ($this->request->get('enable', '') != '') {
            $pluginManager = new fs_plugin_manager();
            $pluginManager->enable($this->request->get('enable'));
            $this->new_message($this->i18n->trans('plugin-enabled'));
        }
    }

}
