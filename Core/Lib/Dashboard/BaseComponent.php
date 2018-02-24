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

    const DIR_MODEL = 'FacturaScripts\\Core\\Model\\';
    const DIR_COMPONENTS = 'FacturaScripts\\Core\\Lib\\Dashboard\\';
    const SUFIX_COMPONENTS = 'Component';

    /**
     * To create some random data or not.
     *
     * @var bool
     */
    protected $randomData;

    /**
     * Name of visual component.
     *
     * @var string
     */
    public $component;

    /**
     * The component version.
     *
     * @var string
     */
    public $version;

    /**
     * The location of component on screen.
     *
     * @var string
     */
    public $location;

    /**
     * Nick of the user to whom the card is addressed.
     *
     * @var string
     */
    public $nick;

    /**
     * BaseComponent constructor.
     *
     * @param Model\DashboardData $data
     * @param string              $userNick
     */
    public function __construct($data, $userNick)
    {
        $this->component = $data->component;
        $this->version = $data->version;
        $this->location = $data->location;
        $this->nick = $userNick;
    }

    /**
     * Get the default filter to obtain dashboard components.
     *
     * @return array
     */
    protected function getDataFilter()
    {
        $result = [
            new DataBase\DataBaseWhere('component', $this->component),
            new DataBase\DataBaseWhere('displaydate', date('Y-m-d'), '>='),
        ];

        if (!empty($this->nick)) {
            $result[] = new DataBase\DataBaseWhere('nick', $this->nick);
        }

        return $result;
    }

    /**
     * Get the default order by.
     *
     * @return array
     */
    protected function getDataOrderBy()
    {
        return ['displaydate' => 'ASC', 'id' => 'ASC'];
    }

    /**
     * Return the template to use for this component.
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->component . self::SUFIX_COMPONENTS . '.html.twig';
    }

    /**
     * Return the number of columns to display width this component.
     *
     * @return string
     */
    public function getNumColumns()
    {
        return 'col';
    }

    /**
     * Return the class name to render this component.
     *
     * @return string
     */
    public function getCardClass()
    {
        return '';
    }

    /**
     * Generate some random data.
     *
     * @param $numRecords
     * @param $maxWord
     */
    protected function generateRandomData($numRecords, $maxWord)
    {
        $this->randomData = true;
        $colors = ['info', 'primary', 'warning', 'danger'];

        for ($key = 1; $key < $numRecords; ++$key) {
            shuffle($colors);

            $data = [
                'color' => $colors[0],
                'description' => $this->getRandomText($maxWord),
            ];

            $this->saveData($data);
        }
        $this->randomData = false;
    }

    /**
     * Return random text to generate sample data.
     *
     * @param int $maxWord
     *
     * @return mixed|string
     */
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
