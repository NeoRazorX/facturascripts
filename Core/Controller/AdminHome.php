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

namespace FacturaScripts\Core\Controller;

/**
 * Description of admin_home
 *
 * @author Carlos García Gómez
 */
class AdminHome extends \FacturaScripts\Core\Base\Controller {

    public $agente;
    public $almacen;
    public $divisa;
    public $ejercicio;
    public $empresa;
    public $formaPago;
    public $pais;
    public $serie;
    
    public function __construct($folder = '', $className = __CLASS__) {
        parent::__construct($folder, $className);
    }

    public function run() {
        parent::run();
        
        /// por ahora desplegamos siempre el contenido de Dinamic, para las pruebas
        $pluginManager = new \FacturaScripts\Core\Base\PluginManager();
        $pluginManager->deploy();

        $this->agente = new \FacturaScripts\Dinamic\Model\agente();
        $this->almacen = new \FacturaScripts\Dinamic\Model\almacen();
        $this->divisa = new \FacturaScripts\Dinamic\Model\divisa();
        $this->ejercicio = new \FacturaScripts\Dinamic\Model\ejercicio();
        $this->empresa = new \FacturaScripts\Dinamic\Model\empresa();
        $this->formaPago = new \FacturaScripts\Dinamic\Model\forma_pago();
        $this->pais = new \FacturaScripts\Dinamic\Model\pais();
        $this->serie = new \FacturaScripts\Dinamic\Model\serie();
    }

}
