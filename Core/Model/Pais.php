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

    /** @var string */
    public $alias;

    /**
     * Alpha-2 code of the country.
     * http://es.wikipedia.org/wiki/ISO_3166-1
     *
     * @var string
     */
    public $codiso;

    /**
     * Primary key. Varchar(3). Alpha-3 code of the country.
     * http://es.wikipedia.org/wiki/ISO_3166-1
     *
     * @var string
     */
    public $codpais;

    /** @var string */
    public $creation_date;

    /** @var string */
    public $last_nick;

    /** @var string */
    public $last_update;

    /** @var float */
    public $latitude;

    /** @var float */
    public $longitude;

    /** @var string */
    public $nick;

    /** @var string */
    public $nombre;

    /** @var string */
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
