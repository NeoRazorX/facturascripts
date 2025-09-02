<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025  Carlos García Gómez    <carlos@facturascripts.com>
 * Copyright (C) 2016       Joe Nilson             <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
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

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\RoleAccess as DinRoleAccess;
use FacturaScripts\Dinamic\Model\RoleUser as DinRoleUser;

/**
 * Define a permission package to quickly assign users.
 *
 * @author Joe Nilson           <joenilson at gmail.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class Role extends ModelClass
{
    use ModelTrait;

    /** @var string */
    public $codrole;

    /** @var string */
    public $descripcion;

    public function addPage(string $page_name): bool
    {
        $rolePage = new DinRoleAccess();
        $rolePage->codrole = $this->codrole;
        $rolePage->pagename = $page_name;

        return $rolePage->save();
    }

    public function addUser(string $nick): bool
    {
        $roleUser = new DinRoleUser();
        $roleUser->codrole = $this->codrole;
        $roleUser->nick = $nick;

        return $roleUser->save();
    }

    public function getAccesses(): array
    {
        $order = ['pagename' => 'ASC'];
        return $this->hasMany(RoleAccess::class, 'codrole', [], $order);
    }

    public function getUsers(): array
    {
        $order = ['nick' => 'ASC'];
        return $this->hasMany(RoleUser::class, 'codrole', [], $order);
    }

    public static function primaryColumn(): string
    {
        return 'codrole';
    }

    public function removePage(string $page_name): bool
    {
        $access = new DinRoleAccess();
        $where = [
            Where::eq('codrole', $this->codrole),
            Where::eq('pagename', $page_name),
        ];
        if (!$access->loadWhere($where)) {
            Tools::log()->warning('role-page-not-found', ['%codrole%' => $this->codrole, '%pagename%' => $page_name]);
            return false;
        }

        return $access->delete();
    }

    public function removeUser(string $nick): bool
    {
        $roleUser = new DinRoleUser();
        $where = [
            Where::eq('codrole', $this->codrole),
            Where::eq('nick', $nick),
        ];
        if (!$roleUser->loadWhere($where)) {
            Tools::log()->warning('role-user-not-found', ['%codrole%' => $this->codrole, '%nick%' => $nick]);
            return false;
        }

        return $roleUser->delete();
    }

    public static function tableName(): string
    {
        return 'roles';
    }

    public function test(): bool
    {
        $this->descripcion = Tools::noHtml($this->descripcion);

        // comprobamos que el código sea correcto
        if (!empty($this->codrole) && 1 !== preg_match('/^[A-Z0-9_\+\.\-]{1,20}$/i', $this->codrole)) {
            Tools::log()->warning(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codrole, '%column%' => 'codrole', '%min%' => '1', '%max%' => '20']
            );
            return false;
        }

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListUser?activetab=List'): string
    {
        return parent::url($type, $list);
    }

    protected function saveInsert(): bool
    {
        // si no hay codrole, lo generamos
        if (empty($this->codrole)) {
            $this->codrole = (string)$this->newCode();
        }

        return parent::saveInsert();
    }
}
