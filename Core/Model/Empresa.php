<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\DataSrc\Paises;
use FacturaScripts\Core\Lib\Vies;
use FacturaScripts\Core\Model\Base\EmailAndPhonesTrait;
use FacturaScripts\Core\Model\Base\FiscalNumberTrait;
use FacturaScripts\Core\Model\Base\GravatarTrait;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Validator;
use FacturaScripts\Core\Model\AttachedFile;
use FacturaScripts\Dinamic\Lib\RegimenIVA;
use FacturaScripts\Dinamic\Model\Almacen as DinAlmacen;
use FacturaScripts\Dinamic\Model\CuentaBanco as DinCuentaBanco;
use FacturaScripts\Dinamic\Model\Ejercicio as DinEjercicio;

/**
 * This class stores the main data of the company.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Empresa extends ModelClass
{
    use ModelTrait;
    use EmailAndPhonesTrait;
    use FiscalNumberTrait;
    use GravatarTrait;

    /** @var string Nombre del administrador o representante de la empresa. */
    public $administrador;

    /** @var string Apartado de correos de la empresa. */
    public $apartado;

    /** @var string Ciudad donde se encuentra la empresa. */
    public $ciudad;

    /** @var string Código del país donde se encuentra la empresa. */
    public $codpais;

    /** @var string Código postal de la empresa. */
    public $codpostal;

    /** @var string Dirección postal de la empresa. */
    public $direccion;

    /** @var string Número de fax de la empresa. */
    public $fax;

    /** @var string Fecha de alta de la empresa. */
    public $fechaalta;

    /** @var int Identificador único de la empresa. */
    public $idempresa;

    /** @var int Identificador del archivo utilizado como logotipo. */
    public $idlogo;

    /** @var string Nombre o razón social de la empresa. */
    public $nombre;

    /** @var string Nombre abreviado de la empresa. */
    public $nombrecorto;

    /** @var string Observaciones internas sobre la empresa. */
    public $observaciones;

    /** @var bool Indica si la empresa corresponde a una persona física. */
    public $personafisica;

    /** @var string Provincia donde se encuentra la empresa. */
    public $provincia;

    /** @var string Régimen de IVA aplicado por la empresa. */
    public $regimeniva;

    /** @var string Sitio web de la empresa. */
    public $web;

    public function checkVies(bool $msg = true): bool
    {
        $codiso = Paises::get($this->codpais)->codiso ?? '';
        return Vies::check($this->cifnif ?? '', $codiso, $msg) === 1;
    }

    public function clear(): void
    {
        parent::clear();
        $this->codpais = Tools::settings('default', 'codpais');
        $this->fechaalta = Tools::date();
        $this->personafisica = false;
        $this->regimeniva = RegimenIVA::defaultValue();
        $this->tipoidfiscal = Tools::settings('default', 'tipoidfiscal');
    }

    public function clearCache(): void
    {
        parent::clearCache();

        Empresas::clear();
    }

    public function delete(): bool
    {
        if ($this->isDefault()) {
            Tools::log()->warning('cant-delete-default-company');
            return false;
        }

        return parent::delete();
    }

    /**
     * Returns the bank accounts associated with the company.
     *
     * @return CuentaBanco[]
     */
    public function getBankAccounts(): array
    {
        return DinCuentaBanco::allWhereEq('idempresa', $this->idempresa);
    }

    /**
     * Returns the exercises associated with the company.
     *
     * @return Ejercicio[]
     */
    public function getExercises(): array
    {
        return DinEjercicio::allWhereEq('idempresa', $this->idempresa);
    }

    /**
     * Returns the warehouses associated with the company.
     *
     * @return Almacen[]
     */
    public function getWarehouses(): array
    {
        return DinAlmacen::allWhereEq('idempresa', $this->idempresa);
    }

    /**
     * Returns the company logo as an AttachedFile.
     * 
     * if the logo is not set, returns null.
     *
     * @return AttachedFile|null
     */
    public function getLogo(): ?AttachedFile
    {
        return $this->belongsTo(AttachedFile::class, 'idlogo');
    }

    public function install(): string
    {
        // needed dependencies
        new AttachedFile();

        $num = mt_rand(1, 9999);
        $name = Tools::config('initial_empresa', 'E-' . $num);
        $codpais = Tools::config('initial_codpais', 'ESP');
        return 'INSERT INTO ' . static::tableName() . ' (idempresa,web,codpais,direccion,administrador,cifnif,nombre,'
            . 'nombrecorto,personafisica,regimeniva) '
            . 'VALUES (1,' . self::db()->var2str('') . ',' . self::db()->var2str($codpais) . ',' . self::db()->var2str('')
            . ',' . self::db()->var2str('') . ',' . self::db()->var2str('00000014Z')
            . ',' . self::db()->var2str(Tools::textBreak($name, 100))
            . ',' . self::db()->var2str(Tools::textBreak($name, 32))
            . ',' . self::db()->var2str(false) . ',' . self::db()->var2str(RegimenIVA::defaultValue()) . ');';
    }

    /**
     * Returns True if this is the default company.
     *
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->idempresa === (int)Tools::settings('default', 'idempresa');
    }

    public static function primaryColumn(): string
    {
        return 'idempresa';
    }

    public function primaryDescriptionColumn(): string
    {
        return 'nombrecorto';
    }

    public static function tableName(): string
    {
        return 'empresas';
    }

    public function test(): bool
    {
        $this->administrador = Tools::noHtml($this->administrador);
        $this->apartado = Tools::noHtml($this->apartado);
        $this->ciudad = Tools::noHtml($this->ciudad);
        $this->codpostal = Tools::noHtml($this->codpostal);
        $this->direccion = Tools::noHtml($this->direccion);
        $this->fax = Tools::noHtml($this->fax);
        $this->nombre = Tools::noHtml($this->nombre);
        $this->nombrecorto = Tools::noHtml($this->nombrecorto);
        $this->observaciones = Tools::noHtml($this->observaciones);
        $this->provincia = Tools::noHtml($this->provincia);
        $this->web = Tools::noHtml($this->web);

        // check if the web is a valid url
        if (!empty($this->web) && false === Validator::url($this->web)) {
            Tools::log()->warning('invalid-web', ['%web%' => $this->web]);
            return false;
        }

        return parent::test() && $this->testEmailAndPhones() && $this->testFiscalNumber();
    }

    protected function createPaymentMethods(): bool
    {
        $formaPago = new FormaPago();
        $formaPago->codpago = $formaPago->newCode();
        $formaPago->descripcion = Tools::lang()->trans('default');
        $formaPago->idempresa = $this->idempresa;

        return $formaPago->save();
    }

    protected function createWarehouse(): bool
    {
        $almacen = new Almacen();
        $almacen->apartado = $this->apartado;
        $almacen->codalmacen = $almacen->newCode();
        $almacen->ciudad = $this->ciudad;
        $almacen->codpais = $this->codpais;
        $almacen->codpostal = $this->codpostal;
        $almacen->direccion = $this->direccion;
        $almacen->idempresa = $this->idempresa;
        $almacen->nombre = $this->nombrecorto ?? $this->nombre;
        $almacen->provincia = $this->provincia;
        $almacen->telefono = $this->telefono1;

        return $almacen->save();
    }

    protected function saveInsert(): bool
    {
        if (empty($this->idempresa)) {
            $this->idempresa = $this->newCode();
        }

        return parent::saveInsert() && $this->createPaymentMethods() && $this->createWarehouse();
    }
}
