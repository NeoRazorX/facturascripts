<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Template\JoinModel;
use FacturaScripts\Core\Where;

/**
 * Auxiliary model to load a summary of subaccount
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 *
 * @property double $debe
 * @property double $haber
 * @property double $saldo
 * @property int $idsubcuenta
 * @property int $mes
 * @property int $canal
 */
class SubcuentaSaldo extends JoinModel
{
    public function clear(): void
    {
        parent::clear();
        $this->debe = 0.0;
        $this->haber = 0.0;
        $this->saldo = 0.0;
        $this->mes = 0;
        $this->canal = 0;
    }

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

    protected function getGroupFields(): string
    {
        return 'partidas.idsubcuenta,'
            . 'partidas.codsubcuenta,'
            . 'asientos.codejercicio,'
            . 'asientos.canal,'
            . 'EXTRACT(MONTH FROM asientos.fecha)';
    }

    protected function getSQLFrom(): string
    {
        return 'partidas'
            . ' INNER JOIN asientos ON asientos.idasiento = partidas.idasiento';
    }

    protected function getTables(): array
    {
        return [
            'asientos',
            'partidas'
        ];
    }

    protected function loadFromData(array $data): void
    {
        parent::loadFromData($data);
        $this->saldo = round($this->debe - $this->haber, FS_NF0);
    }

    /**
     * Load in an array "detail" the monthly and channel balances of a sub-account
     * and return the sum of them.
     */
    public function setSubAccountBalance($idSubAccount, $channel, &$detail): float
    {
        $result = 0;
        $where = [
            Where::eq('partidas.idsubcuenta', $idSubAccount),
            Where::eq('asientos.canal', empty($channel) ? null : $channel),
        ];
        foreach (static::all($where, ['mes' => 'ASC']) as $values) {
            $detail[$values->mes - 1] = round($values->saldo, (int)FS_NF0);
            $result += $values->saldo;
        }

        return round($result, (int)FS_NF0);
    }
}
