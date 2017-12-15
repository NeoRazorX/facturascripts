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

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Model;

/**
 * Edit option for any page.
 *
 * @author Carlos García Gómez
 */
class EditPageOption extends Controller
{

    /**
     * Loads and save selected PageOption.
     * @var Model\PageOption
     */
    public $pageOption;

    /**
     * Runs the controller's private logic.
     *
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @param Model\User|null $user
     */
    public function privateCore(&$response, $user)
    {
        parent::privateCore($response, $user);

        $code = $this->request->get('code');
        $this->pageOption = new Model\PageOption();
        $this->pageOption->getForUser($code, $user->nick);

        if ($this->request->getMethod() === 'POST') {
            $this->saveData();
        }
    }

    /**
     * Data persists in the database, modifying if the record existed or inserting
     * in case the primary key does not exist.
     */
    private function saveData()
    {
        $this->pageOption->columns = json_decode($this->request->request->get('columns'), true);
        if ($this->pageOption->save()) {
            $this->miniLog->info($this->i18n->trans('data-save-ok'));
        } else {
            $this->miniLog->alert($this->i18n->trans('data-save-error'));
        }
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'page-option';
        $pagedata['menu'] = 'admin';
        $pagedata['icon'] = 'fa-wrench';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}
