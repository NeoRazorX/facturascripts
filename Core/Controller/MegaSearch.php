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
use FacturaScripts\Core\Model;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller to perform searches on the page
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class MegaSearch extends Base\Controller
{

    /**
     * This variable contains the input text as the $query parameter
     * to be used to filter the model data
     *
     * @var string|false
     */
    public $query;

    /**
     * Results by page
     *
     * @var array
     */
    public $results;

    /**
     * More sections to search in
     *
     * @var array
     */
    public $sections;

    /**
     * Runs the controller's private logic.
     *
     * @param Response                   $response
     * @param Model\User                 $user
     * @param Base\ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $this->query = mb_strtolower($this->request->request->get('query', ''), 'UTF8');
        $this->results = ['pages' => []];
        $this->sections = [];

        if ($this->query !== '') {
            $this->pageSearch();
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
        $pageData['showonmenu'] = false;

        return $pageData;
    }

    /**
     * Proceeds to search in the whole page
     */
    private function pageSearch()
    {
        $pageModel = new Model\Page();
        foreach ($pageModel->all([], [], 0, 500) as $page) {
            /// Does the page title coincide with the search $query?
            $title = mb_strtolower($this->i18n->trans($page->title), 'UTF8');
            if ($page->showonmenu && strpos($title, $this->query) !== false) {
                $this->results['pages'][] = $page;
            }

            /// Is it a ListController that could return more results?
            if ($page->showonmenu && strpos($page->name, 'List') === 0) {
                $this->sections[$page->name] = $page->url() . '?action=megasearch&query=' . $this->query;
            }
        }
    }
}
