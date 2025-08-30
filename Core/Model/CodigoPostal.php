<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Session;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;

class CodigoPostal extends ModelClass
{
    use ModelTrait;

    /** @var string */
    public $codpais;

    /** @var string */
    public $creation_date;

    /** @var int */
    public $id;

    /** @var int */
    public $idciudad;

    /** @var int */
    public $idprovincia;

    /** @var string */
    public $last_nick;

    /** @var string */
    public $last_update;

    /** @var string */
    public $nick;

    /** @var int */
    public $number;

    public function clear(): void
    {
        parent::clear();
        $this->codpais = Tools::settings('default', 'codpais', 'ESP');
    }

    public static function tableName(): string
    {
        return "codigos_postales";
    }

    public function test(): bool
    {
        $this->creation_date = $this->creation_date ?? Tools::dateTime();
        $this->nick = $this->nick ?? Session::user()->nick;

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListPais?activetab=List'): string
    {
        return parent::url($type, $list);
    }

    protected function saveUpdate(): bool
    {
        $this->last_nick = Session::user()->nick;
        $this->last_update = Tools::dateTime();

        return parent::saveUpdate();
    }
}
