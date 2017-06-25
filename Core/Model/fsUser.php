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

use FacturaScripts\Core\Model\agente;
use FacturaScripts\Core\Model\ejercicio;
use FacturaScripts\Core\Model\fsAccess;
use FacturaScripts\Core\Model\fsPage;

/**
 * Usuario de FacturaScripts. Puede estar asociado a un agente.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class fsUser extends \FacturaScripts\Core\Base\Model  {

    /**
     * Clave primaria. Varchar (12).
     * @var string 
     */
    public $nick;

    /**
     * Contraseña, en sha1
     * @var string 
     */
    public $password;

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
    public $log_key;

    /**
     * TRUE -> el usuario ha iniciado sesión
     * No se guarda en la base de datos
     * @var boolean 
     */
    public $logged_on;

    /**
     * Código del agente/empleado asociado
     * @var string 
     */
    public $codagente;

    /**
     * El objeto agente asignado. Hay que llamar previamente la función get_agente().
     * @var agente 
     */
    public $agente;

    /**
     * TRUE -> el usuario es un administrador
     * @var boolean 
     */
    public $admin;

    /**
     * TRUE -> el usuario esta activo
     * @var boolean
     */
    public $enabled;

    /**
     * Fecha del último login.
     * @var string
     */
    public $last_login;

    /**
     * Hora del último login.
     * @var string
     */
    public $last_login_time;

    /**
     * Última IP usada
     * @var string
     */
    public $last_ip;

    /**
     * Último identificador de navegador usado
     * @var string
     */
    public $last_browser;

    /**
     * Página de inicio.
     * @var string
     */
    public $fs_page;

    /**
     * Plantilla CSS predeterminada.
     * @var string
     */
    public $css;
    private $menu;

    public function __construct($a = FALSE) {
        parent::__construct('fs_users');
        if ($a) {
            $this->nick = $a['nick'];
            $this->password = $a['password'];
            $this->email = $a['email'];
            $this->log_key = $a['log_key'];

            $this->codagente = NULL;
            if (isset($a['codagente'])) {
                $this->codagente = $a['codagente'];
            }

            $this->admin = $this->str2bool($a['admin']);

            $this->last_login = NULL;
            if ($a['last_login']) {
                $this->last_login = Date('d-m-Y', strtotime($a['last_login']));
            }

            $this->last_login_time = NULL;
            if ($a['last_login_time']) {
                $this->last_login_time = $a['last_login_time'];
            }

            $this->last_ip = $a['last_ip'];
            $this->last_browser = $a['last_browser'];
            $this->fs_page = $a['fs_page'];

            $this->css = 'view/css/bootstrap-yeti.min.css';
            if (isset($a['css'])) {
                $this->css = $a['css'];
            }

            $this->enabled = TRUE;
            if (isset($a['enabled'])) {
                $this->enabled = $this->str2bool($a['enabled']);
            }
        } else {
            $this->nick = NULL;
            $this->password = NULL;
            $this->email = NULL;
            $this->log_key = NULL;
            $this->codagente = NULL;
            $this->admin = FALSE;
            $this->enabled = TRUE;
            $this->last_login = NULL;
            $this->last_login_time = NULL;
            $this->last_ip = NULL;
            $this->last_browser = NULL;
            $this->fs_page = NULL;
            $this->css = 'view/css/bootstrap-yeti.min.css';
        }

        $this->logged_on = FALSE;
        $this->agente = NULL;
    }

    /**
     * Inserta valores por defecto a la tabla, en el proceso de creación de la misma.
     * @return string
     */
    protected function install() {
        $this->clean_cache(TRUE);

        /// Esta tabla tiene claves ajenas a agentes y fs_pages
        new \agente();
        new \fs_page();

         $this->miniLog->info('Se ha creado el usuario <b>admin</b> con la contraseña <b>admin</b>.');
        if ($this->db->select("SELECT * FROM agentes WHERE codagente = '1';")) {
            return "INSERT INTO " . $this->table_name . " (nick,password,log_key,codagente,admin,enabled)
            VALUES ('admin','" . sha1('admin') . "',NULL,'1',TRUE,TRUE);";
        } else {
            return "INSERT INTO " . $this->tableName . " (nick,password,log_key,codagente,admin,enabled)
            VALUES ('admin','" . sha1('admin') . "',NULL,NULL,TRUE,TRUE);";
        }
    }

    public function url() {
        if (is_null($this->nick)) {
            return 'index.php?page=admin_users';
        } else
            return 'index.php?page=admin_user&snick=' . $this->nick;
    }

    /**
     * Devuelve el agente/empleado asociado
     * @return boolean|agente
     */
    public function getAgente() {
        if (isset($this->agente)) {
            return $this->agente;
        } else if (is_null($this->codagente)) {
            return FALSE;
        } else {
            $agente = new \agente();
            $agente0 = $agente->get($this->codagente);
            if ($agente0) {
                $this->agente = $agente0;
                return $this->agente;
            } else {
                $this->codagente = NULL;
                $this->save();
                return FALSE;
            }
        }
    }

    public function getAgenteFullname() {
        $agente = $this->getAgente();
        if ($agente) {
            return $agente->getFullname();
        } else
            return $this->nick;
    }

    public function getAgenteArl() {
        $agente = $this->getAgente();
        if ($agente) {
            return $agente->url();
        } else
            return '#';
    }

    /**
     * Devuelve el menú del usuario, el conjunto de páginas a las que tiene acceso.
     * @param boolean $reload
     * @return array
     */
    public function getMenu($reload = FALSE) {
        if (!isset($this->menu) OR $reload) {
            $this->menu = array();
            $page = new \fs_page();

            if ($this->admin OR FS_DEMO) {
                $this->menu = $page->all();
            } else {
                $access = new \fs_access();
                $access_list = $access->allFromNick($this->nick);
                foreach ($page->all() as $p) {
                    foreach ($access_list as $a) {
                        if ($p->name == $a->fs_page) {
                            $this->menu[] = $p;
                            break;
                        }
                    }
                }
            }
        }
        return $this->menu;
    }

    /**
     * Devuelve TRUE si el usuario tiene acceso a la página solicitada.
     * @param string $page_name
     * @return boolean
     */
    public function haveAccessTo($page_name) {
        $status = FALSE;
        foreach ($this->get_menu() as $m) {
            if ($m->name == $page_name) {
                $status = TRUE;
                break;
            }
        }

        return $status;
    }

    /**
     * Devuelve TRUE si el usuario tiene permiso para eliminar elementos en la página solicitada.
     * @param string $page_name
     * @return type
     */
    public function allowDeleteOn($page_name) {
        if ($this->admin OR FS_DEMO) {
            $status = TRUE;
        } else {
            $status = FALSE;
            foreach ($this->getAccesses() as $a) {
                if ($a->fs_page == $page_name) {
                    $status = $a->allow_delete;
                    break;
                }
            }
        }

        return $status;
    }

    /**
     * Devuelve la lista de accesos permitidos del usuario.
     * @return type
     */
    public function getAccesses() {
        $access = new \fs_access();
        return $access->all_from_nick($this->nick);
    }

    public function showLastLogin() {
        if (is_null($this->last_login)) {
            return '-';
        } else {
            return Date('d-m-Y', strtotime($this->last_login)) . ' ' . $this->last_login_time;
        }
    }

    public function setPassword($p = '') {
        $p = trim($p);
        if (mb_strlen($p) > 1 AND mb_strlen($p) <= 32) {
            $this->password = sha1($p);
            return TRUE;
        } else {
            $this->miniLog->critical('La contraseña debe contener entre 1 y 32 caracteres.');
            return FALSE;
        }
    }

    /*
     * Modifica y guarda la fecha de login si tiene una diferencia de más de 5 minutos
     * con la fecha guardada, así se evita guardar en cada consulta
     */

    public function updateLogin() {
        $ltime = strtotime($this->last_login . ' ' . $this->last_login_time);
        if (time() - $ltime >= 300) {
            $this->last_login = Date('d-m-Y');
            $this->last_login_time = Date('H:i:s');

            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $this->last_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $this->last_ip = $_SERVER['REMOTE_ADDR'];
            }

            $this->last_browser = $_SERVER['HTTP_USER_AGENT'];
            $this->save();
        }
    }

    /**
     * Genera una nueva clave de login, para usar en lugar de la contraseña (via cookie),
     * esto impide que dos o más personas utilicen el mismo usuario al mismo tiempo.
     */
    public function newLogkey() {
        if (is_null($this->log_key) OR ! FS_DEMO) {
            $this->log_key = sha1(strval(rand()));
        }

        $this->logged_on = TRUE;
        $this->last_login = Date('d-m-Y');
        $this->last_login_time = Date('H:i:s');

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $this->last_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $this->last_ip = $_SERVER['REMOTE_ADDR'];
        }

        $this->last_browser = $_SERVER['HTTP_USER_AGENT'];
    }

    public function get($n = '') {
        $u = $this->dataBase->select("SELECT * FROM " . $this->tableName . " WHERE nick = " . $this->var2str($n) . ";");
        if ($u) {
            return new \fs_user($u[0]);
        } else
            return FALSE;
    }

    public function exists() {
        if (is_null($this->nick)) {
            return FALSE;
        } else
            return $this->dataBase->select("SELECT * FROM " . $this->tableName . " WHERE nick = " . $this->var2str($this->nick) . ";");
    }

    public function test() {
        $this->nick = trim($this->nick);
        $this->last_browser = $this->noHtml($this->last_browser);

        if (!preg_match("/^[A-Z0-9_\+\.\-]{3,12}$/i", $this->nick)) {
            $this->new_error_msg("Nick no válido. Debe tener entre 3 y 12 caracteres,
            valen números o letras, pero no la Ñ ni acentos.");
            return FALSE;
        } else
            return TRUE;
    }

    public function save() {
        if ($this->test()) {
            $this->clean_cache();

            if ($this->exists()) {
                $sql = "UPDATE " . $this->tableName . " SET password = " . $this->var2str($this->password)
                        . ", email = " . $this->var2str($this->email)
                        . ", log_key = " . $this->var2str($this->log_key)
                        . ", codagente = " . $this->var2str($this->codagente)
                        . ", admin = " . $this->var2str($this->admin)
                        . ", enabled = " . $this->var2str($this->enabled)
                        . ", last_login = " . $this->var2str($this->last_login)
                        . ", last_ip = " . $this->var2str($this->last_ip)
                        . ", last_browser = " . $this->var2str($this->last_browser)
                        . ", last_login_time = " . $this->var2str($this->last_login_time)
                        . ", fs_page = " . $this->var2str($this->fs_page)
                        . ", css = " . $this->var2str($this->css)
                        . "  WHERE nick = " . $this->var2str($this->nick) . ";";
            } else {
                $sql = "INSERT INTO " . $this->tableName . " (nick,password,email,log_key,codagente,admin,enabled,
               last_login,last_login_time,last_ip,last_browser,fs_page,css) VALUES
               (" . $this->var2str($this->nick)
                        . "," . $this->var2str($this->password)
                        . "," . $this->var2str($this->email)
                        . "," . $this->var2str($this->log_key)
                        . "," . $this->var2str($this->codagente)
                        . "," . $this->var2str($this->admin)
                        . "," . $this->var2str($this->enabled)
                        . "," . $this->var2str($this->last_login)
                        . "," . $this->var2str($this->last_login_time)
                        . "," . $this->var2str($this->last_ip)
                        . "," . $this->var2str($this->last_browser)
                        . "," . $this->var2str($this->fs_page)
                        . "," . $this->var2str($this->css) . ");";
            }

            return $this->dataBase->exec($sql);
        } else
            return FALSE;
    }

    public function delete() {
        return $this->dataBase->exec("DELETE FROM " . $this->tableName . " WHERE nick = " . $this->var2str($this->nick) . ";");
    }

    public function clean_cache($full = FALSE) {
        $this->cache->delete('m_fs_user_all');
   
        if ($full) {
            $this->clean_checked_tables();
        }
    }

    /**
     * Devuelve la lista completa de usuarios de FacturaScripts.
     * @return \fs_user
     */
    public function all() {
        /// consultamos primero en la cache
        $userlist = $this->cache->get_array('m_fs_user_all');

        if (!$userlist) {
            /// si no está en la cache, consultamos la base de datos
            $data = $this->dataBase->select("SELECT * FROM " . $this->tableName . " ORDER BY lower(nick) ASC;");
            if ($data) {
                foreach ($data as $u) {
                    $userlist[] = new \fs_user($u);
                }
            }

            /// guardamos en cache
            $this->cache->set('m_fs_user_all', $userlist);
        }

        return $userlist;
    }

    /**
     * Devuelve la lista completa de usuarios activados de FacturaScripts.
     * @return \fs_user
     */
    public function allEnabled() {
        $userlist = array();

        $data = $this->dataBase->select("SELECT * FROM " . $this->tableName . " WHERE enabled = TRUE ORDER BY lower(nick) ASC;");
        if ($data) {
            foreach ($data as $u) {
                $userlist[] = new \fs_user($u);
            }
        }

        return $userlist;
    }

    public function clear() {
        
    }

}
