<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\DataSrc\Paises;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;

/**
 * A country, for example Spain.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Pais extends ModelClass
{
    use ModelTrait;

    /** @var string Nombre alternativo o alias del país. */
    public $alias;

    /** @var string Código alfa-2 del país según la norma ISO 3166-1. */
    public $codiso;

    /** @var string Código alfa-3 del país según la norma ISO 3166-1. */
    public $codpais;

    /** @var string Fecha y hora de creación del país. */
    public $creation_date;

    /** @var string Nombre del último usuario que modificó el país. */
    public $last_nick;

    /** @var string Fecha y hora de la última modificación. */
    public $last_update;

    /** @var float Latitud geográfica de referencia del país. */
    public $latitude;

    /** @var float Longitud geográfica de referencia del país. */
    public $longitude;

    /** @var string Nombre del usuario que creó el país. */
    public $nick;

    /** @var string Nombre del país. */
    public $nombre;

    /** @var string Prefijo telefónico internacional del país. */
    public $telephone_prefix;

    public function clearCache(): void
    {
        parent::clearCache();
        Paises::clear();
    }

    public function delete(): bool
    {
        if ($this->isDefault()) {
            Tools::log()->warning('cant-delete-default-country');
            return false;
        }

        return parent::delete();
    }

    public function getProvinces(): array
    {
        $order = ['provincia' => 'ASC'];
        return $this->hasMany(Provincia::class, 'codpais', [], $order);
    }

    public function install(): string
    {
        // dependencias
        new User();

        return parent::install();
    }

    /**
     * Returns True if this the default country.
     *
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->codpais === Tools::settings('default', 'codpais');
    }

    public static function primaryColumn(): string
    {
        return 'codpais';
    }

    public function primaryDescriptionColumn(): string
    {
        return 'nombre';
    }

    public static function tableName(): string
    {
        return 'paises';
    }

    public function test(): bool
    {
        $this->codpais = Tools::noHtml($this->codpais);
        if ($this->codpais && 1 !== preg_match('/^[A-Z0-9]{1,20}$/i', $this->codpais)) {
            Tools::log()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codpais, '%column%' => 'codpais', '%min%' => '1', '%max%' => '20']
            );
            return false;
        }

        $this->creation_date = $this->creation_date ?? Tools::dateTime();
        $this->nick = $this->nick ?? Session::user()->nick;
        $this->alias = Tools::noHtml($this->alias);
        $this->telephone_prefix = Tools::noHtml($this->telephone_prefix);
        $this->nombre = Tools::noHtml($this->nombre);

        return parent::test();
    }

    protected function saveUpdate(): bool
    {
        $this->last_nick = Session::user()->nick;
        $this->last_update = Tools::dateTime();

        return parent::saveUpdate();
    }
}
