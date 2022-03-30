<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2022 Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * A group of customers, which may be associated with a rate.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class GrupoClientes extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var string
     */
    public $codgrupo;

    /**
     * Accounting code.
     *
     * @var string
     */
    public $codsubcuenta;

    /**
     * Code of the associated rate, if any.
     *
     * @var string
     */
    public $codtarifa;

    /**
     * Group name.
     *
     * @var string
     */
    public $nombre;

    public function install(): string
    {
        // As there is a key outside of tariffs, we have to check that table before
        new Tarifa();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'codgrupo';
    }

    public function primaryDescriptionColumn(): string
    {
        return 'nombre';
    }

    public static function tableName(): string
    {
        return 'gruposclientes';
    }

    public function test(): bool
    {
        if (!empty($this->codgrupo) && 1 !== preg_match('/^[A-Z0-9_\+\.\-]{1,6}$/i', $this->codgrupo)) {
            $this->toolBox()->i18nLog()->warning(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codgrupo, '%column%' => 'codgrupo', '%min%' => '1', '%max%' => '6']
            );
            return false;
        }

        $this->nombre = $this->toolBox()->utils()->noHtml($this->nombre);
        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListCliente?activetab=List'): string
    {
        return parent::url($type, $list);
    }

    protected function saveInsert(array $values = []): bool
    {
        if (empty($this->codgrupo)) {
            $this->codgrupo = (string)$this->newCode();
        }

        return parent::saveInsert($values);
    }
}
