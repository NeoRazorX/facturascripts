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
 * Define que un usuario tiene acceso a una página concreta
 * y si tiene permisos de eliminación en esa página.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class FSPageRules extends \FacturaScripts\Core\Base\Model {

    public $id;

    /**
     * Nick del usuario.
     * @var string 
     */
    public $nick;

    /**
     * Nombre de la página (nombre del controlador).
     * @var string
     */
    public $pagename;

    /**
     * Otorga permisos al usuario a eliminar elementos en la página.
     * @var boolean 
     */
    public $allowdelete;
    public $allowupdate;

    public function __construct($data = FALSE) {
        parent::__construct('fs_access', 'id');
        if ($data) {
            $this->id = (int) $data['id'];
            $this->nick = $data['nick'];
            $this->pagename = $data['pagename'];
            $this->allowdelete = $this->str2bool($data['allowdelete']);
            $this->allowupdate = $this->str2bool($data['allowupdate']);
        } else {
            $this->clear();
        }
    }

    public function clear() {
        $this->id = NULL;
        $this->nick = NULL;
        $this->pagename = NULL;
        $this->allowdelete = NULL;
        $this->allowupdate = NULL;
    }

    protected function install() {
        return '';
    }

    public function save() {
        if ($this->exists()) {
            $sql = "UPDATE " . $this->tableName . " SET allowdelete = " . $this->var2str($this->allowdelete)
                    . ", allowupdate = " . $this->var2str($this->allowupdate)
                    . ", nick = " . $this->var2str($this->nick)
                    . ", pagename = " . $this->var2str($this->pagename)
                    . "  WHERE id = " . $this->var2str($this->id) . ";";
            return $this->dataBase->exec($sql);
        }

        $sql = "INSERT INTO " . $this->tableName . " (nick,pagename,allowdelete,allowupdate) VALUES "
                . "(" . $this->var2str($this->nick)
                . "," . $this->var2str($this->pagename)
                . "," . $this->var2str($this->allowdelete)
                . "," . $this->var2str($this->allowupdate) . ");";

        if ($this->dataBase->exec($sql)) {
            $this->id = $this->dataBase->lastval();
            return TRUE;
        }
        
        return FALSE;
    }

    public function all() {
        $accesslist = array();

        $data = $this->dabaBase->select("SELECT * FROM " . $this->tableName . ";");
        if ($data) {
            foreach ($data as $a) {
                $accesslist[] = new FSPageRules($a);
            }
        }

        return $accesslist;
    }

}
