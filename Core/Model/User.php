<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\DataSrc\Users;
use FacturaScripts\Core\Lib\TwoFactorManager;
use FacturaScripts\Core\Model\Base\CompanyRelationTrait;
use FacturaScripts\Core\Model\Base\GravatarTrait;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Agente as DinAgente;
use FacturaScripts\Dinamic\Model\Empresa as DinEmpresa;
use FacturaScripts\Dinamic\Model\Page as DinPage;
use FacturaScripts\Dinamic\Model\Role as DinRole;
use FacturaScripts\Dinamic\Model\RoleAccess as DinRoleAccess;
use FacturaScripts\Dinamic\Model\RoleUser as DinRoleUser;
use FacturaScripts\Dinamic\Model\Serie as DinSerie;

/**
 * Usuario de FacturaScripts.
 *
 * @author       Carlos García Gómez      <carlos@facturascripts.com>
 * @collaborator Daniel Fernández Giménez <hola@danielfg.es>
 */
class User extends ModelClass
{
    use ModelTrait;
    use CompanyRelationTrait;
    use GravatarTrait;

    const DEFAULT_LEVEL = 2;
    const UPDATE_ACTIVITY_PERIOD = 3600;

    /** @var bool */
    public $admin;

    /** @var string */
    public $codagente;

    /** @var string */
    public $codalmacen;

    /** @var string */
    public $codserie;

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

    /** @var string */
    public $newPassword;

    /** @var string */
    public $newPassword2;

    /** @var string */
    public $password;

    /** @var bool */
    public $two_factor_enabled;

    /** @var string */
    public $two_factor_secret_key;

    public function addRole(?string $code): bool
    {
        if (empty($code)) {
            return false;
        }

        // comprobamos si el usuario ya tiene el rol
        foreach ($this->getRoles() as $role) {
            if ($role->codrole === $code) {
                return true;
            }
        }

        // comprobamos si el rol existe
        $role = new DinRole();
        if (false === $role->load($code)) {
            Tools::log()->error('role-not-found', ['%code%' => $code]);
            return false;
        }

        // añadimos el rol al usuario
        if (false === $role->addUser($this->nick)) {
            Tools::log()->error('cant-add-role-to-user', ['%nick%' => $this->nick, '%role%' => $code]);
            return false;
        }

        // si el usuario no tiene página de inicio, la ponemos
        if (empty($this->homepage)) {
            foreach ($role->getAccesses() as $access) {
                $this->homepage = $access->pagename;
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
            return $page->load($pageName) && $permission != 'only-owner-data';
        }

        // si no es admin, comprobamos si tiene acceso a la página
        foreach (DinRoleAccess::allFromUser($this->nick, $pageName) as $access) {
            if ($access->can($permission)) {
                return true;
            }
        }

        return false;
    }

    public function clear(): void
    {
        parent::clear();
        $this->admin = false;
        $this->codalmacen = Tools::settings('default', 'codalmacen');
        $this->creationdate = Tools::date();
        $this->enabled = true;
        $this->idempresa = Tools::settings('default', 'idempresa', 1);
        $this->langcode = Tools::config('lang');
        $this->level = self::DEFAULT_LEVEL;
        $this->two_factor_enabled = false;
    }

    public function clearCache(): void
    {
        parent::clearCache();

        Users::clear();
    }

    public function delete(): bool
    {
        if ($this->count() === 1) {
            // impide eliminar el último usuario
            Tools::log()->error('cant-delete-last-user');
            return false;
        }

        return parent::delete();
    }

    /**
     * Desactiva la autenticación de dos factores para el usuario.
     */
    public function disableTwoFactor(): void
    {
        $this->two_factor_enabled = false;
        $this->two_factor_secret_key = null;
    }

    /**
     * Activa la autenticación de dos factores para el usuario.
     *
     * @return string La clave secreta generada para configurar la aplicación TOTP.
     */
    public function enableTwoFactor(string $key = ''): string
    {
        if ($this->two_factor_enabled) {
            return $this->two_factor_secret_key;
        }

        $this->two_factor_enabled = true;
        $this->two_factor_secret_key = empty($key) ?
            TwoFactorManager::getSecretKey() :
            $key;

        return $this->two_factor_secret_key;
    }

    /**
     * Devuelve los roles asignados al usuario.
     *
     * @return Role[]
     */
    public function getRoles(): array
    {
        $roles = [];

        $where = [new DataBaseWhere('nick', $this->nick)];
        foreach (DinRoleUser::all($where, [], 0, 0) as $role) {
            $roles[] = $role->getRole();
        }

        return $roles;
    }

    public function getTwoFactorUrl(): string
    {
        return TwoFactorManager::getQRCodeUrl(
            $this->getCompany()->nombrecorto ?? 'FacturaScripts',
            $this->email,
            $this->two_factor_secret_key
        );
    }

    public function getTwoFactorQR(): string
    {
        return $this->two_factor_enabled && !empty($this->two_factor_secret_key) ?
            TwoFactorManager::getQRCodeImage($this->getTwoFactorUrl()) :
            '';
    }

    public function install(): string
    {
        // we need this models to be checked before
        new DinPage();
        new DinEmpresa();
        new DinSerie();

        $nick = Tools::config('initial_user', 'admin');
        $pass = Tools::config('initial_pass', 'admin');
        $email = filter_var($this->nick, FILTER_VALIDATE_EMAIL) ?
            $this->nick :
            Tools::config('initial_email', '');
        $lang = Tools::config('lang');

        Tools::log()->notice('created-default-admin-account', ['%nick%' => $nick, '%pass%' => $pass]);

        return 'INSERT INTO ' . static::tableName() . ' (nick,password,email,admin,enabled,idempresa,codalmacen,langcode,homepage,level)'
            . " VALUES ('" . $nick . "','" . password_hash($pass, PASSWORD_DEFAULT) . "','" . $email
            . "',TRUE,TRUE,'1','1','" . $lang . "','Wizard','99');";
    }

    public function newLogkey(string $ipAddress, string $browser = ''): string
    {
        $this->updateActivity($ipAddress, $browser);
        $this->logkey = Tools::randomString(99);
        return $this->logkey;
    }

    public static function primaryColumn(): string
    {
        return 'nick';
    }

    public function removeRole(?string $code): bool
    {
        if (empty($code)) {
            return false;
        }

        // comprobamos si el usuario tiene el rol
        foreach ($this->getRoles() as $role) {
            if ($role->codrole === $code) {
                // eliminamos el rol del usuario
                return $role->removeUser($this->nick);
            }
        }

        Tools::log()->error('role-not-found', ['%code%' => $code]);
        return false;
    }

    public function setPassword($value): bool
    {
        // si la contraseña tiene menos de 8 caracteres, o no tiene números o no tiene letras, devolvemos false
        if (strlen($value) < 8 || !preg_match('/[0-9]/', $value) || !preg_match('/[a-zA-Z]/', $value)) {
            return false;
        }

        $this->password = password_hash($value, PASSWORD_DEFAULT);
        $this->newLogkey($this->lastip ?? '', $this->lastbrowser ?? '');
        return true;
    }

    public static function tableName(): string
    {
        return 'users';
    }

    public function test(): bool
    {
        $this->nick = trim($this->nick ?? '');
        if (1 !== preg_match("/^[A-Z0-9_@\+\.\-]{3,50}$/i", $this->nick)) {
            Tools::log()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->nick, '%column%' => 'nick', '%min%' => '3', '%max%' => '50']
            );
            return false;
        }

        $this->email = Tools::noHtml(mb_strtolower($this->email ?? '', 'UTF8'));
        if ($this->email && false === filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            Tools::log()->warning('not-valid-email', ['%email%' => $this->email]);
            $this->email = null;
            return false;
        }

        if (empty($this->creationdate)) {
            $this->creationdate = Tools::date();
        }

        if (empty($this->lastactivity)) {
            $this->lastactivity = null;
        }

        // escapamos lastbrowser y comprobamos que no excede los 200 caracteres
        $this->lastbrowser = substr(Tools::noHtml($this->lastbrowser ?? ''), 0, 200);

        // escapamos el html de lastip y comprobamos que no excede los 40 caracteres
        $this->lastip = substr(Tools::noHtml($this->lastip ?? ''), 0, 40);

        if ($this->admin) {
            $this->level = 99;
        } elseif ($this->level === null) {
            $this->level = 0;
        }

        return $this->testPassword() && $this->testAgent() && $this->testWarehouse() && parent::test();
    }

    public function updateActivity(string $ipAddress, string $browser = ''): void
    {
        $this->lastactivity = Tools::dateTime();
        $this->lastip = $ipAddress;
        $this->lastbrowser = $browser;
    }

    public function verifyLogkey(string $value): bool
    {
        return $this->logkey === $value;
    }

    public function verifyPassword(string $value): bool
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
     * Verifica un código TOTP proporcionado por el usuario.
     *
     * @param string $code El código TOTP a verificar.
     * @return bool Verdadero si el código es válido.
     */
    public function verifyTwoFactorCode(string $code): bool
    {
        if (!$this->two_factor_enabled || empty($this->two_factor_secret_key)) {
            return false;
        }

        return TwoFactorManager::verifyCode($this->two_factor_secret_key, $code);
    }

    protected function testPassword(): bool
    {
        if (isset($this->newPassword, $this->newPassword2) && $this->newPassword !== '' && $this->newPassword2 !== '') {
            if ($this->newPassword !== $this->newPassword2) {
                Tools::log()->warning('different-passwords', ['%userNick%' => $this->nick]);
                return false;
            }

            if (false === $this->setPassword($this->newPassword)) {
                Tools::log()->warning('weak-password', ['%userNick%' => $this->nick]);
                return false;
            }
        }

        return true;
    }

    protected function saveInsert(): bool
    {
        if (false === parent::saveInsert()) {
            return false;
        }

        // si el usuario no es admin, le asignamos el rol por defecto
        if (false === $this->admin) {
            $code = Tools::settings('default', 'codrole');
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

        $agent = new DinAgente();
        if (false === $agent->load($this->codagente)) {
            $this->codagente = null;
        }

        return true;
    }

    protected function testWarehouse(): bool
    {
        if (empty($this->codalmacen)) {
            $this->codalmacen = Tools::settings('default', 'codalmacen');
            $this->idempresa = Tools::settings('default', 'idempresa');
            return true;
        }

        $warehouse = new Almacen();
        if (false === $warehouse->load($this->codalmacen) || $warehouse->idempresa != $this->idempresa) {
            $this->codalmacen = Tools::settings('default', 'codalmacen');
            $this->idempresa = Tools::settings('default', 'idempresa');
        }

        return true;
    }
}
