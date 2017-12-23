<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\App\AppSettings;

/**
 * Usuario de FacturaScripts.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class User
{

    use Base\ModelTrait {
        get as private traitGet;
        clear as traitClear;
    }

    /**
     * Primary key. Varchar (50).
     *
     * @var string
     */
    public $nick;

    /**
     * Identificador de empresa seleccionada
     */
    public $idempresa;

    /**
     * Email del usuario.
     *
     * @var string
     */
    public $email;

    /**
     * True -> el usuario es un administrador.
     *
     * @var bool
     */
    public $admin;

    /**
     * True -> el usuario esta activo.
     *
     * @var bool
     */
    public $enabled;

    /**
     * Código del idioma seleccionado para este usuario.
     *
     * @var string
     */
    public $langcode;

    /**
     * Página de inicio.
     *
     * @var string
     */
    public $homepage;

    /**
     * Fecha y hora de la última actividad del usuario.
     *
     * @var string
     */
    public $lastactivity;

    /**
     * Última IP usada.
     *
     * @var string
     */
    public $lastip;

    /**
     * Contraseña, cifrada con password_hash()
     *
     * @var string
     */
    public $password;

    /**
     * Clave de sesión. El cliente se la guarda en una cookie,
     * sirve para no tener que guardar la contraseña.
     * Se regenera cada vez que el cliente inicia sesión. Así se
     * impide que dos personas accedan con el mismo usuario.
     *
     * @var string
     */
    private $logkey;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'fs_users';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'nick';
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        /// hay una clave ajena a fs_pages, así que cargamos el modelo necesario
        new Page();
        new Empresa();

        self::$miniLog->info(self::$i18n->trans('created-default-admin-account'));

        return 'INSERT INTO ' . static::tableName() . " (nick,password,admin,enabled,idempresa,langcode,homepage)"
            . " VALUES ('admin','" . password_hash('admin', PASSWORD_DEFAULT) . "',TRUE,TRUE,'1','" . FS_LANG . "','AdminHome');";
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        $this->traitClear();
        $this->langcode = FS_LANG;
        $this->homepage = 'Dashboard';
        $this->idempresa = AppSettings::get('default', 'idempresa', 1);
        $this->enabled = true;
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
        return $this->traitGet($nick);
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
     * Verifica si la contraseña dada es correcta. Además comprueba si es necesario
     * regenerar el hash de la contraseña, por ejemplo si php ha mejorado el algoritmo.
     *
     * @param string $value
     *
     * @return bool
     */
    public function verifyPassword($value)
    {
        if (password_verify($value, $this->password)) {
            if (password_needs_rehash($this->password, PASSWORD_DEFAULT)) {
                $this->setPassword($value);
            }

            return true;
        }

        return false;
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
        return $this->logkey === $value;
    }

    /**
     * Returns True if there is no erros on properties values.
     * Se ejecuta dentro del método save.
     *
     * @return bool
     */
    public function test()
    {
        $this->nick = trim($this->nick);

        if (!preg_match("/^[A-Z0-9_\+\.\-]{3,50}$/i", $this->nick)) {
            self::$miniLog->alert(self::$i18n->trans('invalid-user-nick', ['%nick%' => $this->nick]));

            return false;
        }

        if ($this->lastactivity === '') {
            $this->lastactivity = null;
        }

        return true;
    }
}
