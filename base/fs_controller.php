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

namespace FacturaScripts\Base;

use Symfony\Component\HttpFoundation\Request;

/**
 * Clase de la que deben heredar todos los controladores de FacturaScripts.
 *
 * @author Carlos García Gómez
 */
class fs_controller {

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

    /**
     * Nombre de la clase del controlador
     * @var string __CLASS__
     */
    protected $className;

    /**
     * Traductor multi-idioma.
     * @var fs_i18n 
     */
    protected $i18n;

    /**
     * Listado de mensajes a mostrar en pantalla.
     * @var array 
     */
    private $messages;

    /**
     * Carpeta de trabajo de FacturaScripts.
     * @var string 
     */
    private static $fsFolder;

    /**
     * Constructor por defecto.
     * @param string $folder 
     * @param string $className 
     */
    public function __construct($folder = '', $className = __CLASS__) {
        if (!isset(self::$fsFolder)) {
            self::$fsFolder = $folder;
        }

        $this->className = $className;
        $this->i18n = new fs_i18n();
        $this->messages = [];
        $this->request = Request::createFromGlobals();
        $this->template = $className . '.html';
        $this->title = $className;
    }

    /**
     * Devuelve la url del controlador actual.
     * @return string
     */
    public function url() {
        return 'index.php?page=' . $this->className;
    }

    /**
     * Añade un mensaje a la lista de mensajes a mostrar al usuario.
     * @param string $msg
     * @param string $type
     */
    public function new_message($msg, $type = 'message') {
        $this->messages[$type][] = $msg;
    }

    /**
     * Devuelve los mensajes a mostrar en pantalla.
     * @param string $type
     * @return array
     */
    public function get_messages($type = 'message') {
        if (isset($this->messages[$type])) {
            return $this->messages[$type];
        } else {
            return [];
        }
    }

}
