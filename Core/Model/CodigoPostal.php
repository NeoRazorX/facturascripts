<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

    /** @var string Código del país al que pertenece el código postal. */
    public $codpais;

    /** @var string Fecha y hora de creación del código postal. */
    public $creation_date;

    /** @var int Identificador único del código postal. */
    public $id;

    /** @var int Identificador de la ciudad asociada. */
    public $idciudad;

    /** @var int Identificador de la provincia asociada. */
    public $idprovincia;

    /** @var string Nombre del último usuario que modificó el código postal. */
    public $last_nick;

    /** @var string Fecha y hora de la última modificación. */
    public $last_update;

    /** @var string Nombre del usuario que creó el código postal. */
    public $nick;

    /** @var string Número del código postal. Es texto para conservar los ceros a la izquierda y admitir letras. */
    public $number;

    public function clear(): void
    {
        parent::clear();
        $this->codpais = Tools::settings('default', 'codpais', 'ESP');
    }

    public function install(): string
    {
        // needed dependencies
        new Ciudad();

        return parent::install();
    }

    public static function tableName(): string
    {
        return "codigos_postales";
    }

    public function test(): bool
    {
        $this->creation_date = $this->creation_date ?? Tools::dateTime();
        $this->nick = $this->nick ?? Session::user()->nick;

        // el código postal es texto: quitamos espacios y html, y si queda vacío lo dejamos a null
        $number = Tools::noHtml(trim((string)$this->number));
        $this->number = '' === $number ? null : $number;

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
