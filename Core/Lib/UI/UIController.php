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

namespace FacturaScripts\Core\Lib\UI;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Lib\AssetManager;
use FacturaScripts\Core\Lib\UI\Contract\HandlesQueries;
use FacturaScripts\Core\Lib\UI\Event\UIEvent;
use FacturaScripts\Core\Lib\UI\Event\UIResponse;
use FacturaScripts\Core\Tools;

/**
 * Controlador base para páginas construidas con el sistema de componentes UI.
 *
 * Las subclases declaran su interfaz en buildUI($page) componiendo forms, tabs,
 * grupos y campos. El árbol es stateless: se reconstruye en cada petición.
 *
 * Enrutado de cada petición:
 *  1. ?_ui_query=accion&_ui_target=path → consulta de datos de un componente
 *     (select2 remoto, autocomplete…), responde JSON.
 *  2. POST con _ui_event='{form}:{evento}' → hidrata SOLO ese form, valida si
 *     procede, ejecuta el handler y responde: envelope JSON (AJAX) o render
 *     completo/redirect (sin JS).
 *  3. GET → fill() de todos los forms desde sus modelos y render completo.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
abstract class UIController extends Controller
{
    protected UIPage $ui;

    /** Construye el árbol de componentes. Se invoca en cada petición. */
    abstract protected function buildUI(UIPage $page): void;

    public function privateCore(&$response, $user, $permissions): void
    {
        parent::privateCore($response, $user, $permissions);

        $this->ui = new UIPage();
        $this->buildUI($this->ui);
        $this->pipe('buildUI', $this->ui);
        $this->ui->assertValid();

        // consulta de datos de un componente
        $queryAction = $this->request->queryOrInput('_ui_query', '');
        if (!empty($queryAction)) {
            $this->handleComponentQuery($queryAction);
            return;
        }

        // evento de form o de página
        $eventId = $this->request->request->get('_ui_event', '');
        if ($this->request->isMethod('POST') && !empty($eventId)) {
            $this->handleEvent($eventId);
            return;
        }

        // GET: rellenar todos los forms desde sus modelos vinculados
        foreach ($this->ui->forms() as $form) {
            $form->fill();
        }
        $this->pipe('loadData', $this->ui);

        $this->renderPage();
    }

    /** Devuelve la página UI. Útil en handlers para localizar componentes. */
    public function ui(): UIPage
    {
        return $this->ui;
    }

    // ------------------------------------------------------------------
    // Eventos
    // ------------------------------------------------------------------

    protected function handleEvent(string $eventId): void
    {
        if (false === $this->pipeFalse('execPreviousAction', $eventId)) {
            return;
        }

        $response = new UIResponse();

        if (false === $this->validateFormToken()) {
            // validateFormToken ya deja el warning en el log
            $response->setOk(false);
            $this->finishEvent($response);
            return;
        }

        $parts = explode(':', $eventId, 2);
        if (count($parts) !== 2) {
            Tools::log()->warning('invalid-request');
            $response->setOk(false);
            $this->finishEvent($response);
            return;
        }
        [$scope, $eventName] = $parts;

        $form = null;
        $handler = null;
        $validate = false;

        if ($scope === UIPage::EVENT_SCOPE) {
            $handler = $this->ui->handler($eventName);
        } else {
            $form = $this->ui->findForm($scope);
            if ($form === null) {
                Tools::log()->warning('invalid-request');
                $response->setOk(false);
                $this->finishEvent($response);
                return;
            }

            // hidratar SOLO el form del scope; el resto conserva sus valores de fill()
            $form->hydrate($this->request->request->getArray($form->name()));
            foreach ($this->ui->forms() as $other) {
                if ($other !== $form) {
                    $other->fill();
                }
            }

            // evento builtin _refresh: re-renderiza los fragmentos que declara el
            // trigger (data-ui-targets), sin handler PHP — cascadas de selects
            if ($eventName === '_refresh') {
                $targets = explode(',', $this->request->request->get('_ui_targets', ''));
                foreach (array_filter(array_map('trim', $targets)) as $targetPath) {
                    $response->rerender($targetPath);
                }
                $this->pipeFalse('execAfterAction', $eventId);
                $this->finishEvent($response);
                return;
            }

            $info = $form->handler($eventName);
            $handler = $info['handler'] ?? null;
            $validate = $info['validate'] ?? ($eventName === 'submit');
        }

        if ($form !== null && $validate) {
            $errors = $form->validate();
            if (!$errors->isEmpty()) {
                $response->setOk(false)
                    ->rerender($form)
                    ->fieldErrors($errors, $form->name());
                $this->pipeFalse('execAfterAction', $eventId);
                $this->finishEvent($response);
                return;
            }
        }

        $event = new UIEvent($eventName, $form, $this->ui, $this->request);

        if ($handler !== null) {
            $result = $handler($event, $response);
        } elseif (method_exists($this, $eventName)) {
            // fallback: método homónimo en la subclase
            $result = $this->{$eventName}($event, $response);
        } else {
            Tools::log()->warning('ui-event-without-handler', ['%event%' => $eventId]);
            $result = null;
        }

        if ($result instanceof UIResponse) {
            $response = $result;
        }

        $this->pipeFalse('execAfterAction', $eventId);
        $this->finishEvent($response);
    }

    /** Responde el evento: envelope JSON si es AJAX; redirect o render completo si no. */
    protected function finishEvent(UIResponse $response): void
    {
        if ($this->request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            $this->response->headers->set('Content-Type', 'application/json');
            $this->response->setContent(json_encode($response->toEnvelope($this->ui)));
            $this->setTemplate(false);
            return;
        }

        if ($response->redirectUrl() !== '') {
            $this->redirect($response->redirectUrl());
            $this->setTemplate(false);
            return;
        }

        // degradación sin JS: render completo; los forms hidratados conservan
        // valores sticky y errores en línea
        $this->renderPage();
    }

    // ------------------------------------------------------------------
    // Consultas de datos de componentes
    // ------------------------------------------------------------------

    protected function handleComponentQuery(string $action): void
    {
        $targetPath = $this->request->queryOrInput('_ui_target', '');
        $component = empty($targetPath) ? null : $this->ui->find($targetPath);

        $data = $component instanceof HandlesQueries
            ? $component->handleQuery($action, $this->request)
            : [];

        $this->response->headers->set('Content-Type', 'application/json');
        $this->response->setContent(json_encode($data));
        $this->setTemplate(false);
    }

    // ------------------------------------------------------------------
    // Render
    // ------------------------------------------------------------------

    protected function renderPage(): void
    {
        // los assets deben registrarse antes de que Twig evalúe el <head>
        AssetManager::addJs(Tools::config('route') . '/Dinamic/Assets/JS/UIEngine.js', 3);
        $this->ui->registerAssets();

        $this->setTemplate($this->resolveTemplate());
    }

    /** Plantilla Twig de la página. Sobrescribible por las subclases. */
    protected function resolveTemplate(): string
    {
        return 'Master/UIController';
    }
}
