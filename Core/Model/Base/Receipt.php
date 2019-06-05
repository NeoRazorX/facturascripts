<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model\Base;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\Utils;

/**
 * Description of Receipt
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
abstract class Receipt extends ModelClass
{

    /**
     *
     * @var string
     */
    public $coddivisa;

    /**
     *
     * @var string
     */
    public $fecha;

    /**
     *
     * @var string
     */
    public $fechapago;

    /**
     *
     * @var string
     */
    public $fechav;

    /**
     *
     * @var int
     */
    public $idempresa;

    /**
     *
     * @var int
     */
    public $idfactura;

    /**
     *
     * @var int
     */
    public $idrecibo;

    /**
     *
     * @var float
     */
    public $importe;

    /**
     *
     * @var float
     */
    public $liquidado;

    /**
     *
     * @var string
     */
    public $nick;

    /**
     *
     * @var string
     */
    public $observaciones;

    /**
     *
     * @var bool
     */
    public $pagado;

    public function clear()
    {
        parent::clear();
        $this->coddivisa = AppSettings::get('default', 'coddivisa');
        $this->fecha = date('d-m-Y');
        $this->fechav = date('d-m-Y');
        $this->importe = 0.0;
        $this->liquidado = 0.0;
        $this->pagado = false;
    }

    /**
     * 
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idrecibo';
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        $this->observaciones = Utils::noHtml($this->observaciones);
        return parent::test();
    }
}
