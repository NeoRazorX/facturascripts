<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\AtributoValor as DinAtributoValor;

/**
 * Un atributo para artículos.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Atributo extends ModelClass
{
    use ModelTrait;

    /** @var string */
    public $codatributo;

    /** @var string */
    public $nombre;

    /** @var int */
    public $num_selector;

    public function clear()
    {
        parent::clear();
        $this->num_selector = 0;
    }

    public function getNewValue(string $value): AtributoValor
    {
        $attValue = new DinAtributoValor();
        $attValue->codatributo = $this->codatributo;
        $attValue->valor = $value;

        return $attValue;
    }

    /**
     * @return AtributoValor[]
     */
    public function getValores(): array
    {
        $valor = new DinAtributoValor();
        $where = [new DataBaseWhere('codatributo', $this->codatributo)];
        $orderBy = ['orden' => 'ASC'];
        return $valor->all($where, $orderBy, 0, 0);
    }

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
        // escapamos el html
        $this->codatributo = Tools::noHtml($this->codatributo);
        $this->nombre = Tools::noHtml($this->nombre);

        if ($this->codatributo && 1 !== preg_match('/^[A-Z0-9_\+\.\-]{1,20}$/i', $this->codatributo)) {
            Tools::log()->error(
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
