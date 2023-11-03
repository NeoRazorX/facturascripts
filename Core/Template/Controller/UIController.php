<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Template\Controller;

use Exception;
use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Template\UI\Component;
use FacturaScripts\Core\UI\ActionResult;
use FacturaScripts\Core\UI\Section;

abstract class UIController extends Controller
{
    /** @var Section[] */
    private $sections = [];

    abstract protected function addComponents(): void;

    public function component(string $id): Component
    {
        // primero comprobamos si es de una sección
        foreach ($this->sections as $section) {
            if ($section->name() === $id) {
                return $section;
            }
        }

        // no es el name de una sección
        // entonces obtenemos lo que haya antes del primer _ para saber a qué sección pertenece
        $pos = strpos($id, '_');
        if ($pos === false) {
            throw new Exception("Component $id not found");
        }

        $section_name = substr($id, 0, $pos);

        // el resto se lo pasamos a la función component de la sección
        return $this->section($section_name)->component(substr($id, $pos + 1));
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->setTemplate('Master/UIController');

        $this->addComponents();

        $this->runActions();
    }

    public function section(string $name): Section
    {
        foreach ($this->sections as $section) {
            if ($section->name() === $name) {
                return $section;
            }
        }

        throw new Exception("Section $name not found");
    }

    public function sections(): array
    {
        $this->sortSections();

        return $this->sections;
    }

    protected function actionResult(): ActionResult
    {
        return new ActionResult();
    }

    protected function addSection(Section $section): Section
    {
        // comprobamos que no exista ya una sección con ese nombre
        foreach ($this->sections as $sec) {
            if ($sec->name() === $section->name()) {
                throw new Exception("Section {$section->name()} already exists");
            }
        }

        $section->setPosition(count($this->sections) * 10);

        $this->sections[] = $section;
        $this->sortSections();

        return $section;
    }

    protected function removeSection(string $name): bool
    {
        foreach ($this->sections as $key => $section) {
            if ($section->name() === $name) {
                unset($this->sections[$key]);
                $this->sortSections();
                return true;
            }
        }

        return false;
    }

    protected function runActions(): void
    {
        $action_name = $this->request->get('_action');
        foreach ($this->sections() as $section) {
            foreach ($section->actions() as $action) {
                if ($action['name'] != $action_name) {
                    continue;
                }

                $result = $this->runFunction($action);

                if ($result->exit) {
                    $this->setTemplate(false);
                    return;
                } elseif ($result->stop) {
                    return;
                }
            }
        }
    }

    private function runFunction(array $action): ActionResult
    {
        // si comienza por component: buscamos el componente y ejecutamos su función
        if (substr($action['function'], 0, 10) === 'component:') {
            // el ID del componente es lo que va antes de los dos puntos en el name del action
            $pos = strpos($action['name'], ':');
            if ($pos === false) {
                throw new Exception("Component {$action['name']} not found");
            }

            $component_id = substr($action['name'], 0, $pos);
            $component = $this->component($component_id);
            $function = substr($action['function'], 10);
            return $component->{$function}();
        }

        // ejecutamos la función del controlador
        $function = $action['function'];
        $return = $this->{$function}();
        return $return instanceof ActionResult ? $return : new ActionResult();
    }

    private function sortSections(): void
    {
        usort($this->sections, function (Section $a, Section $b) {
            return $a->position() <=> $b->position();
        });
    }
}
