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
 * Description of MegaSearch
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class MegaSearch extends Base\Controller
{

    /**
     * Esta variable contiene el texto enviado como parámetro query
     * usado para el filtrado de datos del modelo
     *
     * @var string|false
     */
    public $query;

    /**
     * Resultados por página
     *
     * @var array
     */
    public $results;

    /**
     * Más secciones donde buscar
     *
     * @var array
     */
    public $sections;

    /**
     * Ejecuta la lógica privada del controlador.
     *
     * @param Response $response
     * @param Model\User|null $user
     */
    public function privateCore(&$response, $user)
    {
        parent::privateCore($response, $user);
        $this->query = mb_strtolower($this->request->request->get('query', ''), 'UTF8');
        $this->results = ['pages' => []];
        $this->sections = [];

        if ($this->query != '') {
            $this->pageSearch();
        }
    }

    /**
     * Devuelve los datos básicos de la página
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
     * Realiza la búsqueda por página
     */
    private function pageSearch()
    {
        $pageModel = new Model\Page();
        foreach ($pageModel->all() as $page) {
            /// ¿El título de la página coincide con la búsuqeda?
            $title = mb_strtolower($this->i18n->trans($page->title), 'UTF8');
            if ($page->showonmenu && strpos($title, $this->query) !== false) {
                $this->results['pages'][] = $page;
            }

            /// ¿Es un ListController que podría devolver más resultados?
            if ($page->showonmenu && strpos($page->name, 'List') === 0) {
                $this->sections[$page->name] = [
                    'icon' => $page->icon,
                    'title' => $page->title,
                    'search' => $page->url() . '&action=json&query=' . $this->query,
                ];
            }
        }
    }
}
