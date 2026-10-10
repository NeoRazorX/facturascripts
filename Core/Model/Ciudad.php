<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Ciudad o población. Se guarda en la tabla `ciudades` y pertenece a una `Provincia` a
 * través de `idprovincia`, completando así la jerarquía geográfica país - provincia -
 * ciudad que utiliza el resto de la aplicación.
 *
 * Además del nombre admite un alias, un código geográfico (`codeid`) y las coordenadas de
 * latitud y longitud, y puede tener puntos de interés asociados (`PuntoInteresCiudad`).
 * Se registra automáticamente quién creó el registro y quién lo modificó por última vez,
 * junto con sus fechas. Su mantenimiento se realiza desde la ficha del país.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Frank Aguirre        <faguirre@soenac.com>
 */
class Ciudad extends ModelClass
{
    use ModelTrait;

    /** @var string Nombre alternativo o alias de la ciudad. */
    public $alias;

    /** @var string Fecha y hora de creación de la ciudad. */
    public $creation_date;

    /** @var string Nombre de la ciudad. */
    public $ciudad;

    /** @var string Código geográfico identificativo de la ciudad. */
    public $codeid;

    /** @var int Identificador único de la ciudad. */
    public $idciudad;

    /** @var int Identificador de la provincia a la que pertenece la ciudad. */
    public $idprovincia;

    /** @var string Nombre del último usuario que modificó la ciudad. */
    public $last_nick;

    /** @var string Fecha y hora de la última modificación. */
    public $last_update;

    /** @var float Latitud geográfica de la ciudad. */
    public $latitude;

    /** @var float Longitud geográfica de la ciudad. */
    public $longitude;

    /** @var string Nombre del usuario que creó la ciudad. */
    public $nick;

    /** @return Provincia|null */
    public function getProvince(): ?Provincia
    {
        return $this->belongsTo(Provincia::class, 'idprovincia');
    }

    /** @return PuntoInteresCiudad[] */
    public function getPuntosInteres(): array
    {
        return $this->hasMany(PuntoInteresCiudad::class, 'idciudad');
    }

    public function install(): string
    {
        // needed dependency
        new Provincia();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'idciudad';
    }

    public static function tableName(): string
    {
        return 'ciudades';
    }

    public function test(): bool
    {
        $this->creation_date = $this->creation_date ?? Tools::dateTime();
        $this->nick = $this->nick ?? Session::user()->nick;
        $this->alias = Tools::noHtml($this->alias);
        $this->ciudad = Tools::noHtml($this->ciudad);

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListPais?activetab=List'): string
    {
        if ('list' === $type && !empty($this->id())) {
            return $this->getProvince()->url() . '&activetab=List' . $this->modelClassName();
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
