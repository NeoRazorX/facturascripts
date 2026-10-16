<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Http;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Page;
use FacturaScripts\Dinamic\Model\User;

/**
 * Controlador para realizar búsquedas globales en la aplicación.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class MegaSearch extends Controller
{
    /** URL por defecto del buscador de documentación. Los plugins pueden cambiarla con la extensión docsSearchUrl. */
    const DOCS_SEARCH_URL = 'https://facturascripts.com/MegaSearchDocs';

    /** Número máximo de resultados de documentación a mostrar. */
    const DOCS_SEARCH_LIMIT = 20;

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
     * Convierte la respuesta del buscador de documentación en una sección del megabuscador.
     *
     * @param mixed $data
     * @return array
     */
    public function docsSearchResults($data): array
    {
        if (!is_array($data) || !isset($data['results']) || !is_array($data['results'])) {
            return [];
        }

        $results = [];
        foreach ($data['results'] as $item) {
            if (!is_array($item) || !is_string($item['url'] ?? null) || !is_string($item['title'] ?? null)) {
                continue;
            }

            // solo aceptamos enlaces http o https
            if (preg_match('#^https?://#i', $item['url']) !== 1 || filter_var($item['url'], FILTER_VALIDATE_URL) === false) {
                continue;
            }

            $description = is_string($item['description'] ?? null) ? $item['description'] : '';
            $results[] = [
                'url' => $item['url'],
                'title' => Tools::noHtml($item['title']),
                'description' => Tools::noHtml(Tools::textBreak(strip_tags($description), 120))
            ];

            if (count($results) >= self::DOCS_SEARCH_LIMIT) {
                break;
            }
        }

        if (empty($results)) {
            return [];
        }

        return [
            'docs' => [
                'columns' => [
                    'title' => Tools::trans('title'),
                    'description' => Tools::trans('description')
                ],
                'icon' => 'fa-solid fa-book',
                'title' => Tools::trans('documentation'),
                'results' => $results
            ]
        ];
    }

    /**
     * Devuelve la URL del buscador de documentación.
     *
     * @return string
     */
    public function docsSearchUrl(): string
    {
        $url = $this->pipe('docsSearchUrl');
        return is_string($url) && $url !== '' ? $url : static::DOCS_SEARCH_URL;
    }

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

        $query = $this->request->queryOrInput('query', '');
        $this->query = Tools::noHtml(mb_strtolower($query, 'UTF8'));

        if ($this->request->query('action') === 'docs') {
            $this->docsSearchAction();
            return;
        }

        if ($this->query !== '') {
            $this->search();
        }
    }

    /**
     * Busca la consulta en la documentación remota y devuelve los resultados en JSON.
     */
    protected function docsSearchAction(): void
    {
        $this->setTemplate(false);

        if (mb_strlen($this->query) < 3 || mb_strlen($this->query) > 200) {
            $this->response->json([]);
            return;
        }

        $url = $this->docsSearchUrl();
        $lang = Tools::lang()->getLang();
        $cacheKey = 'megasearch-docs-' . md5($url . '|' . $lang . '|' . $this->query);
        $json = Cache::get($cacheKey);
        if (empty($json) || !is_array($json)) {
            $request = Http::get($url, ['query' => $this->query, 'lang' => $lang])->setTimeout(5);
            $json = $request->ok() ? $this->docsSearchResults($request->json()) : [];

            // solo guardamos en caché las respuestas con resultados
            if (!empty($json)) {
                Cache::set($cacheKey, $json);
            }
        }

        $this->response->json($json);
    }

    /**
     * Proceeds to search in the whole page
     */
    protected function pageSearch(): void
    {
        $results = [];
        foreach (Page::all() as $page) {
            if (!$page->showonmenu) {
                continue;
            }

            // Does the page title coincide with the search $query?
            $translation = mb_strtolower(Tools::trans($page->title ?? ''), 'UTF8');
            if (stripos($page->title, $this->query) !== false || stripos($translation, $this->query) !== false) {
                $results[] = [
                    'icon' => $page->icon,
                    'link' => $page->url(),
                    'menu' => Tools::trans($page->menu ?? ''),
                    'submenu' => Tools::trans($page->submenu ?? ''),
                    'title' => Tools::trans($page->title ?? '')
                ];
            }

            // Is it a ListController that could return more results?
            if (strpos($page->name, 'List') === 0) {
                $this->sections[$page->name] = $page->url() . '?action=megasearch&query=' . urlencode($this->query);
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

    protected function search(): void
    {
        $this->pageSearch();

        // buscamos también en la documentación
        if (mb_strlen($this->query) >= 3) {
            $this->sections['docs'] = $this->url() . '?action=docs&query=' . urlencode($this->query);
        }
    }
}
