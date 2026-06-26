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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Component\ActionResult;
use FacturaScripts\Core\Component\ComponentBlock;
use FacturaScripts\Core\Component\UIController;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditListView;
use FacturaScripts\Core\Lib\ExtendedController\ListView;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\ExportManager;
use FacturaScripts\Dinamic\Model\PageOption;

/**
 * Controlador base para formularios de edición construidos con el sistema de componentes UI.
 *
 * Replica la funcionalidad de PanelController con la excepción de que los campos del
 * formulario se declaran mediante instancias de FieldComponent en lugar de XMLView.
 *
 * La subclase implementa getModelClassName() y buildForm(). Cuando se añaden paneles extra
 * con addPanel(), la plantilla muestra la misma navegación lateral de nav-pills que
 * PanelController; sin paneles extra, muestra una card única sin tabs.
 *
 * Ciclo de vida:
 *  createUI → buildForm() + auto-registro de handlers 'save'/'delete'
 *  → (POST) processComponents → save | (GET) populateFromModel → populatePanels
 *  → modifyUI → setTemplate
 *
 * Uso mínimo:
 *   public function getModelClassName(): string { return 'MiModelo'; }
 *   protected function buildForm(): void {
 *       $this->addComponent(ComponentText::make('nombre')->setRequired());
 *       $panel = $this->addPanel('extra', 'Extra', 'fa-solid fa-list');
 *       $panel->addComponent(ComponentText::make('observaciones'));
 *   }
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
abstract class UIEditController extends UIController
{
    const MODEL_NAMESPACE = '\\FacturaScripts\\Dinamic\\Model\\';

    /**
     * Indica si el registro existe en la base de datos.
     * false → modo creación, true → modo edición.
     */
    public bool $hasData = false;

    /** Instancia cacheada del modelo. Se inicializa en loadModel(). */
    protected mixed $editModel = null;

    /** Gestor de exportación (PDF, XLS, CSV…). Disponible en Twig como fsc.exportManager. */
    public ExportManager $exportManager;

    /** @var ComponentBlock[] Paneles extra indexados por nombre. */
    private array $extraPanels = [];

    /** @var ListView[] Listas relacionadas (vistas debajo del formulario) indexadas por nombre. */
    private array $listViews = [];

    /** Nombre de la vista de lista activa para que Twig llame a getCurrentView(). */
    private string $currentListViewName = '';

    /**
     * Devuelve el nombre de la clase del modelo a editar (sin namespace).
     * Ejemplo: 'FormaPago', 'Cliente'.
     */
    abstract public function getModelClassName(): string;

    /**
     * Declara los campos del formulario principal usando addComponent() y los
     * paneles adicionales usando addPanel(). Los handlers 'save' y 'delete'
     * se registran automáticamente; declara los tuyos con onEvent() si necesitas
     * comportamiento personalizado.
     */
    abstract protected function buildForm(): void;

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['showonmenu'] = false;
        return $data;
    }

    /**
     * Devuelve el modelo activo. Útil en Twig para acceder a datos del registro
     * sin pasar por los componentes (p. ej. para mostrar relaciones).
     */
    public function getModel(): mixed
    {
        return $this->editModel;
    }

    /**
     * Devuelve la URL de una imagen representativa del registro, o cadena vacía.
     * La subclase puede sobreescribir para mostrar una imagen en la cabecera.
     */
    public function getImageUrl(): string
    {
        return '';
    }

    /**
     * Registra un panel extra con nombre, título e icono.
     *
     * Devuelve el ComponentBlock asociado para añadirle componentes.
     * El panel aparece en la navegación lateral junto al formulario principal
     * solo cuando hay al menos un panel extra registrado.
     */
    public function addPanel(string $name, string $title, string $icon = 'fa-solid fa-folder'): ComponentBlock
    {
        $block = ComponentBlock::make($name, $title, $icon);
        $this->extraPanels[$name] = $block;
        return $block;
    }

    /**
     * Devuelve los paneles extra registrados, indexados por nombre.
     * Usado por la plantilla Twig para construir la navegación lateral.
     */
    public function extraPanels(): array
    {
        return $this->extraPanels;
    }

    /**
     * Registra un ListView real debajo del formulario de edición.
     *
     * Crea una instancia de ListView con el modelo y la configuración XML del viewName,
     * aplica loadPageOptions() con el usuario actual y almacena la vista para que la
     * plantilla la renderice con {{ include(listView.template) }}.
     *
     * Llama a este método desde buildForm(). En modifyUI() usa listView($name) para
     * acceder a la vista, llama processFormData($request, 'load') y luego loadData().
     *
     * @param string $viewName  Nombre de la vista (coincide con el fichero XML, p. ej. 'ListSubcuenta').
     * @param string $modelName Nombre del modelo sin namespace (p. ej. 'Subcuenta').
     * @param string $viewTitle Clave de traducción del título.
     * @param string $icon      Clase FontAwesome del icono.
     */
    public function addListView(string $viewName, string $modelName, string $viewTitle, string $icon = 'fa-solid fa-list'): ListView
    {
        $view = new ListView($viewName, $viewTitle, self::MODEL_NAMESPACE . $modelName, $icon);
        $view->settings['card'] = true;
        $view->loadPageOptions($this->user);
        $this->listViews[$viewName] = $view;
        return $view;
    }

    public function addEditListView(string $viewName, string $modelName, string $viewTitle, string $icon = 'fa-solid fa-bars'): EditListView
    {
        $view = new EditListView($viewName, $viewTitle, self::MODEL_NAMESPACE . $modelName, $icon);
        $view->settings['card'] = true;
        $view->loadPageOptions($this->user);
        $this->listViews[$viewName] = $view;
        return $view;
    }

    /**
     * Devuelve las vistas de lista registradas, indexadas por nombre.
     * Usado por la plantilla Twig para renderizar cada sección inferior.
     */
    public function listViews(): array
    {
        return $this->listViews;
    }

    /**
     * Devuelve el ListView con el nombre dado, o null si no existe.
     * Útil en modifyUI() para cargar datos:
     *   $this->listView('ListSubcuenta')?->loadData('', $where);
     */
    protected function listView(string $name): ?BaseView
    {
        return $this->listViews[$name] ?? null;
    }

    /**
     * Establece la vista de lista activa para que {{ include(listView.template) }} en Twig
     * pueda llamar a fsc.getCurrentView() y obtener el objeto correcto.
     */
    public function setCurrentView(string $viewName): void
    {
        $this->currentListViewName = $viewName;
    }

    /**
     * Devuelve la vista de lista activa. Llamado por ListView.html.twig via fsc.getCurrentView().
     */
    public function getCurrentView(): BaseView
    {
        return $this->listViews[$this->currentListViewName];
    }

    /**
     * Omite processComponents() cuando el POST proviene de un ListView embebido
     * (activetab coincide con el nombre de uno de los listViews).
     */
    protected function skipFormProcessing(): bool
    {
        $activetab = $this->request->request->get('activetab', '');
        return isset($this->listViews[$activetab]);
    }

    /**
     * Devuelve el nombre del panel activo según el parámetro activetab de la petición.
     * '__main__' indica que está activo el formulario principal.
     */
    public function activeTab(): string
    {
        $tab = $this->request->inputOrQuery('activetab', '__main__');
        if ($tab === '__main__' || isset($this->extraPanels[$tab])) {
            return $tab;
        }
        return '__main__';
    }

    /**
     * Carga el modelo desde la base de datos usando el parámetro 'code' de la URL.
     *
     * El modelo se cachea en $this->editModel. Si no se encuentra, la instancia queda
     * vacía y hasData = false. Si el usuario no tiene permisos, se activa la plantilla
     * de acceso denegado y se devuelve null.
     */
    protected function loadModel(): ?object
    {
        if ($this->editModel !== null) {
            return $this->editModel;
        }

        $modelClass = self::MODEL_NAMESPACE . $this->getModelClassName();
        $this->editModel = new $modelClass();

        $primaryKey = $this->request->input($this->editModel->primaryColumn(), '');
        $code = $this->request->query('code', $primaryKey);

        if (!empty($code)) {
            if ($this->editModel->loadFromCode($code)) {
                if (false === $this->checkOwnerData($this->editModel)) {
                    $this->setTemplate('Error/AccessDenied');
                    $this->editModel = null;
                    return null;
                }

                $this->hasData = true;
                $this->title .= ' ' . $this->editModel->primaryDescription();
            } else {
                Tools::log()->warning('record-not-found');
            }
        }

        return $this->editModel;
    }

    protected function resolveTemplate(): string
    {
        return 'Master/UIEditController';
    }

    /**
     * Nombre de la vista XML equivalente en el sistema antiguo.
     *
     * Devuelve '' por defecto (sin carga de opciones). La subclase puede
     * sobreescribir para enlazar con un PageOption existente (p. ej.
     * return 'EditFormaPago') y así respetar la visibilidad configurada
     * por el usuario a través del botón Opciones.
     */
    protected function getViewName(): string
    {
        return '';
    }

    /**
     * Sobreescribe modifyUI() para poblar los paneles extra desde el modelo en GET
     * y para aplicar la configuración de visibilidad guardada en pages_options.
     */
    protected function modifyUI(): void
    {
        // Procesa acciones insert/edit/delete de EditListView embebidas.
        // UIController despacha eventos por _event, pero EditListView usa el campo
        // 'action'; cuando skipFormProcessing() devuelve true solo se llama a
        // populateFromModel(), por lo que interceptamos aquí antes de recargar datos.
        if ($this->request->isMethod('POST')) {
            $activetab = $this->request->request->get('activetab', '');
            if (isset($this->listViews[$activetab]) && $this->listViews[$activetab] instanceof EditListView) {
                $editAction = $this->request->request->get('action', '');
                if (in_array($editAction, ['insert', 'edit', 'delete'], true)) {
                    $this->editListViewAction($editAction);
                }
            }
        }

        $model = $this->loadModel();
        if ($model !== null && !empty($this->extraPanels)) {
            foreach ($this->extraPanels as $panel) {
                $panel->populate($model);
            }
        }

        $this->applyPageOptions();
    }

    /**
     * Lee el PageOption para getViewName() y aplica el estado display de cada
     * campo al componente correspondiente por fieldname.
     */
    protected function applyPageOptions(): void
    {
        $viewName = $this->getViewName();
        if (empty($viewName)) {
            return;
        }

        $pageOption = new PageOption();
        $where = [
            new DataBaseWhere('name', $viewName),
            new DataBaseWhere('nick', $this->user->nick),
            new DataBaseWhere('nick', null, 'IS', 'OR'),
        ];

        if (!$pageOption->loadWhere($where, ['nick' => 'ASC'])) {
            return;
        }

        $map = [];
        foreach ((array)$pageOption->columns as $group) {
            foreach ((array)($group['columns'] ?? []) as $col) {
                $fieldname = $col['widget']['fieldname'] ?? null;
                if ($fieldname !== null) {
                    $map[$fieldname] = $col['display'] ?? 'left';
                }
            }
        }

        foreach ($this->components() as $fieldname => $component) {
            if (isset($map[$fieldname])) {
                $component->setDisplay($map[$fieldname]);
            }
        }
    }

    /**
     * Implementación interna de createUI(): llama a buildForm() y registra
     * los handlers por defecto si la subclase no los declaró explícitamente.
     */
    final protected function createUI(): void
    {
        $this->exportManager = new ExportManager();

        $this->buildForm();

        if (!$this->hasEventHandler('save')) {
            $this->onEvent('save', fn() => $this->defaultSave());
        }

        if (!$this->hasEventHandler('delete')) {
            $this->onEvent('delete', fn() => $this->defaultDelete());
        }

        if (!$this->hasEventHandler('export')) {
            $this->onEvent('export', fn() => $this->exportAction());
        }
    }

    /**
     * Devuelve HTML de botones extra inyectados en la cabecera del formulario.
     *
     * La implementación base retorna cadena vacía. Las subclases pueden sobreescribir
     * este método para añadir botones específicos (p. ej. bloquear/desbloquear).
     * El HTML se renderiza crudo en el template con `{{ fsc.extraHeaderButtons() | raw }}`.
     */
    public function extraHeaderButtons(): string
    {
        return '';
    }

    /**
     * Devuelve la URL del listado asociado a este formulario de edición.
     *
     * Se usa en las redirecciones post-guardado y post-borrado, y en el botón
     * «Volver» de la plantilla Twig. La implementación base delega en
     * model->url('list'). Las subclases pueden sobreescribir este método para
     * apuntar a un controlador de listado personalizado.
     */
    public function listUrl(): string
    {
        $model = $this->editModel;
        return ($model !== null && method_exists($model, 'url'))
            ? $model->url('list')
            : $this->url();
    }

    /**
     * Exporta el registro activo al formato solicitado (PDF, XLS, CSV…).
     *
     * Construye adaptadores de columna compatibles con el motor de exportación antiguo
     * usando los componentes del formulario (se omiten los ocultos). El resultado se
     * escribe directamente en la respuesta HTTP y se suprime la plantilla Twig.
     */
    protected function exportAction(): ActionResult
    {
        if (false === $this->permissions->allowExport) {
            Tools::log()->warning('no-print-permission');
            return ActionResult::make();
        }

        $model = $this->loadModel();
        if (null === $model) {
            return ActionResult::make();
        }

        $option = $this->request->get('option', ExportManager::defaultOption());
        $idformat = (int) $this->request->get('idformat', 0);
        $langcode = $this->request->get('langcode', '');

        $this->exportManager->newDoc($option, $this->title, $idformat, $langcode);

        $columns = [];
        foreach ($this->components() as $fieldname => $component) {
            if ($component->isHidden()) {
                continue;
            }

            $fn = $fieldname;
            $comp = $component;

            $col = new class ($fn, $comp) {
                public string $title;
                public string $display = 'left';
                public object $widget;

                public function __construct(string $fn, object $comp)
                {
                    $this->title = $comp->label();
                    $fnInner = $fn;
                    $compInner = $comp;
                    $this->widget = new class ($fnInner, $compInner) {
                        public string $fieldname;
                        private object $comp;

                        public function __construct(string $fn, object $comp)
                        {
                            $this->fieldname = $fn;
                            $this->comp = $comp;
                        }

                        public function plainText(object $model): string
                        {
                            $val = property_exists($model, $this->fieldname)
                                ? $model->{$this->fieldname}
                                : null;
                            $this->comp->setValue($val);
                            return $this->comp->textValue();
                        }
                    };
                }

                public function hidden(): bool
                {
                    return false;
                }
            };

            $columns[] = $col;
        }

        $this->exportManager->addModelPage($model, $columns, $this->title);
        $this->exportManager->show($this->response);

        return ActionResult::make()->exit();
    }

    /**
     * Guarda el modelo en la base de datos.
     *
     * Comprueba permisos de escritura. Si es un registro nuevo, redirige a la URL
     * del registro recién creado. En edición muestra la notificación de éxito.
     */
    protected function defaultSave(): ActionResult
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return ActionResult::make();
        }

        $model = $this->loadModel();
        if ($model === null) {
            return ActionResult::make();
        }

        $isNew = !$model->exists();

        if ($model->save()) {
            if ($isNew) {
                $editUrl = $this->url() . '?code=' . urlencode($model->primaryColumnValue()) . '&action=save-ok';
                return ActionResult::make()->withRedirect($editUrl);
            }

            Tools::log()->notice('record-updated-correctly');
        } else {
            Tools::log()->error('record-save-error');
        }

        return ActionResult::make();
    }

    /**
     * Elimina el modelo de la base de datos.
     *
     * Comprueba permisos y token de formulario. Redirige al listado con
     * action=delete-ok para que UIListController muestre la notificación.
     */
    protected function defaultDelete(): ActionResult
    {
        if (false === $this->permissions->allowDelete) {
            Tools::log()->warning('not-allowed-delete');
            return ActionResult::make();
        }

        if (false === $this->validateFormToken()) {
            return ActionResult::make();
        }

        $model = $this->loadModel();
        if ($model !== null && $model->exists()) {
            $listUrl = $this->listUrl();

            if ($model->delete()) {
                $redirect = strpos($listUrl, '?') === false
                    ? $listUrl . '?action=delete-ok'
                    : $listUrl . '&action=delete-ok';

                return ActionResult::make()->withRedirect($redirect);
            }

            Tools::log()->error('record-deleted-error');
        }

        return ActionResult::make();
    }

    private function editListViewAction(string $action): ActionResult
    {
        $activetab = $this->request->request->get('activetab', '');
        $view = $this->listViews[$activetab] ?? null;
        if (!($view instanceof EditListView)) {
            return ActionResult::make();
        }

        if ($action === 'delete') {
            if (false === $this->permissions->allowDelete) {
                Tools::log()->warning('not-allowed-delete');
                return ActionResult::make();
            }
        } else {
            if (false === $this->permissions->allowUpdate) {
                Tools::log()->warning('not-allowed-modify');
                return ActionResult::make();
            }
        }

        if (false === $this->validateFormToken()) {
            return ActionResult::make();
        }

        $view->processFormData($this->request, 'edit');

        if ($action === 'delete') {
            if ($view->model->delete()) {
                Tools::log()->notice('record-deleted-correctly');
            }
        } else {
            if ($view->model->save()) {
                Tools::log()->notice('record-updated-correctly');
            }
        }

        return ActionResult::make();
    }

    /**
     * Comprueba que el usuario activo tenga permisos sobre el registro cargado.
     * Replica la lógica de BaseController::checkOwnerData().
     */
    protected function checkOwnerData(object $model): bool
    {
        if (false === $this->permissions->onlyOwnerData || empty($model->primaryColumnValue())) {
            return true;
        }

        if (property_exists($model, 'nick')) {
            if (null === $model->nick || $model->nick === $this->user->nick) {
                return true;
            }
            if (property_exists($model, 'codagente') && $this->user->codagente) {
                return $model->codagente === $this->user->codagente;
            }
            return false;
        }

        if (property_exists($model, 'codagente')) {
            return $model->codagente === $this->user->codagente;
        }

        return true;
    }
}
