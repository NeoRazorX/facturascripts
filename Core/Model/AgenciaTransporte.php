<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\DataSrc\AgenciasTransporte;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Validator;

/**
 * Merchandise transport agency.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class AgenciaTransporte extends ModelClass
{
    use ModelTrait;

    /** Indica si la agencia de transporte está activa. @var bool */
    public $activo;

    /** Código identificativo de la agencia de transporte. @var string */
    public $codtrans;

    /** Nombre de la agencia de transporte. @var string */
    public $nombre;

    /** Número de teléfono de la agencia de transporte. @var string */
    public $telefono;

    /** Dirección web de la agencia de transporte. @var string */
    public $web;

    public function clearCache(): void
    {
        parent::clearCache();
        AgenciasTransporte::clear();
    }

    public static function primaryColumn(): string
    {
        return 'codtrans';
    }

    public static function tableName(): string
    {
        return 'agenciastrans';
    }

    public function test(): bool
    {
        if (!empty($this->codtrans) && 1 !== preg_match('/^[A-Z0-9_\+\.\-]{1,8}$/i', $this->codtrans)) {
            Tools::log()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codtrans, '%column%' => 'codtrans', '%min%' => '1', '%max%' => '8']
            );
            return false;
        }

        $this->nombre = Tools::noHtml($this->nombre);
        $this->telefono = Tools::noHtml($this->telefono);
        $this->web = Tools::noHtml($this->web);

        // check if the web is a valid url
        if (!empty($this->web) && false === Validator::url($this->web)) {
            Tools::log()->error('invalid-web', ['%web%' => $this->web]);
            return false;
        }

        return parent::test();
    }

    protected function saveInsert(): bool
    {
        if (empty($this->codtrans)) {
            $this->codtrans = (string)$this->newCode();
        }

        return parent::saveInsert();
    }
}
