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

class PuntoInteresCiudad extends ModelClass
{
    use ModelTrait;

    /** Nombre alternativo o alias del punto de interés. @var string */
    public $alias;

    /** Fecha y hora de creación del punto de interés. @var string */
    public $creation_date;

    /** Identificador único del punto de interés. @var int */
    public $id;

    /** Identificador de la ciudad a la que pertenece. @var int */
    public $idciudad;

    /** Nombre del último usuario que modificó el punto de interés. @var string */
    public $last_nick;

    /** Fecha y hora de la última modificación. @var string */
    public $last_update;

    /** Latitud geográfica del punto de interés. @var float */
    public $latitude;

    /** Longitud geográfica del punto de interés. @var float */
    public $longitude;

    /** Nombre del punto de interés. @var string */
    public $name;

    /** Nombre del usuario que creó el punto de interés. @var string */
    public $nick;

    /** @return Ciudad|null */
    public function getCity(): ?Ciudad
    {
        return $this->belongsTo(Ciudad::class, 'idciudad');
    }

    public function install(): string
    {
        // needed dependency
        new Ciudad();

        return parent::install();
    }

    public static function tableName(): string
    {
        return "puntos_interes_ciudades";
    }

    public function test(): bool
    {
        $this->creation_date = $this->creation_date ?? Tools::dateTime();
        $this->nick = $this->nick ?? Session::user()->nick;
        $this->alias = Tools::noHtml($this->alias);
        $this->name = Tools::noHtml($this->name);

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListPais?activetab=List'): string
    {
        if ('list' === $type && !empty($this->id())) {
            return $this->getCity()->url() . '&activetab=List' . $this->modelClassName();
        }

        return parent::url($type, $list);
    }

    protected function saveUpdate(): bool
    {
        $this->last_nick = Session::user()->nick;
        $this->last_update = Tools::dateTime();

        return parent::saveUpdate();
    }
}
