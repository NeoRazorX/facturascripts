<?php

/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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
use FacturaScripts\Core\Model;

/**
 * Description of admin_home
 *
 * @author Carlos García Gómez
 */
class AdminHome extends Base\Controller {

    public $agente;
    public $almacen;
    public $divisa;
    public $ejercicio;
    public $formaPago;
    public $pais;
    public $serie;
    
    public function __construct(&$cache, &$i18n, &$miniLog, &$response, $user, $className) {
        parent::__construct($cache, $i18n, $miniLog, $response, $user, $className);
        
        /// por ahora desplegamos siempre el contenido de Dinamic, para las pruebas
        $pluginManager = new Base\PluginManager();
        $pluginManager->deploy();

        $this->agente = new Model\Agente();
        $this->almacen = new Model\Almacen();
        $this->divisa = new Model\Divisa();
        $this->ejercicio = new Model\Ejercicio();
        $this->formaPago = new Model\FormaPago();
        $this->pais = new Model\Pais();
        $this->serie = new Model\Serie();
    }
    
    public function publicCore() {
        parent::publicCore();
    }
    
    public function privateCore() {
        parent::privateCore();
    }

}
