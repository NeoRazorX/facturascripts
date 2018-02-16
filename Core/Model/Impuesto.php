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
 * A tax (VAT) that can be associated to articles, delivery notes lines,
 * invoices, etc.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Impuesto extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Primary key. varchar(10).
     *
     * @var string
     */
    public $codimpuesto;

    /**
     * Description of the tax.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Value of VAT.
     *
     * @var float|int
     */
    public $iva;

    /**
     * Value of the surcharge.
     *
     * @var float|int
     */
    public $recargo;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'impuestos';
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'codimpuesto';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->iva = 0.0;
        $this->recargo = 0.0;
    }

    /**
     * Returns True if is the default tax for the user.
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->codimpuesto === AppSettings::get('default', 'codimpuesto');
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->codimpuesto = trim($this->codimpuesto);
        if (empty($this->codimpuesto) || strlen($this->codimpuesto) > 10) {
            self::$miniLog->alert(self::$i18n->trans('not-valid-tax-code-length'));
            return false;
        }

        $this->descripcion = Utils::noHtml($this->descripcion);
        if (empty($this->descripcion) || strlen($this->descripcion) > 50) {
            self::$miniLog->alert(self::$i18n->trans('not-valid-description-tax'));
            return false;
        }

        return true;
    }
}
