<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
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

    public function addValue(string $value): bool
    {
        return $this->getNewValue($value)->save();
    }

    public function clear(): void
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
     * @deprecated replace with getValues()
     */
    public function getValores(): array
    {
        return $this->getValues();
    }

    /**
     * @return AtributoValor[]
     */
    public function getValues(): array
    {
        $orderBy = ['orden' => 'ASC'];
        return $this->hasMany(AtributoValor::class, 'codatributo', [], $orderBy);
    }

    public function hasValue(string $value): bool
    {
        foreach ($this->getValues() as $val) {
            if ($val->valor === $value) {
                return true;
            }
        }

        return false;
    }

    public static function primaryColumn(): string
    {
        return 'codatributo';
    }

    public function removeValue(string $value): bool
    {
        foreach ($this->getValues() as $val) {
            if ($val->valor === $value) {
                return $val->delete();
            }
        }

        return false;
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

    protected function saveInsert(): bool
    {
        if (empty($this->codatributo)) {
            $this->codatributo = (string)$this->newCode();
        }

        return parent::saveInsert();
    }
}
