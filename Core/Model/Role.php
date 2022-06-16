<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2022  Carlos García Gómez    <carlos@facturascripts.com>
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

/**
 * Define a permission package to quickly assign users.
 *
 * @author Joe Nilson           <joenilson at gmail.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class Role extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Role code.
     *
     * @var string
     */
    public $codrole;

    /**
     * Description of the role.
     *
     * @var string
     */
    public $descripcion;

    public static function primaryColumn(): string
    {
        return 'codrole';
    }

    public static function tableName(): string
    {
        return 'roles';
    }

    public function test(): bool
    {
        if (!empty($this->codrole) && 1 !== preg_match('/^[A-Z0-9_\+\.\-]{1,20}$/i', $this->codrole)) {
            $this->toolBox()->i18nLog()->warning(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codrole, '%column%' => 'codrole', '%min%' => '1', '%max%' => '20']
            );
            return false;
        }

        $this->descripcion = $this->toolBox()->utils()->noHtml($this->descripcion);
        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListUser?activetab=List'): string
    {
        return parent::url($type, $list);
    }

    protected function saveInsert(array $values = []): bool
    {
        if (empty($this->codrole)) {
            $this->codrole = (string)$this->newCode();
        }

        return parent::saveInsert($values);
    }
}
