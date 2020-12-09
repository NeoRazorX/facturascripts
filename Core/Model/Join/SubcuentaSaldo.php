<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model\Join;

use FacturaScripts\Core\Model\Base\JoinModel;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Auxiliary model to load a sumary of subaccount
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 *
 * @property double $debe
 * @property double $haber
 * @property double $saldo
 * @property int    $idsubcuenta
 * @property int    $mes
 * @property int    $canal
 */
class SubcuentaSaldo extends JoinModel
{

    /**
     * Reset the values of all model view properties.
     */
    public function clear()
    {
        parent::clear();
        $this->debe = 0.0;
        $this->haber = 0.0;
        $this->saldo = 0.0;
        $this->mes = 0;
        $this->canal = 0;
    }

    /**
     * List of fields or columns to select clausule.
     * 
     * @return array
     */
    protected function getFields(): array
    {
        return [
            'idsubcuenta' => 'partidas.idsubcuenta',
            'codsubcuenta' => 'partidas.codsubcuenta',
            'codejercicio' => 'asientos.codejercicio',
            'canal' => 'asientos.canal',
            'mes' => 'EXTRACT(MONTH FROM asientos.fecha)',
            'debe' => 'SUM(partidas.debe)',
            'haber' => 'SUM(partidas.haber)'
        ];
    }

    /**
     * Return Group By fields
     *
     * @return string
     */
    protected function getGroupFields(): string
    {
        return 'partidas.idsubcuenta,'
            . 'partidas.codsubcuenta,'
            . 'asientos.codejercicio,'
            . 'asientos.canal,'
            . 'EXTRACT(MONTH FROM asientos.fecha)';
    }

    /**
     * List of tables related to from clausule.
     * 
     * @return string
     */
    protected function getSQLFrom(): string
    {
        return 'partidas'
            . ' INNER JOIN asientos ON asientos.idasiento = partidas.idasiento';
    }

    /**
     * List of tables required for the execution of the view.
     * 
     * @return array
     */
    protected function getTables(): array
    {
        return [
            'asientos',
            'partidas'
        ];
    }

    /**
     * Assign the values of the $data array to the model view properties.
     *
     * @param array $data
     */
    protected function loadFromData($data)
    {
        parent::loadFromData($data);
        $this->saldo = $this->debe - $this->haber;
    }

    /**
     * Load in an array "detail" the monthly and channel balances of a sub-account
     * and return the sum of them.
     *
     * @param int   $idSubAccount
     * @param int   $channel
     * @param array $detail
     *
     * @return float
     */
    public function setSubAccountBalance($idSubAccount, $channel, &$detail): float
    {
        $result = 0;
        $where = [
            new DataBaseWhere('partidas.idsubcuenta', $idSubAccount),
            new DataBaseWhere('asientos.canal', empty($channel) ? null : $channel)
        ];
        foreach ($this->all($where, ['mes' => 'ASC']) as $values) {
            $detail[$values->mes - 1] = \round($values->saldo, (int) FS_NF0);
            $result += $values->saldo;
        }

        return \round($result, (int) FS_NF0);
    }
}
