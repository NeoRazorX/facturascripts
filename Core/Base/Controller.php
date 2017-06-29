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
     * Gestor de acceso a cache.
     * @var Cache
     */
    protected $cache;

    /**
     * Nombre de la clase del controlador (aunque se herede de esta clase, el nombre
     * de la clase final lo tendremos aquí).
     * @var string __CLASS__
     */
    private $className;

    /**
     * Gestor de eventos.
     * @var EventDispatcher 
     */
    protected $dispatcher;

    /**
     * Motor de traducción.
     * @var Translator 
     */
    protected $i18n;

    /**
     * Gestor de log de la app.
     * @var MiniLog
     */
    protected $miniLog;

    /**
     * Request sobre la que podemos hacer consultas.
     * @var Request 
     */
    public $request;

    /**
     * Nombre del archivo html para el motor de plantillas.
     * @var string nombre_archivo.html
     */
    private $template;

    /**
     * Título de la página.
     * @var string título de la página.
     */
    public $title;

    /**
     * Inicia todos los objetos y propiedades.
     * @param Cache $cache
     * @param Translator $i18n
     * @param MiniLog $miniLog
     * @param Request $request
     * @param string $className
     */
    public function __construct(&$cache, &$i18n, &$miniLog, &$request, $className) {
        $this->cache = $cache;
        $this->className = $className;
        $this->dispatcher = new EventDispatcher();
        $this->i18n = $i18n;
        $this->miniLog = $miniLog;
        $this->request = $request;
        $this->template = $this->className . '.html';
        $this->title = $this->className;
    }
    
    /**
     * Devuelve el template HTML a utilizar para este controlador.
     * @return type
     */
    public function getTemplate() {
        return $this->template;
    }
    
    /**
     * Establece el template HTML a utilizar para este controlador.
     * @param string $template
     */
    public function setTemplate($template) {
        $this->template = $template;
    }

    /**
     * Devuelve la url del controlador actual.
     * @return string
     */
    public function url() {
        return 'index.php?page=' . $this->className;
    }

    /**
     * Ejecuta la lógica del controlador.
     */
    public function run() {
        $this->dispatcher->dispatch('pre-run');
    }

}
