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

namespace FacturaScripts\Core\Template;

use Exception;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\Block\Block;
use FacturaScripts\Core\Lib\Block\Page;
use FacturaScripts\Core\Lib\Block\ViewBlock;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\ListView;
use FacturaScripts\Core\Lib\ExtendedController\OwnerDataTrait;
use FacturaScripts\Core\Lib\Widget\VisualItem;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Lib\ExportManager;
use FacturaScripts\Dinamic\Lib\MultiRequestProtection;
use FacturaScripts\Dinamic\Model\CodeModel;

/**
 * Controlador cuya página es un árbol de bloques (secciones, columnas, pestañas, formularios,
 * listados...) en lugar de una lista plana de pestañas. Cada bloque declara sus propios datos
 * y acciones, y se puede pedir por separado para cargarlo solo cuando se va a mostrar.
 *
 * Las peticiones se dirigen a un bloque con el parámetro block (o activetab, que es el que
 * envían las plantillas de las vistas) y un bloque se pide por separado con fragment.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class BlockController extends Controller
{
    use OwnerDataTrait;

    /** @var CodeModel */
    public $codeModel;

    /** @var string */
    private $current = '';

    /** @var ExportManager */
    public $exportManager;

    /** @var bool */
    private $finished = false;

    /**
     * Indica si existe el registro principal. Lo consultan las plantillas de las vistas.
     *
     * @var bool
     */
    public $hasData = false;

    /** @var string[] */
    private $loaded = [];

    /** @var Page */
    private $page;

    /** @var string */
    private $target = '';

    /**
     * Añade los bloques a la página.
     */
    abstract protected function createBlocks(): void;

    public function __construct(string $className, string $url = '')
    {
        parent::__construct($className, $url);

        $this->page = new Page();
    }

    public function block(string $name): Block
    {
        $block = $this->page->find($name);
        if (null === $block) {
            throw new Exception('Block not found: ' . $name);
        }

        return $block;
    }

    /**
     * Devuelve la url con la que se pide un bloque por separado.
     */
    public function fragmentUrl(Block $block): string
    {
        $params = $this->request()->query->all();
        unset($params['action'], $params['multireqtoken']);
        $params['fragment'] = $block->name();

        return $this->url() . '?' . http_build_query($params);
    }

    /**
     * Devuelve la vista del bloque que se está pintando. La usan las plantillas de las vistas.
     */
    public function getCurrentView(): BaseView
    {
        return $this->viewBlock($this->current)->view();
    }

    /**
     * Indica si existe el registro principal de la página. Los bloques que lo necesitan
     * no se muestran hasta entonces. Una página sin registro principal devuelve siempre true.
     */
    public function hasRecord(): bool
    {
        return true;
    }

    /**
     * Devuelve el nombre del bloque del registro principal, si la página tiene uno.
     */
    public function mainBlockName(): string
    {
        return '';
    }

    public function page(): Page
    {
        return $this->page;
    }

    /**
     * Devuelve el registro principal de la página, que es el que reciben los bloques
     * para filtrar sus datos, o null si la página no tiene.
     *
     * @return ModelClass|null
     */
    public function record()
    {
        return null;
    }

    public function run(): void
    {
        parent::run();

        Session::set('permissions', $this->permissions);

        // añadimos el usuario a la semilla de generación del token
        $protection = new MultiRequestProtection();
        $protection->addSeed($this->user->nick);
        VisualItem::setToken($protection->newToken());

        $this->codeModel = new CodeModel();
        $this->exportManager = new ExportManager();
        AssetManager::addJs(Tools::config('route') . '/Dinamic/Assets/JS/BlockController.js');

        $this->createBlocks();
        $this->pipe('createBlocks');
        $this->page->assertUniqueNames();

        $this->process();
        if ($this->finished) {
            $this->response()->send();
            return;
        }

        // ¿se pide un bloque por separado?
        $fragment = (string)$this->request()->query('fragment', '');
        if ('' !== $fragment) {
            $this->view('Master/BlockFragment.html.twig', ['blockItem' => $this->block($fragment)]);
            return;
        }

        $this->view('Master/BlockController.html.twig');
    }

    public function setCurrentBlock(string $name): void
    {
        $this->current = $name;
    }

    /**
     * Devuelve el nombre del bloque al que va dirigida la petición.
     */
    public function target(): string
    {
        return $this->target;
    }

    public function viewBlock(string $name): ViewBlock
    {
        $block = $this->page->find($name);
        if ($block instanceof ViewBlock) {
            return $block;
        }

        throw new Exception('ViewBlock not found: ' . $name);
    }

    /**
     * Se ejecuta antes de cualquier acción para comprobar que el usuario puede acceder
     * al registro sobre el que se ejecuta. Debe lanzar una KernelException si no puede.
     */
    protected function assertActionOwnerData(string $action): void
    {
    }

    /**
     * Devuelve los valores para un widget autocomplete.
     */
    protected function autocompleteAction(): array
    {
        $data = $this->requestGet(['field', 'fieldcode', 'fieldfilter', 'fieldsign', 'fieldtitle', 'formname', 'source', 'strict', 'term']);
        if ('' == $data['source']) {
            // sin source necesitamos el nombre del bloque y el campo para localizar el widget
            $block = $this->page->find((string)$data['formname']);
            if (empty($data['field']) || false === $block instanceof ViewBlock) {
                return [];
            }

            $results = [];
            $column = $block->view()->columnForField($data['field']);
            foreach (empty($column) ? [] : $column->widget->values as $value) {
                $results[] = ['key' => Tools::trans($value['title']), 'value' => $value['value']];
            }
            return $results;
        }

        $where = $this->codeModelWhere($data, true);
        if (null === $where) {
            return [];
        }

        $results = [];
        foreach ($this->codeModel->search($data['source'], $data['fieldcode'], $data['fieldtitle'], $data['term'], $where) as $value) {
            $results[] = ['key' => Tools::fixHtml($value->code), 'value' => Tools::fixHtml($value->description)];
        }

        if (empty($results) && '0' == $data['strict']) {
            $results[] = ['key' => $data['term'], 'value' => $data['term']];
        } elseif (empty($results)) {
            $results[] = ['key' => null, 'value' => Tools::trans('no-data')];
        }

        return $results;
    }

    /**
     * Devuelve los valores para un widget datalist.
     */
    protected function datalistAction(): array
    {
        $data = $this->requestGet(['field', 'fieldcode', 'fieldfilter', 'fieldsign', 'fieldtitle', 'formname', 'source', 'term']);
        $where = $this->codeModelWhere($data, false);
        if (null === $where) {
            return [];
        }

        $results = [];
        foreach ($this->codeModel->all($data['source'], $data['fieldcode'], $data['fieldtitle'], false, $where) as $value) {
            $results[] = ['key' => $value->code, 'value' => $value->description];
        }

        return $results;
    }

    /**
     * Elimina los registros seleccionados del bloque al que va dirigida la petición.
     */
    protected function deleteAction(): bool
    {
        $view = $this->targetView();
        if (null === $view || false === $this->permissions->allowDelete || false === $view->settings['btnDelete']) {
            Tools::log()->warning('not-allowed-delete');
            return false;
        } elseif (false === $this->validateFormToken()) {
            return false;
        }

        $model = $view->model;
        $codes = $this->request()->request->getArray('codes');
        $code = $this->request()->input('code');
        if (empty($codes) && empty($code)) {
            Tools::log()->warning('no-selected-item');
            return false;
        }

        if (false === empty($codes)) {
            // varios registros: o se eliminan todos o ninguno
            $this->db()->beginTransaction();
            foreach ($codes as $cod) {
                if ($model->loadFromCode($cod) && $this->checkOwnerData($model) && $model->delete()) {
                    continue;
                }

                $this->db()->rollback();
                Tools::log()->warning('record-deleted-error');
                $model->clear();
                return false;
            }

            $this->db()->commit();
            Tools::log()->notice('record-deleted-correctly');
            $model->clear();
            return true;
        }

        if ($model->loadFromCode($code) && $this->checkOwnerData($model) && $model->delete()) {
            Tools::log()->notice('record-deleted-correctly');
            $model->clear();
            return true;
        }

        Tools::log()->warning('record-deleted-error');
        $model->clear();
        return false;
    }

    /**
     * Guarda los cambios del formulario del bloque al que va dirigida la petición.
     */
    protected function editAction(): bool
    {
        $view = $this->targetView();
        if (null === $view || false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return false;
        } elseif (false === $this->validateFormToken()) {
            return false;
        }

        $code = (string)$this->request()->input('code', '');
        if (false === $view->model->loadFromCode($code)) {
            Tools::log()->error('record-not-found');
            return false;
        }

        // ¿puede el usuario modificar este registro?
        if (false === $this->checkOwnerData($view->model)) {
            Tools::log()->warning('not-allowed-modify');
            return false;
        }

        $view->processFormData($this->request(), 'edit');

        // ¿ha cambiado la clave primaria?
        $view->newCode = (string)$view->model->primaryColumnValue();
        if ($code !== $view->newCode && $view->model->test()) {
            $pkColumn = $view->model->primaryColumn();
            $view->model->{$pkColumn} = $code;
            if (false === $view->model->changePrimaryColumnValue($view->newCode)) {
                Tools::log()->error('record-save-error');
                return false;
            }
        }

        if ($view->model->save()) {
            Tools::log()->notice('record-updated-correctly');
            return true;
        }

        Tools::log()->error('record-save-error');
        return false;
    }

    /**
     * Ejecuta las acciones que modifican datos, antes de cargarlos.
     *
     * @return bool false para terminar la petición sin cargar datos ni pintar la página
     */
    protected function execAction(string $action): bool
    {
        switch ($action) {
            case '':
                return true;

            case 'autocomplete':
                return $this->json($this->autocompleteAction());

            case 'datalist':
                return $this->json($this->datalistAction());

            case 'select':
                return $this->json($this->selectAction());

            case 'delete':
                if ($this->deleteAction() && $this->target === $this->mainBlockName()) {
                    // al eliminar el registro principal vamos al listado, que es donde se muestra el mensaje
                    $listUrl = $this->targetView()->model->url('list');
                    return $this->redirect($listUrl . (false === strpos($listUrl, '?') ? '?' : '&') . 'action=delete-ok');
                }
                return true;

            case 'edit':
                if ($this->editAction()) {
                    $this->targetView()->model->clear();
                }
                return true;

            case 'insert':
                return $this->insertAction();
        }

        // ¿es una acción propia del bloque?
        $block = $this->page->find($this->target);
        $callback = $block instanceof ViewBlock ? $block->getAction($action) : null;
        if (null !== $callback) {
            call_user_func($callback, $block, $this);
        }

        return true;
    }

    /**
     * Ejecuta las acciones que necesitan los datos ya cargados.
     */
    protected function execAfterAction(string $action): void
    {
        switch ($action) {
            case 'delete-ok':
                Tools::log()->notice('record-deleted-correctly');
                break;

            case 'export':
                $this->exportAction();
                break;

            case 'save-ok':
                Tools::log()->notice('record-updated-correctly');
                break;
        }
    }

    /**
     * Exporta el bloque al que va dirigida la petición.
     */
    protected function exportAction(): void
    {
        $view = $this->targetView();
        if (null === $view || false === $view->settings['btnPrint'] || false === $this->permissions->allowExport) {
            Tools::log()->warning('no-print-permission');
            return;
        }

        $this->exportManager->newDoc(
            (string)$this->request()->queryOrInput('option', ''),
            $this->title,
            (int)$this->request()->input('idformat', ''),
            (string)$this->request()->input('langcode', '')
        );
        $view->export($this->exportManager, $this->request()->request->getArray('codes'));

        $response = $this->response();
        $this->exportManager->show($response);
        $this->finished = true;
    }

    /**
     * Crea el registro del formulario del bloque al que va dirigida la petición.
     *
     * @return bool false para terminar la petición sin cargar datos ni pintar la página
     */
    protected function insertAction(): bool
    {
        $view = $this->targetView();
        if (null === $view || false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return true;
        } elseif (false === $this->validateFormToken()) {
            return true;
        }

        $view->processFormData($this->request(), 'edit');
        if ($view->model->exists()) {
            Tools::log()->error('duplicate-record');
            $view->model->clear();
            return true;
        }

        if (false === $view->model->save()) {
            // conservamos los datos del formulario para que el usuario pueda corregirlos
            Tools::log()->error('record-save-error');
            return true;
        }

        // si es el registro principal, vamos a su página
        if ($this->target === $this->mainBlockName()) {
            return $this->redirect($view->model->url() . '&action=save-ok');
        }

        $view->newCode = (string)$view->model->primaryColumnValue();
        $view->model->clear();
        Tools::log()->notice('record-updated-correctly');
        return true;
    }

    /**
     * Termina la petición respondiendo con json.
     *
     * @return bool siempre false, para devolverlo desde execAction()
     */
    protected function json(array $data): bool
    {
        $this->response()->json($data);
        $this->finished = true;

        return false;
    }

    /**
     * Carga los datos del bloque indicado y de los que contiene, salvo los que no se
     * muestran y los aplazados.
     */
    protected function loadBlock(Block $block): void
    {
        if ($block->isDeferred() || false === $block->isVisible($this->hasRecord())) {
            return;
        }

        if (false === in_array($block->name(), $this->loaded, true)) {
            $block->load($this, $block->name() === $this->target);
            $this->loaded[] = $block->name();
            $this->pipe('loadBlock', $block);

            // guardamos la foto de navegación de los listados
            if ($block instanceof ViewBlock && $block->view() instanceof ListView) {
                $block->view()->saveNavigation();
            }
        }

        foreach ($block->children() as $child) {
            $this->loadBlock($child);
        }
    }

    /**
     * Carga el registro principal de la página, antes de cargar los bloques.
     */
    protected function loadRecord(): void
    {
    }

    /**
     * Marca un bloque como ya cargado, para que loadBlock() no lo vuelva a cargar.
     */
    protected function markLoaded(Block $block): void
    {
        $this->loaded[] = $block->name();
    }

    /**
     * Ejecuta la petición sobre el árbol de bloques: acciones, carga de datos y acciones posteriores.
     */
    protected function process(): void
    {
        $action = (string)$this->request()->inputOrQuery('action', '');
        $fragment = (string)$this->request()->query('fragment', '');
        $this->target = $this->resolveTarget();

        // el usuario debe poder acceder al registro antes de ejecutar ninguna acción sobre él
        $this->assertActionOwnerData($action);

        if (false === $this->execAction($action) || false === $this->pipeFalse('execAction', $action)) {
            $this->finished = true;
            return;
        }

        $this->loadRecord();

        $root = '' === $fragment ? $this->page : $this->page->find($fragment);
        if (null === $root) {
            $this->response()->setHttpCode(404);
            $this->finished = true;
            return;
        }

        $this->page->plan($this->target, $fragment);
        $this->loadBlock($root);

        $this->execAfterAction($action);
        $this->pipe('execAfterAction', $action);
    }

    /**
     * Termina la petición redirigiendo a otra url.
     *
     * @return bool siempre false, para devolverlo desde execAction()
     */
    protected function redirect(string $url): bool
    {
        $this->response()->redirect($url);
        $this->finished = true;

        return false;
    }

    protected function requestGet(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->request()->queryOrInput($key);
        }

        return $result;
    }

    /**
     * Devuelve los valores para un widget select que depende de otro.
     */
    protected function selectAction(): array
    {
        $required = (bool)$this->request()->queryOrInput('required', false);
        $data = $this->requestGet(['field', 'fieldcode', 'fieldfilter', 'fieldsign', 'fieldtitle', 'formname', 'source', 'term']);

        $return = $this->pipe('selectAction', $data, $required);
        if ($return) {
            return $return;
        }

        $where = $this->codeModelWhere($data, false);
        if (null === $where) {
            return [];
        }

        $results = [];
        foreach ($this->codeModel->all($data['source'], $data['fieldcode'], $data['fieldtitle'], !$required, $where) as $value) {
            // no usar fixHtml() aquí porque compromete la seguridad
            $results[] = ['key' => $value->code, 'value' => $value->description];
        }

        return $results;
    }

    /**
     * Devuelve la vista del bloque al que va dirigida la petición, o null si no hay ninguno.
     */
    protected function targetView(): ?BaseView
    {
        $block = $this->page->find($this->target);

        return $block instanceof ViewBlock ? $block->view() : null;
    }

    /**
     * Comprueba la firma que los widgets envían con sus parámetros de consulta y devuelve
     * el filtro a aplicar, o null si la petición no es válida. Sin la firma se podría leer
     * cualquier columna de cualquier tabla cambiando source, fieldcode o fieldtitle.
     *
     * @param array $data
     * @param bool $fromRequest true si el valor de cada filtro viene en la petición con el nombre del campo
     *
     * @return DataBaseWhere[]|null
     */
    private function codeModelWhere(array $data, bool $fromRequest): ?array
    {
        $valid = CodeModel::verifySign(
            (string)($data['fieldsign'] ?? ''),
            (string)($data['source'] ?? ''),
            (string)($data['fieldcode'] ?? ''),
            (string)($data['fieldtitle'] ?? ''),
            (string)($data['fieldfilter'] ?? '')
        );
        if (false === $valid) {
            Tools::log()->warning('invalid-autocomplete-source: ' . $data['source']);
            return null;
        }

        $where = [];
        foreach (DataBaseWhere::applyOperation($data['fieldfilter'] ?? '') as $field => $operation) {
            // validamos el nombre del campo para prevenir inyección SQL
            if (1 !== preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z_][a-zA-Z0-9_]*)?$/', $field)) {
                Tools::log()->warning('invalid field filter name');
                return null;
            }

            $value = $fromRequest ? $this->request()->queryOrInput($field) : $data['term'];
            $where[] = new DataBaseWhere($field, $value, '=', $operation);
        }

        return $where;
    }

    /**
     * Devuelve el nombre del bloque al que va dirigida la petición. Si no se indica ninguno,
     * es el del registro principal o, en su defecto, el primer bloque con una vista.
     */
    private function resolveTarget(): string
    {
        $block = (string)$this->request()->inputOrQuery('block', '');
        $name = '' === $block ? (string)$this->request()->inputOrQuery('activetab', '') : $block;
        if ($this->page->find($name) instanceof ViewBlock) {
            return $name;
        }

        if ('' !== $this->mainBlockName()) {
            return $this->mainBlockName();
        }

        $first = $this->firstViewBlock($this->page);
        return null === $first ? '' : $first->name();
    }

    private function firstViewBlock(Block $block): ?ViewBlock
    {
        if ($block instanceof ViewBlock) {
            return $block;
        }

        foreach ($block->children() as $child) {
            $found = $this->firstViewBlock($child);
            if (null !== $found) {
                return $found;
            }
        }

        return null;
    }
}
