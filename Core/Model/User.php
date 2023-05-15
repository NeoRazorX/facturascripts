<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Empresa as DinEmpresa;
use FacturaScripts\Dinamic\Model\Page as DinPage;

/**
 * Usuario de FacturaScripts.
 *
 * @author       Carlos García Gómez      <carlos@facturascripts.com>
 * @collaborator Daniel Fernández Giménez <hola@danielfg.es>
 */
class User extends Base\ModelClass
{
    use Base\ModelTrait;
    use Base\CompanyRelationTrait;
    use Base\PasswordTrait;
    use Base\GravatarTrait;

    const DEFAULT_LEVEL = 2;

    /** @var bool */
    public $admin;

    /** @var string */
    public $codagente;

    /** @var string */
    public $codalmacen;

    /** @var string */
    public $creationdate;

    /** @var string */
    public $email;

    /** @var bool */
    public $enabled;

    /** @var string */
    public $homepage;

    /** @var string */
    public $langcode;

    /** @var string */
    public $lastactivity;

    /** @var string */
    public $lastbrowser;

    /** @var string */
    public $lastip;

    /** @var integer */
    public $level;

    /** @var string */
    public $logkey;

    /** @var string */
    public $nick;

    public function addRole(?string $code): bool
    {
        if (empty($code)) {
            return false;
        }

        $roleUser = new RoleUser();
        $roleUser->codrole = $code;
        $roleUser->nick = $this->nick;
        if (false === $roleUser->save()) {
            return false;
        }

        // si el usuario no tiene página de inicio, la ponemos
        if (empty($this->homepage)) {
            foreach ($roleUser->getRoleAccess() as $roleAccess) {
                $this->homepage = $roleAccess->pagename;
                if ('List' == substr($this->homepage, 0, 4)) {
                    break;
                }
            }
            $this->save();
        }

        return true;
    }

    /**
     * Devuelve true si el usuario tiene acceso a la página $pageName. Para comprobar si el usuario
     * tiene permiso para modificar datos en la página, se debe pasar 'update' como parámetro $permission.
     *
     * @param string $pageName
     * @param string $permission
     * @return bool
     */
    public function can(string $pageName, string $permission = 'access'): bool
    {
        // si está desactivado, no puede acceder a nada
        if (false === $this->enabled) {
            return false;
        }

        // si es admin, tiene acceso completo
        if ($this->admin) {
            // comprobamos si la página existe y si el permiso a comprobar no es only-owner-data
            $page = new DinPage();
            return $page->loadFromCode($pageName) && $permission != 'only-owner-data';
        }

        // si no es admin, comprobamos si tiene acceso a la página
        foreach (RoleAccess::allFromUser($this->nick, $pageName) as $access) {
            if ($access->can($permission)) {
                return true;
            }
        }

        return false;
    }

    public function clear()
    {
        parent::clear();
        $this->admin = false;
        $this->codalmacen = $this->toolBox()->appSettings()->get('default', 'codalmacen');
        $this->creationdate = date(self::DATE_STYLE);
        $this->enabled = true;
        $this->idempresa = $this->toolBox()->appSettings()->get('default', 'idempresa', 1);
        $this->langcode = FS_LANG;
        $this->level = self::DEFAULT_LEVEL;
    }

    public function delete(): bool
    {
        if ($this->count() === 1) {
            // impide eliminar el último usuario
            $this->toolBox()->i18nLog()->error('cant-delete-last-user');
            return false;
        }

        return parent::delete();
    }

    public function getRoles(): array
    {
        $roles = [];

        $roleUser = new RoleUser();
        $where = [new DataBaseWhere('nick', $this->nick)];
        foreach ($roleUser->all($where, [], 0, 0) as $role) {
            $roles[] = $role->getRole();
        }

        return $roles;
    }

    public function install(): string
    {
        // we need this models to be checked before
        new DinPage();
        new DinEmpresa();

        $nick = defined('FS_INITIAL_USER') ? FS_INITIAL_USER : 'admin';
        $pass = defined('FS_INITIAL_PASS') ? FS_INITIAL_PASS : 'admin';
        $email = filter_var($this->nick, FILTER_VALIDATE_EMAIL) ?
            $this->nick :
            (defined('FS_INITIAL_EMAIL') ? FS_INITIAL_EMAIL : '');
        $this->toolBox()->i18nLog()->notice('created-default-admin-account', ['%nick%' => $nick, '%pass%' => $pass]);
        return 'INSERT INTO ' . static::tableName() . ' (nick,password,email,admin,enabled,idempresa,codalmacen,langcode,homepage,level)'
            . " VALUES ('" . $nick . "','" . password_hash($pass, PASSWORD_DEFAULT) . "','" . $email
            . "',TRUE,TRUE,'1','1','" . FS_LANG . "','Wizard','99');";
    }

    public function newLogkey(string $ipAddress, string $browser = ''): string
    {
        $this->updateActivity($ipAddress, $browser);
        $this->logkey = $this->toolBox()->utils()->randomString(99);
        return $this->logkey;
    }

    public static function primaryColumn(): string
    {
        return 'nick';
    }

    public static function tableName(): string
    {
        return 'users';
    }

    public function test(): bool
    {
        $this->nick = trim($this->nick);
        if (1 !== preg_match("/^[A-Z0-9_@\+\.\-]{3,50}$/i", $this->nick)) {
            $this->toolBox()->i18nLog()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->nick, '%column%' => 'nick', '%min%' => '3', '%max%' => '50']
            );
            return false;
        }

        $this->email = $this->toolBox()->utils()->noHtml(mb_strtolower($this->email ?? '', 'UTF8'));
        if ($this->email && false === filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->toolBox()->i18nLog()->warning('not-valid-email', ['%email%' => $this->email]);
            $this->email = null;
            return false;
        }

        if (empty($this->creationdate)) {
            $this->creationdate = date(self::DATE_STYLE);
        }

        if (empty($this->lastactivity)) {
            $this->lastactivity = null;
        }

        // escapamos lastbrowser y comprobamos que no excede los 200 caracteres
        $this->lastbrowser = substr($this->toolBox()->utils()->noHtml($this->lastbrowser ?? ''), 0, 200);

        // escapamos el html de lastip y comprobamos que no excede los 40 caracteres
        $this->lastip = substr($this->toolBox()->utils()->noHtml($this->lastip ?? ''), 0, 40);

        if ($this->admin) {
            $this->level = 99;
        } elseif ($this->level === null) {
            $this->level = 0;
        }

        return $this->testPassword() && $this->testAgent() && $this->testWarehouse() && parent::test();
    }

    public function updateActivity(string $ipAddress, string $browser = '')
    {
        $this->lastactivity = date(self::DATETIME_STYLE);
        $this->lastip = $ipAddress;
        $this->lastbrowser = $browser;
    }

    /**
     * Verifies the login key.
     *
     * @param string $value
     *
     * @return bool
     */
    public function verifyLogkey(string $value): bool
    {
        return $this->logkey === $value;
    }

    protected function saveInsert(array $values = []): bool
    {
        if (false === parent::saveInsert($values)) {
            return false;
        }

        // si el usuario no es admin, le asignamos el rol por defecto
        if (false === $this->admin) {
            $code = $this->toolBox()->appSettings()->get('default', 'codrole');
            $this->addRole($code);
        }

        return true;
    }

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
