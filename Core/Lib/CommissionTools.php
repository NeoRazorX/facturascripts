<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\Base\SalesDocumentLine;
use FacturaScripts\Dinamic\Model\Comision;
use FacturaScripts\Dinamic\Model\Producto;

/**
 * Class for the calculation of sales commissions
 *
 * @author Artex Trading s.a.   <jcuello@artextrading.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class CommissionTools
{

    /**
     *
     * @var Comision[]
     */
    protected $commissions;

    /**
     *
     * @var SalesDocument
     */
    protected $document;

    /**
     *
     * @param SalesDocument       $doc
     * @param SalesDocumentLine[] $lines
     */
    public function recalculate(&$doc, &$lines)
    {
        if (!property_exists($doc, 'totalcomision')) {
            return;
        }

        $this->document = $doc;
        $this->loadCommissions();

        $totalcommission = 0.0;
        foreach ($lines as $line) {
            if (!$line->suplido) {
                $totalcommission += $this->recalculateLine($line);
            }
        }

        $doc->totalcomision = round($totalcommission, (int) FS_NF0);
    }

    /**
     *
     * @param SalesDocumentLine $line
     *
     * @return float
     */
    protected function getCommission($line)
    {
        $product = $line->getProducto();
        foreach ($this->commissions as $commission) {
            if ($this->isValidCommissionForLine($line, $product, $commission)) {
                return $commission->porcentaje;
            }
        }

        return 0.0;
    }

    /**
     * Check if the commission record is applicable to the document
     *
     * @param Comision $commission
     *
     * @return bool
     */
    protected function isValidCommissionForDoc($commission): bool
    {
        if (!empty($commission->codagente) && $commission->codagente != $this->document->codagente) {
            return false;
        }

        if (!empty($commission->codcliente) && $commission->codcliente != $this->document->codcliente) {
            return false;
        }

        return true;
    }

    /**
     * Check if the commission record is applicable to the line document
     *
     * @param SalesDocumentLine $line
     * @param Producto          $product
     * @param Comision          $commission
     *
     * @return bool
     */
    protected function isValidCommissionForLine(&$line, $product, $commission): bool
    {
        if (!empty($commission->codfamilia) && $commission->codfamilia != $product->codfamilia) {
            return false;
        }

        if (!empty($commission->idproducto) && $commission->idproducto != $line->idproducto) {
            return false;
        }

        return true;
    }

    /**
     * Charge applicable commissions.
     */
    protected function loadCommissions()
    {
        $this->commissions = [];
        if (empty($this->document->codagente)) {
            return;
        }

        $commission = new Comision();
        $where = [new DataBaseWhere('idempresa', $this->document->idempresa)];
        foreach ($commission->all($where, ['prioridad' => 'DESC'], 0, 0) as $comm) {
            if ($this->isValidCommissionForDoc($comm)) {
                $this->commissions[] = $comm;
            }
        }
    }

    /**
     * Update commission sale of a document line
     *
     * @param SalesDocumentLine $line
     *
     * @return float
     */
    protected function recalculateLine(&$line)
    {
        $newValue = $this->getCommission($line);
        if ($newValue != $line->porcomision) {
            $line->porcomision = $newValue;
            $line->save();
        }

        return $line->porcomision * $line->pvptotal / 100;
    }
}
