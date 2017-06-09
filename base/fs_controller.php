<?php

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

use Symfony\Component\HttpFoundation\Request;

/**
 * Description of fs_controller
 *
 * @author Carlos García Gómez
 */
class fs_controller {

    public $request;
    public $template;
    public $title;
    private $_className;
    private $_i18n;
    private static $_fsFolder;

    public function __construct($folder = '', $className = __CLASS__) {
        if (!isset(self::$_fsFolder)) {
            self::$_fsFolder = $folder;
        }
        
        $this->_className = $className;
        $this->_i18n = new fs_i18n();
        $this->request = Request::createFromGlobals();
        $this->template = $className.'.html';
        $this->title = $className;
    }

}
