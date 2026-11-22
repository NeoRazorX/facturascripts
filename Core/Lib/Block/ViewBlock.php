<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\Block;

use Closure;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Template\BlockController;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\User;

/**
 * Bloque que muestra una vista de ExtendedController (formulario, listado...), con su
 * XMLView, sus widgets y su personalización. El nombre del bloque es el nombre de la vista:
 * el XMLView que se carga es la parte del nombre anterior al guion, si lo hay.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class ViewBlock extends Block
{
    const MODEL_NAMESPACE = '\\FacturaScripts\\Dinamic\\Model\\';

    /** @var Closure[] */
    private $actions = [];

    /** @var Closure|null */
    private $onLoad;

    /** @var BaseView */
    protected $view;

    /** @var Closure|null */
    private $where;

    public function __construct(string $name, BaseView $view)
    {
        parent::__construct($name);

        $user = Session::get('user');
        $view->loadPageOptions($user instanceof User ? $user : false);
        $this->view = $view;
    }

    /**
     * Registra una acción propia del bloque. Se ejecuta, antes de cargar los datos, cuando
     * la petición va dirigida a este bloque con ese nombre de acción.
     *
     * @param string $name
     * @param Closure $callback function (ViewBlock $block, BlockController $controller): void
     *
     * @return static
     */
    public function action(string $name, Closure $callback)
    {
        $this->actions[$name] = $callback;

        return $this;
    }

    /**
     * @return static
     */
    public function addButton(array $btnArray)
    {
        $this->view->addButton($btnArray);

        return $this;
    }

    public function count(): int
    {
        return (int)$this->view->count;
    }

    /**
     * @return static
     */
    public function disableColumn(string $columnName, bool $disabled = true, string $readOnly = '')
    {
        $this->view->disableColumn($columnName, $disabled, $readOnly);

        return $this;
    }

    public function getAction(string $name): ?Closure
    {
        return $this->actions[$name] ?? null;
    }

    public function hasOptions(): bool
    {
        return (bool)$this->view->settings['btnOptions'] && false === empty($this->view->getColumns());
    }

    public function icon(): string
    {
        return (string)$this->view->icon;
    }

    public function load(BlockController $controller, bool $target): void
    {
        $this->view->processFormData($controller->request(), $target ? 'load' : 'preload');

        $this->view->loadData('', $this->baseWhere($controller));

        if (null !== $this->onLoad) {
            call_user_func($this->onLoad, $this, $controller);
        }
    }

    /**
     * @return ModelClass
     */
    public function model()
    {
        return $this->view->model;
    }

    /**
     * Se ejecuta después de cargar los datos del bloque, por ejemplo para rellenar
     * los valores de un widget.
     *
     * @param Closure $callback function (ViewBlock $block, BlockController $controller): void
     *
     * @return static
     */
    public function onLoad(Closure $callback)
    {
        $this->onLoad = $callback;

        return $this;
    }

    /**
     * @return static
     */
    public function setIcon(string $icon)
    {
        $this->view->icon = $icon;

        return $this;
    }

    /**
     * @param mixed $value
     *
     * @return static
     */
    public function setSettings(string $key, $value)
    {
        $this->view->setSettings($key, $value);

        return $this;
    }

    /**
     * @param string $title clave de traducción del título
     *
     * @return static
     */
    public function setTitle(string $title)
    {
        $this->view->title = Tools::trans($title);

        return $this;
    }

    public function template(): string
    {
        return 'Block/ViewBlock.html.twig';
    }

    public function title(): string
    {
        return (string)$this->view->title;
    }

    public function view(): BaseView
    {
        return $this->view;
    }

    /**
     * Indica el filtro con el que el bloque carga sus datos.
     *
     * @param Closure $callback function (?ModelClass $record): array, que recibe el registro
     * principal de la página y devuelve un array de Where
     *
     * @return static
     */
    public function where(Closure $callback)
    {
        $this->where = $callback;

        return $this;
    }

    /**
     * Filtro del bloque para el registro principal de la página, sin búsquedas ni filtros del usuario.
     */
    protected function baseWhere(BlockController $controller): array
    {
        return null === $this->where ? [] : call_user_func($this->where, $controller->record());
    }

    protected static function modelClass(string $modelName): string
    {
        return false === strpos($modelName, '\\') ? self::MODEL_NAMESPACE . $modelName : $modelName;
    }
}
