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

use FacturaScripts\Core\Tools;

/**
 * A manufacturer of products.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class Fabricante extends Base\ModelClass
{
    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var string
     */
    public $codfabricante;

    /**
     * Manufacturer name.
     *
     * @var string
     */
    public $nombre;

    /**
     * Number of products
     *
     * @var int
     */
    public $numproductos;

    public function clear()
    {
        parent::clear();
        $this->numproductos = 0;
    }

    public static function primaryColumn(): string
    {
        return 'codfabricante';
    }

    public static function tableName(): string
    {
        return 'fabricantes';
    }

    public function test(): bool
    {
        $this->codfabricante = Tools::noHtml($this->codfabricante);
        $this->nombre = Tools::noHtml($this->nombre);

        if ($this->codfabricante && 1 !== preg_match('/^[A-Z0-9_\+\.\-]{1,8}$/i', $this->codfabricante)) {
            Tools::log()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codfabricante, '%column%' => 'codfabricante', '%min%' => '1', '%max%' => '8']
            );
            return false;
        }

        if (empty($this->nombre) || strlen($this->nombre) > 100) {
            Tools::log()->warning(
                'invalid-column-lenght',
                ['%column%' => 'nombre', '%min%' => '1', '%max%' => '100']
            );
            return false;
        }

        return parent::test();
    }

    protected function saveInsert(array $values = []): bool
    {
        if (empty($this->codfabricante)) {
            $this->codfabricante = $this->newCode();
        }

        return parent::saveInsert($values);
    }
}
