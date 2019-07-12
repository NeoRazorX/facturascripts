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
     *
     * @var string
     */
    public $codsubcuentarep;

    /**
     *
     * @var string
     */
    public $codsubcuentasop;

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
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->iva = 0.0;
        $this->recargo = 0.0;
    }

    /**
     * Removes tax from database.
     * 
     * @return bool
     */
    public function delete()
    {
        if ($this->isDefault()) {
            self::$miniLog->alert(self::$i18n->trans('cant-delete-default-tax'));
            return false;
        }

        return parent::delete();
    }

    /**
     * Returns True if this is the default tax.
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->codimpuesto === AppSettings::get('default', 'codimpuesto');
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
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'impuestos';
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->codimpuesto = trim($this->codimpuesto);
        if (!preg_match('/^[A-Z0-9_\+\.\-]{1,10}$/i', $this->codimpuesto)) {
            self::$miniLog->alert(self::$i18n->trans('invalid-alphanumeric-code', ['%value%' => $this->codimpuesto, '%column%' => 'codimpuesto', '%min%' => '1', '%max%' => '10']));
            return false;
        }

        $this->codsubcuentarep = empty($this->codsubcuentarep) ? null : $this->codsubcuentarep;
        $this->codsubcuentasop = empty($this->codsubcuentasop) ? null : $this->codsubcuentasop;
        $this->descripcion = Utils::noHtml($this->descripcion);
        return parent::test();
    }
}
