<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\Utils;

/**
 * Usuario de FacturaScripts.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class User extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * true -> user is admin.
     *
     * @var bool
     */
    public $admin;

    /**
     * user's email.
     *
     * @var string
     */
    public $email;

    /**
     * true -> user enabled.
     *
     * @var bool
     */
    public $enabled;

    /**
     * Homepage.
     *
     * @var string
     */
    public $homepage;

    /**
     * Corporation identifier.
     *
     * @var int
     */
    public $idempresa;

    /**
     * Language code.
     *
     * @var string
     */
    public $langcode;

    /**
     * Last IP used.
     *
     * @var string
     */
    public $lastip;

    /**
     * Last activity date.
     *
     * @var string
     */
    public $lastactivity;

    /**
     * Session key, saved also in cookie. Regenerated when user log in.
     *
     * @var string
     */
    public $logkey;

    /**
     * New password.
     *
     * @var string
     */
    public $newPassword;

    /**
     * Repeated new password.
     *
     * @var string
     */
    public $newPassword2;

    /**
     * Primary key. Varchar (50).
     *
     * @var string
     */
    public $nick;

    /**
     * Password hashed with password_hash()
     *
     * @var string
     */
    public $password;

    /**
     * Indicates the level of security that the user can access
     *
     * @var integer
     */
    public $level;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'users';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
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
        /// we need this models to be checked before
        new Page();
        new Empresa();

        self::$miniLog->info(self::$i18n->trans('created-default-admin-account'));

        return 'INSERT INTO ' . static::tableName() . ' (nick,password,admin,enabled,idempresa,langcode,homepage,level)'
            . " VALUES ('admin','" . password_hash('admin', PASSWORD_DEFAULT)
            . "',TRUE,TRUE,'1','" . FS_LANG . "','Wizard','99');";
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->langcode = FS_LANG;
        $this->homepage = 'Dashboard';
        $this->idempresa = AppSettings::get('default', 'idempresa', 1);
        $this->enabled = true;
        $this->level = 1;
    }

    /**
     * Asigns the new password to the user.
     *
     * @param string $value
     */
    public function setPassword($value)
    {
        $this->password = password_hash($value, PASSWORD_DEFAULT);
    }

    /**
     * Verifies password. It also rehash the password if needed.
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
     * Generates a new login key for the user. It also updates lastactivity
     * ans last IP.
     *
     * @param string $ipAddress
     *
     * @return string
     */
    public function newLogkey($ipAddress)
    {
        $this->lastactivity = date('d-m-Y H:i:s');
        $this->lastip = $ipAddress;
        $this->logkey = Utils::randomString(99);

        return $this->logkey;
    }

    /**
     * Verifies the login key.
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
     * Check the null value of the fields
     */
    private function checkEmptyValues()
    {
        if ($this->lastactivity === '') {
            $this->lastactivity = null;
        }

        if ($this->level === null) {
            $this->level = 0;
        }
    }

    /**
     * Returns True if there is no errors on properties values.
     * It runs inside the save method.
     *
     * @return bool
     */
    public function test()
    {
        $this->checkEmptyValues();
        $this->nick = trim($this->nick);

        if (!preg_match("/^[A-Z0-9_\+\.\-]{3,50}$/i", $this->nick)) {
            self::$miniLog->alert(self::$i18n->trans('invalid-user-nick', ['%nick%' => $this->nick]));

            return false;
        }

        if (isset($this->newPassword, $this->newPassword2) && $this->newPassword !== '' && $this->newPassword2 !== '') {
            if ($this->newPassword !== $this->newPassword2) {
                self::$miniLog->alert(self::$i18n->trans('different-passwords', ['%userNick%' => $this->nick]));

                return false;
            }

            $this->setPassword($this->newPassword);
        }

        return true;
    }
}
