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

namespace FacturaScripts\Core\Base;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

/**
 * Clase de la que deben heredar todos los controladores de FacturaScripts.
 *
 * @author Carlos García Gómez
 */
class Controller {

    /**
     * Request sobre la que podemos hacer consultas.
     * @var Request 
     */
    public $request;

    /**
     * Nombre del archivo html para el motor de plantillas.
     * @var string nombre_archivo.html
     */
    public $template;

    /**
     * Título de la página.
     * @var string título de la página.
     */
    public $title;
    
    protected $cache;

    /**
     * Nombre de la clase del controlador
     * @var string __CLASS__
     */
    protected $className;

    /**
     * Gestor de eventos.
     * @var EventDispatcher 
     */
    protected $dispatcher;

    /**
     * Traductor multi-idioma.
     * @var Translator 
     */
    protected $i18n;
    
    /**
     * Gestor de log del sistema.
     * @var MiniLog
     */
    protected $miniLog;

    /**
     * Carpeta de trabajo de FacturaScripts.
     * @var string 
     */
    private static $folder;

    /**
     * Constructor por defecto.
     * @param string $folder 
     * @param string $className 
     */
    public function __construct($folder = '', $className = __CLASS__) {
        if (!isset(self::$folder)) {
            self::$folder = $folder;
        }

        /// obtenemos el nombre de la clase sin el namespace
        $pos = strrpos($className, '\\');
        if ($pos !== FALSE) {
            $className = substr($className, $pos + 1);
        }
        $this->className = $className;
        
        $this->cache = new Cache();
        $this->dispatcher = new EventDispatcher();
        $this->i18n = new Translator();
        $this->miniLog = new MiniLog();
        $this->request = Request::createFromGlobals();
        $this->template = $this->className . '.html';
        $this->title = $this->className;
    }

    /**
     * Ejecuta la lógica del controlador.
     */
    public function run() {
        $this->dispatcher->dispatch('pre-run');
    }

    /**
     * Devuelve la url del controlador actual.
     * @return string
     */
    public function url() {
        return 'index.php?page=' . $this->className;
    }
}
