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

namespace FacturaScripts\Core\UIComponents;

use FacturaScripts\Core\Component\ActionResult;
use FacturaScripts\Dinamic\Lib\ExtendedController\PanelController;

/**
 * Controlador base para formularios con paneles/pestañas construido sobre PanelController.
 *
 * Completa la triada del nuevo sistema UI junto a UIEditController y UIListController:
 *
 *   UIEditController  — formulario simple de edición, sin pestañas, con componentes UI
 *   UIListController  — listado con columnas declaradas como componentes UI
 *   UIPanelController — formulario con pestañas (DocFiles, LogAudit, vistas relacionadas)
 *
 * A diferencia de UIEditController, UIPanelController reutiliza toda la maquinaria de
 * PanelController: plantillas de pestañas (top/left/bottom), DocFilesTrait, LogAuditTrait,
 * addHtmlView(), addListView(), addEditView(), addComponentBlock(), etc.
 *
 * La subclase implementa:
 *   - getModelClassName()  — nombre del modelo principal sin namespace
 *   - createPanels()       — declara las vistas/paneles (reemplaza createViews())
 *   - loadData()           — carga los datos para cada vista (heredado de BaseController)
 *
 * Para controladores con formulario principal estándar (cabecera + líneas AJAX), usa
 * addHtmlView() en createPanels() con la plantilla correspondiente y registra las acciones
 * directamente en execPreviousAction(). Para acciones sencillas sin AJAX también puedes
 * usar onEvent() con un callable que devuelva ActionResult.
 *
 * Uso mínimo:
 *   class MiController extends UIPanelController
 *   {
 *       public function getModelClassName(): string { return 'MiModelo'; }
 *
 *       protected function createPanels(): void
 *       {
 *           $this->addHtmlView('main', 'Tab/MiVista', 'MiModelo', 'my-title', 'fa-solid fa-file');
 *           $this->createViewDocFiles();
 *           $this->createViewLogAudit();
 *       }
 *
 *       protected function loadData($viewName, $view): void { ... }
 *   }
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
abstract class UIPanelController extends PanelController
{
    /**
     * Devuelve el nombre de la clase del modelo principal (sin namespace).
     * Ejemplo: 'Asiento', 'FormaPago'.
     */
    abstract public function getModelClassName(): string;

    /**
     * Declara las vistas y paneles del controlador.
     *
     * Llama aquí a addHtmlView(), addListView(), addEditView(), addComponentBlock(),
     * createViewDocFiles(), createViewLogAudit(), setTabsPosition(), etc.
     * Es el equivalente a createViews() de PanelController con nombre más explícito.
     */
    abstract protected function createPanels(): void;

    /** @var array<string, callable> Manejadores de eventos registrados con onEvent(). */
    private array $panelEventHandlers = [];

    /**
     * Registra un callable para una acción con nombre.
     *
     * Alternativa ligera a sobreescribir execPreviousAction() cuando la acción no
     * necesita lógica AJAX compleja. El callable no recibe argumentos; debe devolver
     * ActionResult (o null para continuar el ciclo de vida normal).
     *
     * Para acciones AJAX que ya devuelven false y gestionan la respuesta directamente,
     * sobreescribe execPreviousAction() en la subclase.
     */
    public function onEvent(string $event, callable $handler): void
    {
        $this->panelEventHandlers[$event] = $handler;
    }

    /**
     * Delega createViews() en createPanels() para que la subclase use el nombre
     * semánticamente correcto para el nuevo sistema UI.
     */
    final protected function createViews(): void
    {
        $this->createPanels();
    }

    /**
     * Enruta acciones a los manejadores registrados con onEvent() antes de delegar
     * en la lógica estándar de PanelController (edit, delete, etc.).
     *
     * Devuelve false para detener el ciclo (cuando el manejador ya ha enviado la
     * respuesta o ha redirigido). Devuelve true para continuar con loadData().
     */
    protected function execPreviousAction($action)
    {
        if ($action !== '' && isset($this->panelEventHandlers[$action])) {
            $result = ($this->panelEventHandlers[$action])();

            if ($result instanceof ActionResult) {
                if ($result->exit) {
                    if (!empty($result->redirect)) {
                        $this->redirect($result->redirect);
                    } else {
                        $this->setTemplate(false);
                    }
                    return false;
                }

                if ($result->stop) {
                    return false;
                }
            }

            // El manejador se ejecutó pero no requiere detener el ciclo.
            return true;
        }

        return parent::execPreviousAction($action);
    }

    /**
     * Carga y cachea el modelo principal desde la base de datos.
     *
     * Lee el código del registro desde el parámetro 'code' de la URL o desde la
     * clave primaria enviada por POST. Devuelve la instancia del modelo (vacía si
     * no se encontró el registro). Útil en los manejadores de eventos para acceder
     * al registro sin duplicar la lógica de carga.
     */
    protected function loadMainModel(): mixed
    {
        $mainViewName = $this->getMainViewName();
        if (!isset($this->views[$mainViewName])) {
            return null;
        }

        $view = $this->views[$mainViewName];
        if ($view->model->exists()) {
            return $view->model;
        }

        $primaryKey = $this->request->input($view->model->primaryColumn(), '');
        $code = $this->request->query('code', $primaryKey);

        if (!empty($code)) {
            $view->model->loadFromCode($code);
        }

        return $view->model;
    }

    /**
     * URL del listado asociado a este formulario.
     *
     * Usada en redirecciones post-guardado/borrado. La implementación base delega en
     * model->url('list'). Las subclases sobreescriben para apuntar a un listado custom.
     */
    public function listUrl(): string
    {
        $model = $this->loadMainModel();
        return ($model !== null && method_exists($model, 'url'))
            ? $model->url('list')
            : $this->url();
    }
}
