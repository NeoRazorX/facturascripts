<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2022 Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Dinamic\Model\Base\ModelClass;
use FacturaScripts\Dinamic\Model\Pais as DinPais;
use FacturaScripts\Dinamic\Model\Provincia as DinProvincia;

/**
 * A tax (VAT) that can be associated to tax, country, province, and.
 *
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 * @author Rafael San José Tovar        <rafael.sanjose@x-netdigital.com>
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 */
class ImpuestoZona extends ModelClass
{

    use ModelTrait;

    /**
     * Foreign key with tax table. varchar(10).
     *
     * @var string
     */
    public $codimpuesto;

    /**
     * Foreign key with tax table. varchar(10).
     *
     * @var string
     */
    public $codimpuestosel;

    /**
     * Foreign key with provincias table. varchar(10).
     *
     * @var string
     */
    public $codisopro;

    /**
     * Foreign key with country table. varchar(20).
     *
     * @var string
     */
    public $codpais;

    /**
     * Primary key autoincremental
     *
     * @var int
     */
    public $id;

    /**
     * Priority of taxt by zone.
     *
     * @var int
     */
    public $prioridad;

    /**
     *
     * @var string
     */
    protected $provincia;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->codimpuesto = $this->toolBox()->appSettings()->get('default', 'codimpuesto');
        $this->codpais = $this->toolBox()->appSettings()->get('default', 'codpais');
        $this->prioridad = 1;
    }

    /**
     * Get country data.
     *
     * @return Pais
     */
    public function getCountry()
    {
        $country = new DinPais();
        $country->loadFromCode($this->codpais);
        return $country;
    }

    /**
     * Get province data.
     *
     * @return Provincia
     */
    public function getProvince()
    {
        $province = new DinProvincia();
        $province->loadFromCode($this->codisopro);
        return $province;
    }

    /**
     * Returns the current value of the main column of the model.
     *
     * @return mixed
     */
    public static function primaryColumn(): string
    {
        return 'id';
    }

    /**
     * Get the name of the province.
     *
     * @return string|null
     */
    public function provincia(): ?string
    {
        if (false === isset($this->provincia)) {
            $this->provincia = $this->getProvince()->provincia;
        }

        return $this->provincia;
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName(): string
    {
        return 'impuestoszonas';
    }

    /**
     * Returns true if there are no errors in the values of the model properties.
     * It runs inside the save method.
     *
     * @return bool
     */
    public function test()
    {
        if (empty($this->codpais)) {
            $this->codisopro = null;
        }

        if (false === empty($this->codisopro)) {
            $province = $this->getProvince();
            if ($province->codpais !== $this->codpais) {
                $this->toolBox()->i18nLog()->warning('province-not-country');
                return false;
            }
        }

        return parent::test();
    }
}
