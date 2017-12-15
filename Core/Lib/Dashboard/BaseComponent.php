<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib\Dashboard;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Model;

/**
 * Description of ComponentBase
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class BaseComponent
{
    const DIR_COMPONENTS = 'FacturaScripts\\Core\\Lib\\Dashboard\\';
    const SUFIX_COMPONENTS = 'Component';

    protected $randomData;

    public $component;
    public $version;
    public $location;
    public $nick;

    /**
     *
     * @param Model\DashboardData $data
     * @param string $userNick
     */
    public function __construct($data, $userNick)
    {
        $this->component = $data->component;
        $this->version = $data->version;
        $this->location = $data->location;
        $this->nick = $userNick;
    }

    protected function getDataFilter()
    {
        return [
            new DataBase\DataBaseWhere('component', $this->component),
            new DataBase\DataBaseWhere('nick', $this->nick),
            new DataBase\DataBaseWhere('displaydate', date('Y-m-d'), '>=')
        ];
    }

    protected function getDataOrderBy()
    {
        return [ 'displaydate' => 'ASC', 'id' => 'ASC' ];
    }

    public function getTemplate()
    {
        return $this->component . self::SUFIX_COMPONENTS . '.html';
    }

    public function getNumColumns()
    {
        return "col";
    }

    public function getCardClass()
    {
        return "";
    }

    protected function genetareRandomData($numRecords, $maxWord)
    {
        $this->randomData = TRUE;
        $colors = ['info', 'primary', 'warning', 'danger'];

        for ($key = 1; $key < $numRecords; $key++) {
            shuffle($colors);

            $data = [
                'color' => $colors[0],
                'description' => $this->getRandomText($maxWord)
            ];

            $this->saveData($data);
        }
        $this->randomData = FALSE;
    }

    private function getRandomText($maxWord = 20)
    {
        $words = ['lorem', 'ipsum', 'trastis', 'tus', 'turum', 'maruk', 'tartor', 'isis', 'osiris', 'morowik'];
        $txt = $words[mt_rand(0, 9)];

        $numWord = 0;
        while (mt_rand(0, 8) > 0) {
            shuffle($words);
            $txt .= $words[0] . ' ';
            ++$numWord;
            if ($numWord === $maxWord) {
                break;
            }
        }

        return $txt;
    }
}
