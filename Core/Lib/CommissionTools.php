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
namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\Base\SalesDocumentLine;
use FacturaScripts\Dinamic\Model\Comision;

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
     * @param SalesDocument       $doc
     * @param SalesDocumentLine[] $lines
     */
    public function recalculate(&$doc, &$lines)
    {
        if (!property_exists($doc, 'totalcomision')) {
            return;
        }

        $totalcommission = 0.0;
        $this->loadCommissions($doc);
        foreach ($lines as $line) {
            $totalcommission += $this->recalculateLine($line);
        }

        $doc->totalcomision = round($totalcommission, (int) FS_NF0);
    }

    /**
     * 
     * @param SalesDocumentLine $line
     *
     * @return float
     */
    protected function getCommision(&$line)
    {
        $codfamilia = $line->getProducto()->codfamilia;
        foreach ($this->commissions as $commission) {
            if (!empty($commission->codfamilia) && $commission->codfamilia != $codfamilia) {
                continue;
            }

            if (!empty($commission->idproducto) && $commission->idproducto != $line->idproducto) {
                continue;
            }

            return $commission->porcentaje;
        }

        return 0.0;
    }

    /**
     * Charge applicable commissions.
     * 
     * @param SalesDocument $doc
     */
    protected function loadCommissions(&$doc)
    {
        $this->commissions = [];
        if (empty($doc->codagente)) {
            return;
        }

        $commission = new Comision();
        $where = [new DataBaseWhere('idempresa', $doc->idempresa)];
        foreach ($commission->all($where, ['prioridad' => 'DESC'], 0, 0) as $comm) {
            if (!empty($comm->codagente) && $comm->codagente != $doc->codagente) {
                continue;
            }

            if (!empty($comm->codcliente) && $comm->codcliente != $doc->codcliente) {
                continue;
            }

            $this->commissions[] = $comm;
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
        $newValue = $this->getCommision($line);
        if ($newValue != $line->porcomision) {
            $line->porcomision = $newValue;
            $line->save();
        }

        return $line->porcomision * $line->pvptotal / 100;
    }
}
