<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2016       Joe Nilson             <joenilson at gmail.com>
 * Copyright (C) 2017-2019  Carlos García Gómez    <carlos@facturascripts.com>
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

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'codrole';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'roles';
    }

    /**
     * Returns True if there is no erros on properties values.
     * It runs inside the save method.
     *
     * @return bool
     */
    public function test()
    {
        if (!empty($this->codrole) && 1 !== \preg_match('/^[A-Z0-9_\+\.\-]{1,20}$/i', $this->codrole)) {
            $this->toolBox()->i18nLog()->warning(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codrole, '%column%' => 'codrole', '%min%' => '1', '%max%' => '20']
            );
            return false;
        }

        $this->descripcion = $this->toolBox()->utils()->noHtml($this->descripcion);
        return parent::test();
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'ListUser?activetab=List')
    {
        return parent::url($type, $list);
    }

    /**
     * 
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = [])
    {
        if (empty($this->codrole)) {
            $this->codrole = (string) $this->newCode();
        }

        return parent::saveInsert($values);
    }
}
