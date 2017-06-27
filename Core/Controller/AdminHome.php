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

        $this->cache->clear();
        
        $this->agente = new \FacturaScripts\Core\Model\Agente();
        $this->almacen = new \FacturaScripts\Core\Model\Almacen();
        $this->divisa = new \FacturaScripts\Core\Model\Divisa();
        $this->ejercicio = new \FacturaScripts\Core\Model\Ejercicio();
        $this->empresa = new \FacturaScripts\Core\Model\Empresa();
        $this->formaPago = new \FacturaScripts\Core\Model\FormaPago();
        $this->pais = new \FacturaScripts\Core\Model\Pais();
        $this->serie = new \FacturaScripts\Core\Model\Serie();
        
        foreach($this->agente->all() as $age) {
            $age->save();
        }
    }

}
