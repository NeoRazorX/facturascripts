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

namespace FacturaScripts\Core\Model;

/**
 * Usuario de FacturaScripts.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class FSUser {

    use \FacturaScripts\Core\Base\Model;

    /**
     * Clave primaria. Varchar (50).
     * @var string 
     */
    public $nick;

    /**
     * Contraseña, cifrada con password_hash()
     * @var string 
     */
    private $password;

    /**
     * Email del usuario.
     * @var string 
     */
    public $email;

    /**
     * Clave de sesión. El cliente se la guarda en una cookie,
     * sirve para no tener que guardar la contraseña.
     * Se regenera cada vez que el cliente inicia sesión. Así se
     * impide que dos personas accedan con el mismo usuario.
     * @var string 
     */
    private $logkey;

    /**
     * TRUE -> el usuario es un administrador.
     * @var boolean 
     */
    public $admin;

    /**
     * TRUE -> el usuario esta activo.
     * @var boolean
     */
    public $enabled;

    /**
     * Código del idioma seleccionado para este usuario.
     * @var string
     */
    public $langcode;

    /**
     * Página de inicio.
     * @var string
     */
    public $homepage;

    /**
     * Fecha y hora de la última actividad del usuario.
     * @var string
     */
    public $lastactivity;

    /**
     * Última IP usada.
     * @var string
     */
    public $lastip;

    public function __construct($data = FALSE) {
        $this->init(__CLASS__, 'fs_users', 'nick');
        if ($data) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }

    /**
     * Reseta los valores de este objeto.
     */
    public function clear() {
        $this->nick = NULL;
        $this->password = NULL;
        $this->email = NULL;
        $this->logkey = NULL;
        $this->admin = FALSE;
        $this->enabled = TRUE;
        $this->langcode = FS_LANG;
        $this->homepage = NULL;
        $this->lastactivity = NULL;
        $this->lastip = NULL;
    }

    /**
     * Inserta valores por defecto a la tabla, en el proceso de creación de la misma.
     * @return string
     */
    protected function install() {
        $this->miniLog->info($this->i18n->trans('created-default-admin-account'));
        return "INSERT INTO " . $this->tableName() . " (nick,password,admin,enabled) VALUES ('admin','"
                . password_hash('admin', PASSWORD_DEFAULT) . "',TRUE,TRUE);";
    }

    /**
     * Devuelve la url desde donde editar este usuario.
     * @return string
     */
    public function url() {
        if (is_null($this->nick)) {
            return 'index.php?page=AdminUsers';
        }

        return 'index.php?page=AdminUser&id=' . $this->nick;
    }

    public function setPassword($value) {
        $this->password = password_hash($value, PASSWORD_DEFAULT);
    }

    public function verifyPassword($value) {
        return password_verify($value, $this->password);
    }

    public function newLogkey() {
        $this->logkey = $this->randomString(99);
        return $this->logkey;
    }

    public function verifyLogkey($value) {
        return ($this->logkey === $value);
    }

    public function test() {
        $this->nick = trim($this->nick);

        if (!preg_match("/^[A-Z0-9_\+\.\-]{3,50}$/i", $this->nick)) {
            $this->miniLog->alert($this->i18n->trans('invalid-user-nick', [$this->nick]));
            return FALSE;
        }

        return TRUE;
    }

}
