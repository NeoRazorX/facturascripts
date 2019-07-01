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
namespace FacturaScripts\Core\Model\Base;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Dinamic\Model\Divisa;
use FacturaScripts\Dinamic\Model\Proveedor;

/**
 * Description of PurchaseDocument
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class PurchaseDocument extends TransformerDocument
{

    /**
     * Supplier code for this document.
     *
     * @var string
     */
    public $codproveedor;

    /**
     * Provider's name.
     *
     * @var string
     */
    public $nombre;

    /**
     * Supplier's document number, if any.
     * May contain letters.
     *
     * @var string
     */
    public $numproveedor;

    public function clear()
    {
        parent::clear();

        /// select default currency
        $divisa = new Divisa();
        if ($divisa->loadFromCode(AppSettings::get('default', 'coddivisa'))) {
            $this->coddivisa = $divisa->coddivisa;
            $this->tasaconv = $divisa->tasaconvcompra;
        }
    }

    /**
     * 
     * @return Proveedor
     */
    public function getSubject()
    {
        $proveedor = new Proveedor();
        $proveedor->loadFromCode($this->codproveedor);
        return $proveedor;
    }

    /**
     * 
     * @return string
     */
    public function install()
    {
        /// we need to call parent first
        $result = parent::install();

        /// needed dependencies
        new Proveedor();

        return $result;
    }

    /**
     * Assign the supplier to the document.
     * 
     * @param Proveedor $subject
     *
     * @return bool
     */
    public function setSubject($subject)
    {
        if (!isset($subject->codproveedor)) {
            return false;
        }

        /// supplier model
        $this->codproveedor = $subject->codproveedor;
        $this->nombre = $subject->razonsocial;
        $this->cifnif = $subject->cifnif;

        /// commercial data
        $this->codpago = $subject->codpago ?? $this->codpago;
        $this->codserie = $subject->codserie ?? $this->codserie;
        $this->irpf = $subject->irpf() ?? $this->irpf;

        return true;
    }

    /**
     * 
     * @return string
     */
    public function subjectColumn()
    {
        return 'codproveedor';
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->nombre = Utils::noHtml($this->nombre);
        $this->numproveedor = Utils::noHtml($this->numproveedor);

        return parent::test();
    }

    /**
     * Updates subjects data in this document.
     *
     * @return bool
     */
    public function updateSubject()
    {
        if (empty($this->codproveedor)) {
            return false;
        }

        $proveedor = new Proveedor();
        if (!$proveedor->loadFromCode($this->codproveedor)) {
            return false;
        }

        return $this->setSubject($proveedor);
    }

    /**
     * 
     * @param array $fields
     */
    protected function setPreviousData(array $fields = [])
    {
        $more = ['codproveedor'];
        parent::setPreviousData(array_merge($more, $fields));
    }
}
