<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\Utils;

/**
 * A series of invoicing or accounting, to have different numbering
 * in each series.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Serie extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Primary key. Varchar (4).
     *
     * @var string
     */
    public $codserie;

    /**
     * Description of the billing series.
     *
     * @var string
     */
    public $descripcion;

    /**
     * If associated invoices are without tax True, else False.
     *
     * @var bool
     */
    public $siniva;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'series';
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'codserie';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->siniva = false;
    }

    /**
     * Returns True if is the default serie for the company.
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->codserie === AppSettings::get('default', 'codserie');
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->codserie = trim($this->codserie);
        $this->descripcion = Utils::noHtml($this->descripcion);

        if (!preg_match('/^[A-Z0-9]{1,4}$/i', $this->codserie)) {
            self::$miniLog->alert(self::$i18n->trans('invalid-column-lenght', ['%column%' => 'codserie', '%min%' => '1', '%max%' => '4']));
            return false;
        }

        if (strlen($this->descripcion) < 1 || strlen($this->descripcion) > 100) {
            self::$miniLog->alert(self::$i18n->trans('invalid-column-lenght', ['%column%' => 'descripcion', '%min%' => '1', '%max%' => '100']));
            return false;
        }

        return true;
    }
}
