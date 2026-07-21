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

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;

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

    /** @var string Código del impuesto original que se desea sustituir. */
    public $codimpuesto;

    /** @var string Código del impuesto que se aplicará en la zona. */
    public $codimpuestosel;

    /** @var string Identificador de la provincia a la que se aplica la regla. */
    public $codisopro;

    /** @var string Código del país al que se aplica la regla. */
    public $codpais;

    /** @var string Código de la excepción de IVA que se aplicará en la zona. */
    public $excepcioniva;

    /** @var int Identificador único de la regla de impuesto por zona. */
    public $id;

    /** @var int Prioridad con la que se evalúa la regla de impuesto. */
    public $prioridad;

    /** @var string Nombre de la provincia almacenado temporalmente para las comparaciones. */
    protected $provincia;

    public function clear(): void
    {
        parent::clear();
        $this->codimpuesto = Tools::settings('default', 'codimpuesto');
        $this->codpais = Tools::settings('default', 'codpais');
        $this->prioridad = 1;
    }

    public function matchPais(?string $codpais, ?string $provincia): bool
    {
        if ($this->codpais !== null && $this->codpais != $codpais) {
            return false;
        }

        return $this->matchProvincia($provincia);
    }

    public function matchProvincia(?string $provincia): bool
    {
        if ($this->codisopro === null) {
            return true;
        }

        if ($provincia === null) {
            return false;
        }

        return 0 === strcasecmp($this->provincia(), $provincia);
    }

    public function provincia(): ?string
    {
        if (!isset($this->provincia)) {
            $provincia = new Provincia();
            $provincia->load($this->codisopro);
            $this->provincia = $provincia->provincia;
        }

        return $this->provincia;
    }

    public static function tableName(): string
    {
        return 'impuestoszonas';
    }
}
