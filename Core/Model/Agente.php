<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\DataSrc\Agentes;
use FacturaScripts\Core\DataSrc\Paises;
use FacturaScripts\Core\Lib\Vies;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Contacto as DinContacto;
use FacturaScripts\Dinamic\Model\Producto as DinProducto;

/**
 * Un agente es una persona física o jurídica que actúa como comercial
 * y se le puede dar una comisión.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa    <jcuello@artextrading.com>
 */
class Agente extends Base\Contact
{
    use Base\ModelTrait;
    use Base\ProductRelationTrait;

    /** @var string */
    public $cargo;

    /** @var string */
    public $codagente;

    /** @var bool */
    public $debaja;

    /** @var string */
    public $fechabaja;

    /** @var integer */
    public $idcontacto;

    public function checkVies(bool $msg = true): bool
    {
        $codiso = Paises::get($this->getContact()->codpais)->codiso ?? '';
        return Vies::check($this->cifnif ?? '', $codiso, $msg) === 1;
    }

    public function getContact(): DinContacto
    {
        $contact = new DinContacto();
        $contact->loadFromCode($this->idcontacto);
        return $contact;
    }

    public function delete(): bool
    {
        if (false === parent::delete()) {
            return false;
        }

        // limpiamos la caché
        Agentes::clear();
        return true;
    }

    public function install(): string
    {
        // cargamos las dependencias de este modelo
        new DinProducto();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'codagente';
    }

    public function primaryDescriptionColumn(): string
    {
        return 'nombre';
    }

    public function save(): bool
    {
        if (false === parent::save()) {
            return false;
        }

        // limpiamos la caché
        Agentes::clear();
        return true;
    }

    public static function tableName(): string
    {
        return 'agentes';
    }

    public function test(): bool
    {
        $this->cargo = Tools::noHtml($this->cargo);
        $this->debaja = !empty($this->fechabaja);

        if ($this->codagente && 1 !== preg_match('/^[A-Z0-9_\+\.\-]{1,10}$/i', $this->codagente)) {
            Tools::log()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codagente, '%column%' => 'codagente', '%min%' => '1', '%max%' => '10']
            );
            return false;
        }

        return parent::test() && $this->testContact();
    }

    protected function saveInsert(array $values = []): bool
    {
        if (empty($this->codagente)) {
            $this->codagente = (string)$this->newCode();
        }

        return parent::saveInsert($values);
    }

    protected function testContact(): bool
    {
        if ($this->idcontacto) {
            return true;
        }

        // creamos un contacto para este agente
        $contact = new DinContacto();
        $contact->cifnif = $this->cifnif;
        $contact->descripcion = $this->nombre;
        $contact->email = $this->email;
        $contact->nombre = $this->nombre;
        $contact->telefono1 = $this->telefono1;
        $contact->telefono2 = $this->telefono2;
        if ($contact->save()) {
            $this->idcontacto = $contact->idcontacto;
            return true;
        }

        return false;
    }
}
