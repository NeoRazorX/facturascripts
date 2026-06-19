<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Component;

use FacturaScripts\Core\Base\Controller;

/**
 * Controlador base para páginas construidas íntegramente con el sistema de componentes.
 *
 * Las subclases declaran su formulario en createUI() usando addComponent() y onEvent().
 * El ciclo de vida es: createUI → (POST: processComponents → evento save) | (GET:
 * populateFromModel) → modifyUI → renderizado. Si processComponents encuentra errores
 * de validación el formulario se vuelve a renderizar con feedback en línea; en caso de
 * éxito se despacha el evento 'save' para que la subclase persista los datos y,
 * opcionalmente, redirija.
 *
 * Sobreescribe resolveTemplate() para devolver una plantilla Twig diferente según el
 * estado interno del controlador (por ejemplo, modo lista vs. modo edición).
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
abstract class UIController extends Controller
{
    /** @var BaseComponent[] keyed by fieldname */
    private array $components = [];

    /** @var array<string, string[]> fieldname → error messages */
    private array $errors = [];

    /** @var array<string, callable> event name → controller method */
    private array $eventHandlers = [];

    /**
     * Construye el árbol de componentes. Se invoca una vez al inicio de cada petición,
     * antes de cualquier procesamiento o renderizado. Registra los componentes con
     * addComponent() y los manejadores de eventos con onEvent() aquí.
     */
    abstract protected function createUI(): void;

    /**
     * Se invoca tras el procesamiento o la población y antes de resolver la plantilla.
     * Sobreescribe este método para ajustar el árbol de componentes según el estado
     * procesado (por ejemplo, ocultar un campo una vez que su valor ha sido confirmado).
     */
    protected function modifyUI(): void
    {
    }

    /**
     * Devuelve la instancia del modelo cuyas propiedades se mapearán a los valores de
     * los componentes en GET (populateFromModel) y se actualizarán en POST (processComponents).
     * Sobreescribe y almacena en caché el resultado para evitar consultas redundantes a la BD;
     * la implementación base devuelve null, lo que significa que no se realiza ningún mapeo.
     */
    protected function loadModel(): ?object
    {
        return null;
    }

    protected function addComponent(BaseComponent $component): BaseComponent
    {
        $fieldname = $component->fieldname();

        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $fieldname)) {
            throw new \InvalidArgumentException(
                "Invalid component fieldname '{$fieldname}': must start with a letter or underscore and contain only alphanumeric characters and underscores."
            );
        }

        if (isset($this->components[$fieldname])) {
            throw new \LogicException(
                "Duplicate component fieldname '{$fieldname}': a component with this name is already registered."
            );
        }

        $this->components[$fieldname] = $component;
        return $component;
    }

    protected function component(string $fieldname): ?BaseComponent
    {
        return $this->components[$fieldname] ?? null;
    }

    protected function removeComponent(string $fieldname): void
    {
        unset($this->components[$fieldname]);
    }

    /**
     * Registra un callable para un evento con nombre.
     *
     * El evento 'save' se dispara automáticamente tras superar la validación de todos
     * los componentes. Cualquier otro nombre puede activarse enviando _event=<nombre>
     * por POST. El manejador no recibe argumentos y debe devolver un ActionResult
     * (o null para continuar el renderizado con normalidad).
     */
    protected function onEvent(string $event, callable $handler): void
    {
        $this->eventHandlers[$event] = $handler;
    }

    public function components(): array
    {
        return $this->components;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function errorsFor(string $fieldname): array
    {
        return $this->errors[$fieldname] ?? [];
    }

    public function privateCore(&$response, $user, $permissions): void
    {
        parent::privateCore($response, $user, $permissions);

        $this->createUI();

        $event = $this->request->get('_event');
        if (!empty($event)) {
            $result = $this->dispatchEvent($event);
            if ($result !== null && $result->exit) {
                if (!empty($result->redirect)) {
                    $this->redirect($result->redirect);
                } else {
                    $this->setTemplate(false);
                }
                return;
            }
        }

        if ($this->request->isMethod('POST') && empty($event)) {
            if ($this->processComponents()) {
                return; // redirección gestionada dentro de processComponents
            }
        } else {
            $this->populateFromModel();
        }

        $this->modifyUI();

        $this->setTemplate($this->resolveTemplate());
    }

    protected function resolveTemplate(): string
    {
        return 'Master/ComponentController';
    }

    private function processComponents(): bool
    {
        $model = $this->loadModel();

        foreach ($this->components as $fieldname => $component) {
            $result = $component->processRequest($this->request, $model);
            if (!$result['success']) {
                $this->errors[$fieldname] = $result['errors'];
                $component->setValidationErrors($result['errors']);
            }
        }

        if (empty($this->errors)) {
            $result = $this->dispatchEvent('save');
            if ($result !== null && $result->exit) {
                if (!empty($result->redirect)) {
                    $this->redirect($result->redirect);
                } else {
                    $this->setTemplate(false);
                }
                return true;
            }
        }

        return false;
    }

    private function populateFromModel(): void
    {
        $model = $this->loadModel();
        if ($model === null) {
            return;
        }

        foreach ($this->components as $fieldname => $component) {
            if (property_exists($model, $fieldname)) {
                $component->setValue($model->{$fieldname});
            }
        }
    }

    private function dispatchEvent(string $name): ?ActionResult
    {
        if (isset($this->eventHandlers[$name])) {
            $result = ($this->eventHandlers[$name])();
            return $result instanceof ActionResult ? $result : null;
        }

        if (method_exists($this, $name)) {
            $result = $this->{$name}();
            return $result instanceof ActionResult ? $result : null;
        }

        return null;
    }
}
