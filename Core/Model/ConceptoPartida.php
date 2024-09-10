<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * A predefined concept for a line item (the line of an accounting entry).
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ConceptoPartida extends Base\ModelClass
{
    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var string
     */
    public $codconcepto;

    /**
     * Concept of departure.
     *
     * @var string
     */
    public $descripcion;

    public static function primaryColumn(): string
    {
        return 'codconcepto';
    }

    public function primaryDescriptionColumn(): string
    {
        return 'codconcepto';
    }

    public static function tableName(): string
    {
        return 'conceptos_partidas';
    }

    public function test(): bool
    {
        $this->descripcion = Tools::noHtml($this->descripcion);

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListAsiento?activetab=List'): string
    {
        return parent::url($type, $list);
    }

    protected function saveInsert(array $values = []): bool
    {
        if (empty($this->codconcepto)) {
            $this->codconcepto = $this->newCode();
        }

        return parent::saveInsert($values);
    }
}
