<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Model;

use FacturaScripts\Dinamic\Model\Empresa as DinEmpresa;
use FacturaScripts\Dinamic\Model\Page as DinPage;

/**
 * Usuario de FacturaScripts.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class User extends Base\ModelClass
{

    use Base\ModelTrait;
    use Base\CompanyRelationTrait;
    use Base\PasswordTrait;

    const DEFAULT_LEVEL = 2;

    /**
     * true -> user is admin.
     *
     * @var bool
     */
    public $admin;

    /**
     *
     * @var string
     */
    public $codagente;

    /**
     *
     * @var string
     */
    public $codalmacen;

    /**
     *
     * @var string
     */
    public $creationdate;

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
     * Language code.
     *
     * @var string
     */
    public $langcode;

    /**
     * Last activity date.
     *
     * @var string
     */
    public $lastactivity;

    /**
     * Last IP used.
     *
     * @var string
     */
    public $lastip;

    /**
     * Indicates the level of security that the user can access.
     *
     * @var integer
     */
    public $level;

    /**
     * Session key, saved also in cookie. Regenerated when user log in.
     *
     * @var string
     */
    public $logkey;

    /**
     * Primary key. Varchar (50).
     *
     * @var string
     */
    public $nick;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->codalmacen = $this->toolBox()->appSettings()->get('default', 'codalmacen');
        $this->creationdate = \date(self::DATE_STYLE);
        $this->enabled = true;
        $this->idempresa = $this->toolBox()->appSettings()->get('default', 'idempresa', 1);
        $this->langcode = \FS_LANG;
        $this->level = self::DEFAULT_LEVEL;
    }

    /**
     * 
     * @return bool
     */
    public function delete()
    {
        if ($this->count() === 1) {
            /// prevent delete all users
            $this->toolBox()->i18nLog()->error('cant-delete-last-user');
            return false;
        }

        return parent::delete();
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
        new DinPage();
        new DinEmpresa();

        $nick = \defined('FS_INITIAL_USER') ? \FS_INITIAL_USER : 'admin';
        $pass = \defined('FS_INITIAL_PASS') ? \FS_INITIAL_PASS : 'admin';
        $email = \filter_var($this->nick, \FILTER_VALIDATE_EMAIL) ? $this->nick : '';
        $this->toolBox()->i18nLog()->notice('created-default-admin-account', ['%nick%' => $nick, '%pass%' => $pass]);
        return 'INSERT INTO ' . static::tableName() . ' (nick,password,email,admin,enabled,idempresa,codalmacen,langcode,homepage,level)'
            . " VALUES ('" . $nick . "','" . \password_hash($pass, \PASSWORD_DEFAULT) . "','" . $email
            . "',TRUE,TRUE,'1','1','" . \FS_LANG . "','Wizard','99');";
    }

    /**
     * Generates a new login key for the user. It also updates lastactivity
     * and last IP.
     *
     * @param string $ipAddress
     *
     * @return string
     */
    public function newLogkey($ipAddress)
    {
        $this->updateActivity($ipAddress);
        $this->logkey = $this->toolBox()->utils()->randomString(99);
        return $this->logkey;
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
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'users';
    }

    /**
     * Returns True if there is no errors on properties values.
     * It runs inside the save method.
     *
     * @return bool
     */
    public function test()
    {
        if ($this->lastactivity === '') {
            $this->lastactivity = null;
        }

        if ($this->admin) {
            $this->level = 99;
        } elseif ($this->level === null) {
            $this->level = 0;
        }

        $this->nick = \trim($this->nick);
        if (1 !== \preg_match("/^[A-Z0-9_@\+\.\-]{3,50}$/i", $this->nick)) {
            $this->toolBox()->i18nLog()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->nick, '%column%' => 'nick', '%min%' => '3', '%max%' => '50']
            );
            return false;
        }

        $this->email = $this->toolBox()->utils()->noHtml(\mb_strtolower($this->email, 'UTF8'));
        if ($this->email && false === \filter_var($this->email, \FILTER_VALIDATE_EMAIL)) {
            $this->toolBox()->i18nLog()->warning('not-valid-email', ['%email%' => $this->email]);
            $this->email = null;
            return false;
        }

        return $this->testPassword() && $this->testAgent() && $this->testWarehouse() && parent::test();
    }

    /**
     * Updates last ip address and last activity property.
     * 
     * @param string $ipAddress
     */
    public function updateActivity($ipAddress)
    {
        $this->lastactivity = \date(self::DATETIME_STYLE);
        $this->lastip = $ipAddress;
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
     * 
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = [])
    {
        $result = parent::saveInsert($values);
        if ($result && false === $this->admin) {
            $this->setNewRole();
        }

        return $result;
    }

    /**
     * Assigns the first role to this user.
     */
    protected function setNewRole()
    {
        $roleModel = new Role();
        foreach ($roleModel->all() as $role) {
            $roleUser = new RoleUser();
            $roleUser->codrole = $role->codrole;
            $roleUser->nick = $this->nick;
            $roleUser->save();

            /// set user homepage
            foreach ($roleUser->getRoleAccess() as $roleAccess) {
                $this->homepage = $roleAccess->pagename;
                if ('List' == \substr($this->homepage, 0, 4)) {
                    break;
                }
            }
            $this->save();
            break;
        }
    }

    /**
     * 
     * @return bool
     */
    protected function testAgent(): bool
    {
        if (empty($this->codagente)) {
            $this->codagente = null;
            return true;
        }

        $agent = new Agente();
        if (false === $agent->loadFromCode($this->codagente)) {
            $this->codagente = null;
        }

        return true;
    }

    /**
     * 
     * @return bool
     */
    protected function testWarehouse(): bool
    {
        $appSettings = $this->toolBox()->appSettings();

        if (empty($this->codalmacen)) {
            $this->codalmacen = $appSettings->get('default', 'codalmacen');
            $this->idempresa = $appSettings->get('default', 'idempresa');
            return true;
        }

        $warehouse = new Almacen();
        if (false === $warehouse->loadFromCode($this->codalmacen) || $warehouse->idempresa != $this->idempresa) {
            $this->codalmacen = $appSettings->get('default', 'codalmacen');
            $this->idempresa = $appSettings->get('default', 'idempresa');
        }

        return true;
    }
}
