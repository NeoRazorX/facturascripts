<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Tools;

/**
 * A country, for example Spain.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Pais extends ModelClass
{
    use ModelTrait;

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

    /**
     * Country name.
     *
     * @var string
     */
    public $nombre;

    public function delete(): bool
    {
        if ($this->isDefault()) {
            Tools::log()->warning('cant-delete-default-country');
            return false;
        }

        if (parent::delete()) {
            // limpiamos la caché
            Paises::clear();
            return true;
        }

        return false;
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

    public function save(): bool
    {
        if (parent::save()) {
            // limpiamos la caché
            Paises::clear();
            return true;
        }

        return false;
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

        $this->nombre = Tools::noHtml($this->nombre);

        return parent::test();
    }
}
