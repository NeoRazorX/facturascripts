<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Page;
use FacturaScripts\Dinamic\Model\User;

/**
 * Controller to perform searches on the page
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class MegaSearch extends Controller
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

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'reports';
        $data['title'] = 'mega-search';
        $data['showonmenu'] = false;
        return $data;
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response $response
     * @param User $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->results = [];
        $this->sections = [];

        $query = $this->request->request->get('query', '');
        $this->query = Tools::noHtml(mb_strtolower($query, 'UTF8'));
        if ($this->query !== '') {
            $this->search();
        }
    }

    /**
     * Proceeds to search in the whole page
     */
    protected function pageSearch()
    {
        $results = [];
        $pageModel = new Page();
        $i18n = Tools::lang();
        foreach ($pageModel->all([], [], 0, 0) as $page) {
            if (!$page->showonmenu) {
                continue;
            }

            // Does the page title coincide with the search $query?
            $translation = mb_strtolower($i18n->trans($page->title ?? ''), 'UTF8');
            if (stripos($page->title, $this->query) !== false || stripos($translation, $this->query) !== false) {
                $results[] = [
                    'icon' => $page->icon,
                    'link' => $page->url(),
                    'menu' => $i18n->trans($page->menu ?? ''),
                    'submenu' => $i18n->trans($page->submenu ?? ''),
                    'title' => $i18n->trans($page->title ?? '')
                ];
            }

            // Is it a ListController that could return more results?
            if (strpos($page->name, 'List') === 0) {
                $this->sections[$page->name] = $page->url() . '?action=megasearch&query=' . $this->query;
            }
        }

        if (!empty($results)) {
            $this->results['pages'] = [
                'columns' => ['icon' => 'icon', 'menu' => 'menu', 'submenu' => 'submenu', 'title' => 'title'],
                'icon' => 'fa-solid fa-mouse-pointer',
                'title' => 'pages',
                'results' => $results
            ];
        }
    }

    protected function search()
    {
        $this->pageSearch();
    }
}
