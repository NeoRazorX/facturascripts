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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Model\Base;

use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Dinamic\Model\Proveedor;

/**
 * Description of PurchaseDocument
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class PurchaseDocument extends BusinessDocument
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

    public function getSubjectColumns()
    {
        return ['codproveedor'];
    }

    /**
     * Assign the supplier to the document.
     * 
     * @param Proveedor[] $subjects
     *
     * @return boolean
     */
    public function setSubject($subjects)
    {
        if (!isset($subjects[0]->codproveedor)) {
            return false;
        }

        $this->codproveedor = $subjects[0]->codproveedor;
        $this->nombre = $subjects[0]->razonsocial;
        $this->cifnif = $subjects[0]->cifnif;
        return true;
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
     * @return boolean
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

        return $this->setSubject([$proveedor]);
    }
}
