<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\Partida;

/**
 * Description of PartidaAsiento
 *
 * @author Carlos Garcia Gomez              <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal    <yopli2000@gmail.com>
 */
class PartidaAsiento extends JoinModel
{

    /**
     * @var Asiento
     */
    private $asiento;

    /**
     * @param array $data
     */
    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->setMasterModel(new Partida());
        $this->asiento = new Asiento();
    }

    /**
     * @return Partida
     */
    public function getPartida(): Partida
    {
        $partida = new Partida();
        $partida->loadFromCode($this->idpartida);
        return $partida;
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'List')
    {
        $this->asiento->idasiento = $this->idasiento;
        return $this->asiento->url($type, $list);
    }

    /**
     * @return array
     */
    protected function getFields(): array
    {
        return [
            'concepto' => 'partidas.concepto',
            'debe' => 'partidas.debe',
            'fecha' => 'asientos.fecha',
            'haber' => 'partidas.haber',
            'idasiento' => 'partidas.idasiento',
            'idpartida' => 'partidas.idpartida',
            'numero' => 'asientos.numero',
            'punteada' => 'partidas.punteada',
            'saldo' => 'partidas.saldo'
        ];
    }

    /**
     * @return string
     */
    protected function getSQLFrom(): string
    {
        return 'partidas LEFT JOIN asientos ON partidas.idasiento = asientos.idasiento';
    }

    /**
     * @return array
     */
    protected function getTables(): array
    {
        return ['asientos', 'partidas'];
    }
}
