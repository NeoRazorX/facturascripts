<?php
/**
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
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\Utils;

/**
 * Usuario de FacturaScripts.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class User
{

    use Base\ModelTrait {
        get as private getTrait;
    }
    use Utils;

    /**
     * Clave primaria. Varchar (50).
     * @var string
     */
    public $nick;

    /**
     * Email del usuario.
     * @var string
     */
    public $email;

    /**
     * TRUE -> el usuario es un administrador.
     * @var bool
     */
    public $admin;

    /**
     * TRUE -> el usuario esta activo.
     * @var bool
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

    /**
     * Contraseña, cifrada con password_hash()
     * @var string
     */
    private $password;

    /**
     * Clave de sesión. El cliente se la guarda en una cookie,
     * sirve para no tener que guardar la contraseña.
     * Se regenera cada vez que el cliente inicia sesión. Así se
     * impide que dos personas accedan con el mismo usuario.
     * @var string
     */
    private $logkey;

    /**
     * User constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->init('fs_users', 'nick');
        if (empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->nick = null;
        $this->password = null;
        $this->email = null;
        $this->logkey = null;
        $this->admin = false;
        $this->enabled = true;
        $this->langcode = FS_LANG;
        $this->homepage = null;
        $this->lastactivity = null;
        $this->lastip = null;
    }

    /**
     * Devuelve el usuario con el nick solicitado
     *
     * @param string $nick
     *
     * @return User|bool
     */
    public function get($nick)
    {
        return $this->getTrait($nick);
    }

    /**
     * Devuelve la url desde donde editar este usuario.
     * @return string
     */
    public function url()
    {
        if ($this->nick === null) {
            return 'index.php?page=AdminUsers';
        }

        return 'index.php?page=AdminUser&id=' . $this->nick;
    }

    /**
     * Asigna la contraseña dada al usuario.
     *
     * @param string $value
     */
    public function setPassword($value)
    {
        $this->password = password_hash($value, PASSWORD_DEFAULT);
    }

    /**
     * Verifica si la contraseña dada es correcta.
     *
     * @param string $value
     *
     * @return bool
     */
    public function verifyPassword($value)
    {
        return password_verify($value, $this->password);
    }

    /**
     * Genera una nueva clave de login para el usuario.
     * Además actualiza lastactivity y asigna la IP proporcionada.
     *
     * @param string $ipAddress
     *
     * @return string
     */
    public function newLogkey($ipAddress)
    {
        $this->lastactivity = date('d-m-Y H:i:s');
        $this->lastip = $ipAddress;
        $this->logkey = static::randomString(99);
        return $this->logkey;
    }

    /**
     * Verifica la clave de login proporcionada.
     *
     * @param string $value
     *
     * @return bool
     */
    public function verifyLogkey($value)
    {
        return ($this->logkey === $value);
    }

    /**
     * Devuelve true si no hay errores en los valores de las propiedades del modelo.
     * Se ejecuta dentro del método save.
     * @return bool
     */
    public function test()
    {
        $this->nick = trim($this->nick);

        if (!preg_match("/^[A-Z0-9_\+\.\-]{3,50}$/i", $this->nick)) {
            $this->miniLog->alert($this->i18n->trans('invalid-user-nick', [$this->nick]));
            return false;
        }

        return true;
    }

    /**
     * Inserta valores por defecto a la tabla, en el proceso de creación de la misma.
     * @return string
     */
    public function install()
    {
        /// hay una clave ajena a fs_pages, así que cargamos el modelo necesario
        new Page();

        $this->miniLog->info($this->i18n->trans('created-default-admin-account'));
        return 'INSERT INTO ' . $this->tableName() . " (nick,password,admin,enabled) VALUES ('admin','"
            . password_hash('admin', PASSWORD_DEFAULT) . "',TRUE,TRUE);";
    }
}
