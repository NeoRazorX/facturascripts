<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018    Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model\Base;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Model\Impuesto;

/**
 * Description of Product
 *
 * @author carlos
 */
abstract class Product extends ModelClass
{

    /**
     * Barcode. Maximum 20 characters.
     *
     * @var string
     */
    public $codbarras;

    /**
     * Tax identifier of the tax assigned.
     *
     * @var string
     */
    public $codimpuesto;

    /**
     * Description of the product.
     *
     * @var string
     */
    public $descripcion;

    /**
     * List of Tax.
     *
     * @var Impuesto[]
     */
    private static $impuestos;

    /**
     * VAT% of the assigned tax.
     *
     * @var float|int
     */
    protected $iva;

    /**
     * True -> do not control the stock.
     * Activating it implies putting True $controlstock;
     *
     * @var bool
     */
    public $nostock;

    /**
     * Partnumber of the product. Maximum 40 characters.
     *
     * @var string
     */
    public $partnumber;

    /**
     * Product SKU. Maximum 30 characters.
     *
     * @var string
     */
    public $referencia;

    /**
     * Physical stock.
     *
     * @var float|int
     */
    public $stockfis;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->codimpuesto = AppSettings::get('default', 'codimpuesto');
        $this->nostock = false;
        $this->stockfis = 0.0;
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->codbarras = Utils::noHtml($this->codbarras);
        $this->descripcion = Utils::noHtml($this->descripcion);
        $this->partnumber = Utils::noHtml($this->partnumber);
        $this->referencia = Utils::noHtml($this->referencia);
        
        if ($this->nostock) {
            $this->stockfis = 0.0;
        }
        
        return true;
    }

    /**
     * Returns the tax on the item.
     *
     * @return bool|Impuesto
     */
    public function getImpuesto()
    {
        $imp = new Impuesto();

        return $imp->get($this->codimpuesto);
    }

    /**
     * Returns the VAT% of the item.
     * If $reload is True, check back instead of using the loaded data.
     *
     * @param bool $reload
     *
     * @return float|null
     */
    public function getIva($reload = false)
    {
        if ($reload) {
            $this->iva = null;
        }

        if (!isset(self::$impuestos)) {
            self::$impuestos = [];
            $impuestoModel = new Impuesto();
            foreach ($impuestoModel->all() as $imp) {
                self::$impuestos[$imp->codimpuesto] = $imp;
            }
        }

        if ($this->iva === null) {
            $this->iva = 0;

            if ($this->codimpuesto !== null && isset(self::$impuestos[$this->codimpuesto])) {
                $this->iva = self::$impuestos[$this->codimpuesto]->iva;
            }
        }

        return $this->iva;
    }

    /**
     * Returns the name of the column that describes the model, such as name, description...
     *
     * @return string
     */
    public function primaryDescriptionColumn()
    {
        return 'referencia';
    }

    /**
     * Change the tax associated with the item.
     *
     * @param string $codimpuesto
     */
    public function setImpuesto($codimpuesto)
    {
        if ($codimpuesto !== $this->codimpuesto) {
            $this->codimpuesto = $codimpuesto;
            $this->iva = null;

            if (!isset(self::$impuestos)) {
                self::$impuestos = [];
                $impuestoModel = new Impuesto();
                foreach ($impuestoModel->all() as $imp) {
                    self::$impuestos[$imp->codimpuesto] = $imp;
                }
            }
        }
    }
}
