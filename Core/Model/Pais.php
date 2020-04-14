<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * A country, for example Spain.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Pais extends Base\ModelClass
{

    use Base\ModelTrait;

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

    /**
     * Removed country from database.
     * 
     * @return bool
     */
    public function delete()
    {
        if ($this->isDefault()) {
            $this->toolBox()->i18nLog()->warning('cant-delete-default-country');
            return false;
        }

        return parent::delete();
    }

    /**
     * Returns True if this the default country.
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->codpais === $this->toolBox()->appSettings()->get('default', 'codpais');
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'codpais';
    }

    /**
     * Returns the name of the column that is the model's description.
     *
     * @return string
     */
    public function primaryDescriptionColumn()
    {
        return 'nombre';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'paises';
    }

    /**
     * Check the country's data, return True if they are correct.
     *
     * @return bool
     */
    public function test()
    {
        $this->codpais = \trim($this->codpais);
        if (1 !== \preg_match('/^[A-Z0-9]{1,20}$/i', $this->codpais)) {
            $this->toolBox()->i18nLog()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codpais, '%column%' => 'codpais', '%min%' => '1', '%max%' => '20']
            );
            return false;
        }

        $this->nombre = $this->toolBox()->utils()->noHtml($this->nombre);
        return parent::test();
    }
}
