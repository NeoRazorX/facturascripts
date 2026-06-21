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
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
abstract class UIController extends Controller
{
    /** @var FieldComponent[] keyed by fieldname */
    private array $components = [];

    /** @var array<string, string[]> fieldname → error messages */
    private array $errors = [];

    /** Nombre del grupo activo para las siguientes llamadas a addComponent(). */
    private string $currentGroup = '__default__';

    /** @var array<string, array{alignBottom: bool, components: string[]}> */
    private array $groups = [];

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

    /**
     * Inicia un nuevo grupo de campos. Los componentes añadidos con addComponent() a
     * continuación pertenecerán a este grupo hasta que se llame a startGroup() de nuevo.
     *
     * En la plantilla cada grupo se renderiza como:
     *   <div class="col-md-12"><div class="row g-2 [align-items-end]">...</div></div>
     *
     * @param bool $alignBottom si true, añade align-items-end al row interno (útil para checkboxes)
     */
    protected function startGroup(string $name, bool $alignBottom = false): void
    {
        $this->currentGroup = $name;
        if (!isset($this->groups[$name])) {
            $this->groups[$name] = ['alignBottom' => $alignBottom, 'components' => []];
        }
    }

    /**
     * Registra un componente en el controlador y lo asigna al grupo activo.
     *
     * Lanza InvalidArgumentException si el fieldname no cumple el patrón
     * /^[a-zA-Z_][a-zA-Z0-9_]*$/ y LogicException si ya existe otro componente
     * con el mismo nombre.
     */
    protected function addComponent(FieldComponent $component): FieldComponent
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

        // assign to current group
        if (!isset($this->groups[$this->currentGroup])) {
            $this->groups[$this->currentGroup] = ['alignBottom' => false, 'components' => []];
        }
        $this->groups[$this->currentGroup]['components'][] = $fieldname;

        return $component;
    }

    /**
     * Devuelve los grupos de componentes para la plantilla Twig.
     *
     * Cada elemento es ['alignBottom' => bool, 'components' => FieldComponent[]].
     * Si no se usó startGroup(), devuelve un único grupo con todos los componentes.
     */
    public function componentGroups(): array
    {
        $result = [];
        foreach ($this->groups as $groupDef) {
            $comps = [];
            foreach ($groupDef['components'] as $fieldname) {
                if (isset($this->components[$fieldname])) {
                    $comps[$fieldname] = $this->components[$fieldname];
                }
            }
            $result[] = ['alignBottom' => $groupDef['alignBottom'], 'components' => $comps];
        }
        return $result;
    }

    /** Devuelve el componente registrado con ese fieldname, o null si no existe. */
    protected function component(string $fieldname): ?FieldComponent
    {
        return $this->components[$fieldname] ?? null;
    }

    /** Elimina el componente con ese fieldname del árbol. No lanza error si no existe. */
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

    /** Indica si hay un handler registrado para el evento dado. */
    protected function hasEventHandler(string $event): bool
    {
        return isset($this->eventHandlers[$event]);
    }

    /** Devuelve todos los componentes registrados, indexados por fieldname. Usado por Twig. */
    public function components(): array
    {
        return $this->components;
    }

    /** Devuelve el mapa completo de errores de validación: fieldname → string[]. */
    public function errors(): array
    {
        return $this->errors;
    }

    /** Indica si algún componente falló la validación en el último POST. */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /** Devuelve los mensajes de error asociados a un fieldname concreto. */
    public function errorsFor(string $fieldname): array
    {
        return $this->errors[$fieldname] ?? [];
    }

    /**
     * Punto de entrada principal del controlador.
     *
     * Flujo: createUI → (si hay _event: dispatchEvent) | (POST sin event:
     * processComponents) | (GET: populateFromModel) → modifyUI → setTemplate.
     */
    public function privateCore(&$response, $user, $permissions): void
    {
        parent::privateCore($response, $user, $permissions);

        $this->createUI();
        $this->pipe('createUI');

        // dispatch widget AJAX actions (action=widget-*) sent by WidgetSubcuenta.js etc.
        $widgetAction = $this->request->request->get('action', '');
        if (!empty($widgetAction) && str_starts_with($widgetAction, 'widget-')) {
            $this->dispatchWidgetAction($widgetAction);
            return;
        }

        $action = $this->request->queryOrInput('_event', '');

        if (!empty($action)) {
            if (false === $this->pipeFalse('execPreviousAction', $action)) {
                return;
            }
            $result = $this->dispatchEvent($action);
            if ($result !== null && $result->exit) {
                if (!empty($result->redirect)) {
                    $this->redirect($result->redirect);
                } else {
                    $this->setTemplate(false);
                }
                return;
            }
        }

        if ($this->request->isMethod('POST') && empty($action)) {
            if (false === $this->pipeFalse('execPreviousAction', 'save')) {
                return;
            }
            if ($this->processComponents()) {
                return;
            }
        } else {
            $this->populateFromModel();
        }

        $this->pipe('loadData', $this->loadModel());

        $this->modifyUI();
        $this->pipe('modifyUI');

        $this->pipeFalse('execAfterAction', $action);

        // Registrar activos JS/CSS de cada componente ANTES de que Twig evalúe
        // assetManager.get('js') en el <head>. Si se registrase dentro de renderEdit()
        // sería demasiado tarde (el <head> ya está renderizado).
        foreach ($this->components as $component) {
            $component->registerAssets();
        }

        $this->setTemplate($this->resolveTemplate());
    }

    /**
     * Devuelve el nombre de la plantilla Twig a renderizar.
     *
     * Sobreescribe en la subclase para cambiar de plantilla según el estado
     * interno (p. ej. lista vs. edición). La implementación base devuelve la
     * plantilla genérica de componentes.
     */
    protected function resolveTemplate(): string
    {
        return 'Master/ComponentController';
    }

    /**
     * Procesa todos los componentes del formulario POST.
     *
     * Por cada componente invoca processRequest(). Si hay errores de validación
     * los almacena en $errors y los inyecta en el componente para que renderEdit()
     * muestre el feedback en línea. Si todos pasan, dispara el evento 'save'.
     * Devuelve true si se ha gestionado una redirección (la llamada debe salir
     * inmediatamente), false para continuar con el renderizado normal.
     */
    private function processComponents(): bool
    {
        $model = $this->loadModel();

        foreach ($this->components as $fieldname => $component) {
            if ($component->isHidden()) {
                continue; // el campo oculto no se procesa; el modelo conserva el valor de BD
            }
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

    /**
     * En GET, rellena los valores de los componentes desde el modelo devuelto por loadModel().
     *
     * Solo copia propiedades que existan tanto en el modelo como en el árbol de componentes;
     * las propiedades sin componente correspondiente se ignoran silenciosamente.
     */
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

    /**
     * Despacha una acción de widget AJAX (action=widget-*) al componente que la reconozca.
     *
     * Itera por todos los componentes llamando a handleWidgetAction(). El primero que
     * devuelva un string no nulo gana: se envía como JSON y se suprime la plantilla.
     * Si ningún componente reconoce la acción se devuelve un array vacío.
     */
    private function dispatchWidgetAction(string $widgetAction): void
    {
        foreach ($this->components as $component) {
            $json = $component->handleWidgetAction($widgetAction, $this->request);
            if ($json !== null) {
                $this->response->headers->set('Content-Type', 'application/json');
                $this->response->setContent($json);
                $this->setTemplate(false);
                return;
            }
        }

        $this->response->headers->set('Content-Type', 'application/json');
        $this->response->setContent('[]');
        $this->setTemplate(false);
    }

    /**
     * Despacha un evento por nombre.
     *
     * Busca primero en los handlers registrados con onEvent(); si no hay ninguno,
     * intenta llamar a un método del mismo nombre en la subclase. Devuelve el
     * ActionResult retornado por el handler, o null si no hay handler o no devuelve
     * un ActionResult.
     */
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
