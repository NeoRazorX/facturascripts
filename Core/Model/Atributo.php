<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Un atributo para artículos.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Atributo extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var string
     */
    public $codatributo;

    /**
     * Name of the attribute.
     *
     * @var string
     */
    public $nombre;

    public static function primaryColumn(): string
    {
        return 'codatributo';
    }

    public static function tableName(): string
    {
        return 'atributos';
    }

    public function test(): bool
    {
        $this->codatributo = $this->toolBox()->utils()->noHtml($this->codatributo);
        $this->nombre = $this->toolBox()->utils()->noHtml($this->nombre);

        if ($this->codatributo && 1 !== preg_match('/^[A-Z0-9_\+\.\-]{1,20}$/i', $this->codatributo)) {
            $this->toolBox()->i18nLog()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codatributo, '%column%' => 'codatributo', '%min%' => '1', '%max%' => '20']
            );
            return false;
        }

        return parent::test();
    }

    protected function saveInsert(array $values = []): bool
    {
        if (empty($this->codatributo)) {
            $this->codatributo = (string)$this->newCode();
        }

        return parent::saveInsert($values);
    }
}
