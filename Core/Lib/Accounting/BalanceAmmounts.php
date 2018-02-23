<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Lib\Accounting;

use FacturaScripts\Core\Model\Subcuenta;

/**
 * Description of BalanceAmmounts
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author nazca <comercial@nazcanetworks.com>
 */
class BalanceAmmounts extends AccountingBase
{

    /**
     * Model with related information.
     *
     * @var Subcuenta
     */
    private $subcuentaModel;

    /**
     * BalanceAmmounts constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->subcuentaModel = new Subcuenta();
    }

    /**
     * Generate the balance ammounts between two dates.
     *
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return array
     */
    public function generate($dateFrom, $dateTo)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;

        $results = $this->getData();
        if (empty($results)) {
            return [];
        }

        $balance = [];
        foreach ($results as $line) {
            $balance[] = $this->processLine($line);
        }

        /// every page is a table
        $pages = [$balance];
        return $pages;
    }

    /**
     * Return the appropiate data from database.
     *
     * @return array
     */
    protected function getData()
    {
        $sql = 'SELECT partida.idsubcuenta, partida.codsubcuenta, SUM(partida.debe) AS debe, SUM(partida.haber) AS haber'
            . ' FROM partidas as partida, asientos as asiento'
            . ' WHERE asiento.idasiento = partida.idasiento'
            . ' AND asiento.fecha >= ' . $this->dataBase->var2str($this->dateFrom)
            . ' AND asiento.fecha <= ' . $this->dataBase->var2str($this->dateTo)
            . ' GROUP BY idsubcuenta, codsubcuenta ORDER BY codsubcuenta ASC';

        return $this->dataBase->select($sql);
    }

    /**
     * Process the line data to use the appropiate formats.
     *
     * @param array $line
     *
     * @return array
     */
    private function processLine($line)
    {
        $saldo = (float) $line['debe'] - (float) $line['haber'];

        return [
            'subcuenta' => $line['codsubcuenta'],
            'descripcion' => $this->getDescriptionSubcuenta($line['idsubcuenta']),
            'debe' => $this->divisaTools->format($line['debe'], FS_NF0, false),
            'haber' => $this->divisaTools->format($line['haber'], FS_NF0, false),
            'saldo' => $this->divisaTools->format($saldo, FS_NF0, false),
        ];
    }

    /**
     * Gets the description of the subaccount with that ID.
     *
     * @param string $idsubcuenta
     *
     * @return string
     */
    private function getDescriptionSubcuenta($idsubcuenta)
    {
        $subcuenta = $this->subcuentaModel->get($idsubcuenta);
        if ($subcuenta !== false) {
            return $subcuenta->descripcion;
        }

        return '-';
    }
}
