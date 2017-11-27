<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\DashboardCard;

/**
 * Description of Dashboard
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Dashboard extends Base\Controller
{

    /**
     * List of cards.
     * @var DashboardCard[] 
     */
    public $cursor;

    public function privateCore(&$response, $user)
    {
        parent::privateCore($response, $user);

        $dashboardCardModel = new DashboardCard();
        $this->cursor = $dashboardCardModel->all([new DataBaseWhere('nick', $user->nick)]);

        if (empty($this->cursor)) {
            $this->genetareRandomCards();
        }
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'dashboard';
        $pageData['menu'] = 'reports';
        $pageData['icon'] = 'fa-dashboard';

        return $pageData;
    }

    private function genetareRandomCards()
    {
        $colors = ['info', 'warning', 'success', 'danger', 'secondary', 'primary', 'light', 'dark'];

        for ($key = 1; $key < 29; $key++) {
            shuffle($colors);

            $newCard = new DashboardCard();
            $newCard->nick = $this->user->nick;
            $newCard->descripcion = $this->getRandomText();
            $newCard->color = $colors[0];

            if (mt_rand(0, 2) == 0) {
                $newCard->link = 'https://www.' . mt_rand(999, 99999) . '.com';
            }

            $newCard->save();
        }

        $dashboardCardModel = new DashboardCard();
        $this->cursor = $dashboardCardModel->all([new DataBaseWhere('nick', $this->user->nick)]);
    }

    private function getRandomText()
    {
        $words = ['lorem', 'ipsum', 'trastis', 'tus', 'turum', 'maruk', 'tartor', 'isis', 'osiris', 'morowik'];
        $txt = $words[mt_rand(0, 8)];

        while (mt_rand(0, 8) > 0) {
            shuffle($words);
            $txt .= $words[0] . ' ';
        }

        return $txt;
    }
}
