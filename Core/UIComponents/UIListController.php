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

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Component\FieldComponent;
use FacturaScripts\Core\Lib\ExportManager;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Dinamic\Model\PageOption;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Core\UIComponents\UIListTab;

/**
 * Controlador base para listados construidos con el sistema de componentes UI.
 *
 * Replica la funcionalidad de ListController con la excepción de que las columnas
 * se declaran mediante instancias de FieldComponent en lugar de XMLView. Esto permite
 * aprovechar el motor de renderizado del sistema de componentes (renderCell, displayValue)
 * directamente en la tabla de resultados.
 *
 * Ciclo de vida: createUI → execPreviousAction → loadRecords → execAfterAction → setTemplate.
 *
 * Uso mínimo en subclase:
 *   public function getModelClassName(): string { return 'MiModelo'; }
 *   protected function createUI(): void {
 *       $this->addColumn(ComponentText::make('codigo')->setLabel('code')->setCols(2));
 *       $this->addSearchField('codigo', 'descripcion');
 *       $this->addOrderBy(['codigo'], 'code', 1);
 *   }
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
abstract class UIListController extends Controller
{
    use HasListFilters;

    const MODEL_NAMESPACE = '\\FacturaScripts\\Dinamic\\Model\\';

    /** @var FieldComponent[] keyed by fieldname */
    protected array $columns = [];

    /** Campos de la tabla sobre los que actúa la búsqueda por texto libre. */
    protected array $searchFields = [];

    /**
     * Opciones de ordenación declaradas con addOrderBy().
     * Cada elemento: ['fields' => string[], 'label' => string, 'default' => int].
     * El valor 'default': 0 = sin orden por defecto, 1 = ASC por defecto, 2 = DESC.
     */
    protected array $orderOptions = [];

    /**
     * Condiciones de coloreado de filas declaradas con addColor().
     * Cada elemento: ['field' => string, 'value' => mixed, 'color' => string, 'title' => string].
     * El color es una clase CSS de Bootstrap (p. ej. 'table-danger', 'table-success').
     */
    protected array $colorConditions = [];

    /**
     * Pestañas adicionales declaradas con addTab().
     * Cuando este array no está vacío, el controlador opera en modo multi-pestaña:
     * solo se cargan los registros de la pestaña activa y la plantilla muestra
     * una navegación de tabs en la cabecera.
     *
     * @var UIListTab[] indexado por nombre de pestaña
     */
    private array $tabs = [];

    /** Registros cargados desde la base de datos para la página actual. */
    protected array $records = [];

    /** Total de registros que coinciden con los filtros activos (sin paginar). */
    protected int $count = 0;

    /** Desplazamiento de la página actual. */
    protected int $offset = 0;

    /** Número máximo de registros por página. */
    protected int $limit = 50;

    /** Texto de búsqueda activo, extraído de la petición. */
    protected string $query = '';

    protected ExportManager $exportManager;

    /** WHERE y ORDER usados en loadRecords(), reutilizados en exportAction(). */
    protected array $lastWhere = [];
    protected array $lastOrder = [];

    /**
     * Devuelve el nombre de la clase del modelo a listar (sin namespace).
     * Ejemplo: 'FormaPago', 'Cliente'.
     */
    abstract public function getModelClassName(): string;

    /**
     * Declara las columnas, campos de búsqueda y opciones de ordenación.
     *
     * Usa addColumn(), addSearchField() y addOrderBy() aquí.
     * No cargues datos en este método; eso ocurre en loadRecords().
     */
    abstract protected function createUI(): void;

    public function privateCore(&$response, $user, $permissions): void
    {
        parent::privateCore($response, $user, $permissions);

        $this->exportManager = new ExportManager();
        $this->limit = defined('FS_ITEM_LIMIT') ? (int)FS_ITEM_LIMIT : 50;

        $this->createUI();
        $this->pipe('createUI');
        $this->applyPageOptions();

        $action = $this->request->inputOrQuery('action', '');

        if (false === $this->execPreviousAction($action)
            || false === $this->pipeFalse('execPreviousAction', $action)) {
            return;
        }

        if (!empty($this->tabs)) {
            $activeTabName = $this->activeTabName();
            foreach ($this->tabs as $tabName => $tab) {
                if ($tabName !== $activeTabName) {
                    $tab->loadCount();
                }
            }
            $activeTab = $this->activeTab();
            if ($activeTab !== null) {
                $activeTab->loadRecords($this->request, $this->limit);
            }
        } else {
            $this->loadRecords();
        }

        $this->pipeFalse('loadData', $this->records);

        $this->execAfterAction($action);
        $this->pipeFalse('execAfterAction', $action);

        if ($this->getTemplate() !== false) {
            $this->setTemplate($this->resolveTemplate());
        }
    }

    /**
     * Registra una pestaña en el controlador multi-pestaña.
     *
     * Devuelve el UIListTab para que la subclase le añada columnas, búsqueda y ordenación.
     * Si se llama al menos una vez, el controlador opera en modo multi-pestaña.
     */
    public function addTab(
        string $name,
        string $modelClassName,
        string $titleKey,
        string $icon = 'fa-solid fa-list'
    ): UIListTab {
        $tab = UIListTab::make($name, $modelClassName, $titleKey, $icon);
        $this->tabs[$name] = $tab;
        return $tab;
    }

    /**
     * Devuelve todas las pestañas registradas, indexadas por nombre.
     * Usado por la plantilla para construir la navegación.
     */
    public function tabs(): array
    {
        return $this->tabs;
    }

    /**
     * Devuelve el nombre de la pestaña activa según el parámetro activetab.
     * Si no hay pestañas o el valor no es válido, devuelve el nombre de la primera pestaña.
     */
    public function activeTabName(): string
    {
        if (empty($this->tabs)) {
            return '';
        }

        $requested = $this->request->inputOrQuery('activetab', '');
        if (isset($this->tabs[$requested])) {
            return $requested;
        }

        return array_key_first($this->tabs);
    }

    /**
     * Devuelve la instancia UIListTab activa, o null si no hay pestañas.
     */
    public function activeTab(): ?UIListTab
    {
        $name = $this->activeTabName();
        return $name !== '' ? ($this->tabs[$name] ?? null) : null;
    }

    protected function resolveTemplate(): string
    {
        return 'Master/UIListController';
    }

    /**
     * Acciones ejecutadas antes de cargar los datos.
     *
     * Maneja: 'autocomplete' (devuelve JSON y aborta), 'delete' (elimina registros).
     * Devuelve false para interrumpir el ciclo de vida (cuando ya se envió respuesta).
     */
    protected function execPreviousAction(string $action): bool
    {
        switch ($action) {
            case 'autocomplete':
                $this->setTemplate(false);
                $this->response->json($this->autocompleteAction());
                return false;

            case 'delete':
                $this->deleteAction();
                break;
        }

        return true;
    }

    /**
     * Devuelve el nombre de clase del modelo a usar en deleteAction().
     *
     * En modo multi-pestaña, usa el modelo de la pestaña activa.
     * En modo single-tab, usa getModelClassName().
     */
    protected function activeModelClassName(): string
    {
        if (!empty($this->tabs)) {
            $tab = $this->activeTab();
            if ($tab !== null) {
                return $tab->modelClassName();
            }
        }
        return $this->getModelClassName();
    }

    /**
     * Acciones ejecutadas después de cargar los datos.
     *
     * Maneja: 'delete-ok' (muestra notificación de éxito), 'export' (PDF/XLS/CSV/MAIL).
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
        }
    }

    protected function exportAction(): void
    {
        if (false === $this->permissions->allowExport) {
            Tools::log()->warning('no-print-permission');
            return;
        }

        $option = $this->request->queryOrInput('option', '');

        if (!empty($this->tabs)) {
            $tab   = $this->activeTab();
            $model = new (self::MODEL_NAMESPACE . $tab->modelClassName())();
            $where = $tab->lastWhere();
            $order = $tab->lastOrder();
            $cols  = array_keys($tab->columns());
        } else {
            $model = new (self::MODEL_NAMESPACE . $this->getModelClassName())();
            $where = $this->lastWhere;
            $order = $this->lastOrder;
            $cols  = array_keys($this->columns);
        }

        $this->setTemplate(false);
        $this->exportManager->newDoc($option, $this->title);
        $this->exportManager->addListModelPage($model, $where, $order, 0, $cols, $this->title);
        $this->exportManager->show($this->response);
    }

    /**
     * Registra un FieldComponent como columna de la tabla.
     *
     * El fieldname del componente determina qué propiedad del modelo se muestra en la celda.
     * setCols() en el componente no afecta a la anchura de columna en modo tabla.
     */
    protected function addColumn(FieldComponent $component): FieldComponent
    {
        $this->columns[$component->fieldname()] = $component;
        return $component;
    }

    /**
     * Declara uno o varios campos de la tabla sobre los que actúa la búsqueda.
     *
     * Internamente se combinan con '|' para construir un DataBaseWhere con OR implícito.
     */
    protected function addSearchField(string ...$fields): void
    {
        foreach ($fields as $field) {
            $this->searchFields[] = $field;
        }
    }

    /**
     * Declara una opción de ordenación para la cabecera de la tabla.
     *
     * @param array  $fields Columnas de la BD por las que ordenar (se aplican en orden).
     * @param string $label  Clave de traducción o texto de la etiqueta visible.
     * @param int    $default 0 = sin activar, 1 = ASC por defecto, 2 = DESC por defecto.
     */
    protected function addOrderBy(array $fields, string $label, int $default = 0): void
    {
        $this->orderOptions[] = [
            'fields'  => $fields,
            'label'   => $label,
            'default' => $default,
        ];
    }

    /**
     * Añade una condición de coloreado de fila.
     *
     * Cuando el campo $field del registro tiene el valor $value, la fila recibe la clase
     * Bootstrap $color (p. ej. 'table-danger'). Las condiciones se evalúan en orden;
     * la primera que coincida gana.
     */
    protected function addColor(string $field, mixed $value, string $color, string $title = ''): void
    {
        $this->colorConditions[] = [
            'field' => $field,
            'value' => $value,
            'color' => $color,
            'title' => $title,
        ];
    }

    /**
     * Calcula la clase CSS de Bootstrap para la fila de un registro según colorConditions.
     *
     * Devuelve una cadena vacía si ninguna condición aplica.
     */
    public function rowClass(object $record): string
    {
        foreach ($this->colorConditions as $cond) {
            $field = $cond['field'];
            if (property_exists($record, $field) && (string)$record->{$field} === (string)$cond['value']) {
                return $cond['color'];
            }
        }

        return '';
    }

    /**
     * Carga los registros del modelo aplicando búsqueda, orden y paginación.
     *
     * Extrae 'query', 'offset' y 'order' de la petición, construye los DataBaseWhere
     * correspondientes y ejecuta la consulta. Los resultados quedan en $this->records.
     */
    protected function loadRecords(): void
    {
        $modelClass = self::MODEL_NAMESPACE . $this->getModelClassName();
        if (!class_exists($modelClass)) {
            return;
        }

        $model = new $modelClass();

        $where = $this->permissions->onlyOwnerData ? $this->getOwnerFilter($model) : [];

        $this->readFilterValues($this->request);
        $where = array_merge($where, $this->buildFilterWhere());

        $this->query = $this->request->inputOrQuery('query', '');
        if (!empty($this->query) && !empty($this->searchFields)) {
            $where[] = new DataBaseWhere(
                implode('|', $this->searchFields),
                $this->query,
                'LIKE'
            );
        }

        $this->offset = max(0, (int)$this->request->inputOrQuery('offset', 0));
        $order = $this->resolveOrder();

        $this->lastWhere = $where;
        $this->lastOrder = $order;
        $this->count   = $model->count($where);
        $this->records = $model->all($where, $order, $this->offset, $this->limit);
    }

    /**
     * Determina la ordenación activa a partir del parámetro 'order' de la petición
     * y las opciones declaradas con addOrderBy().
     *
     * Devuelve un array ['campo' => 'ASC'|'DESC'] listo para pasarlo a model->all().
     */
    protected function resolveOrder(): array
    {
        $orderIndex = (int)$this->request->inputOrQuery('order', -1);

        if (isset($this->orderOptions[$orderIndex])) {
            $opt = $this->orderOptions[$orderIndex];
            $dir = ($orderIndex % 2 === 0) ? 'ASC' : 'DESC';
            $order = [];
            foreach ($opt['fields'] as $field) {
                $order[$field] = $dir;
            }
            return $order;
        }

        // Usar la primera opción marcada como default
        foreach ($this->orderOptions as $opt) {
            if ($opt['default'] > 0) {
                $dir = $opt['default'] === 2 ? 'DESC' : 'ASC';
                $order = [];
                foreach ($opt['fields'] as $field) {
                    $order[$field] = $dir;
                }
                return $order;
            }
        }

        return [];
    }

    /**
     * Elimina el registro (o registros) indicados en la petición.
     *
     * Comprueba permisos y token de formulario antes de actuar. Soporta eliminación
     * masiva mediante el array 'codes' y eliminación individual mediante 'code'.
     */
    protected function deleteAction(): bool
    {
        if (false === $this->permissions->allowDelete) {
            Tools::log()->warning('not-allowed-delete');
            return false;
        }

        if (false === $this->validateFormToken()) {
            return false;
        }

        $modelClass = self::MODEL_NAMESPACE . $this->activeModelClassName();
        $model = new $modelClass();

        $codes = $this->request->request->getArray('codes');
        $code  = $this->request->input('code');

        if (empty($codes) && empty($code)) {
            Tools::log()->warning('no-selected-item');
            return false;
        }

        if (!empty($codes)) {
            $this->dataBase->beginTransaction();
            $deleted = 0;

            foreach ($codes as $cod) {
                if ($model->loadFromCode($cod) && $model->delete()) {
                    $deleted++;
                    continue;
                }
                $this->dataBase->rollback();
                Tools::log()->warning('record-deleted-error');
                $model->clear();
                return false;
            }

            $model->clear();
            $this->dataBase->commit();

            if ($deleted > 0) {
                Tools::log()->notice('record-deleted-correctly');
                return true;
            }
        } elseif ($model->loadFromCode($code) && $model->delete()) {
            Tools::log()->notice('record-deleted-correctly');
            $model->clear();
            return true;
        }

        Tools::log()->warning('record-deleted-error');
        $model->clear();
        return false;
    }

    /**
     * Construye el filtro de propietario para cuando el permiso onlyOwnerData está activo.
     *
     * Replica la lógica de BaseController::getOwnerFilter(): filtra por nick o por codagente
     * según las propiedades que tenga el modelo.
     */
    protected function getOwnerFilter(object $model): array
    {
        $where = [];

        if (property_exists($model, 'nick')) {
            $where[] = new DataBaseWhere('nick', $this->user->nick);
            $where[] = new DataBaseWhere('nick', null, 'IS', 'OR');
            if (property_exists($model, 'codagente') && $this->user->codagente) {
                $where[] = new DataBaseWhere('codagente', $this->user->codagente, '=', 'OR');
            }
            return $where;
        }

        if (property_exists($model, 'codagente')) {
            $where[] = new DataBaseWhere('codagente', $this->user->codagente);
        }

        return $where;
    }

    /**
     * Maneja la acción 'autocomplete': busca en el CodeModel y devuelve JSON.
     *
     * Replica el comportamiento de BaseController::autocompleteAction() para mantener
     * compatibilidad con los widgets select2 del frontend.
     */
    protected function autocompleteAction(): array
    {
        $source     = $this->request->queryOrInput('source', '');
        $fieldcode  = $this->request->queryOrInput('fieldcode', 'id');
        $fieldtitle = $this->request->queryOrInput('fieldtitle', $fieldcode);
        $term       = $this->request->queryOrInput('term', '');
        $strict     = $this->request->queryOrInput('strict', '1');

        if (empty($source)) {
            return [];
        }

        $where = [];
        $fieldfilter = $this->request->queryOrInput('fieldfilter', '');
        foreach (DataBaseWhere::applyOperation($fieldfilter) as $field => $operation) {
            if (1 !== preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z_][a-zA-Z0-9_]*)?$/', $field)) {
                continue;
            }
            $value   = $this->request->queryOrInput($field);
            $where[] = new DataBaseWhere($field, $value, '=', $operation);
        }

        $codeModel = new CodeModel();
        $results   = [];

        foreach ($codeModel->search($source, $fieldcode, $fieldtitle, $term, $where) as $value) {
            $results[] = ['key' => Tools::fixHtml($value->code), 'value' => Tools::fixHtml($value->description)];
        }

        if (empty($results) && $strict === '0') {
            $results[] = ['key' => $term, 'value' => $term];
        } elseif (empty($results)) {
            $results[] = ['key' => null, 'value' => Tools::trans('no-data')];
        }

        return $results;
    }

    /**
     * Devuelve la URL de edición para un registro dado.
     *
     * La implementación base devuelve cadena vacía (sin enlace). Las subclases
     * deben sobreescribir este método para enlazar cada fila con su controlador
     * de edición correspondiente.
     */
    public function rowUrl(object $record): string
    {
        return '';
    }

    /**
     * Devuelve la URL para crear un nuevo registro.
     *
     * Si devuelve cadena vacía no se muestra el botón «Nuevo» en la cabecera.
     * Las subclases sobreescriben este método para apuntar a su controlador
     * de edición correspondiente sin parámetro de código.
     */
    public function newUrl(): string
    {
        return '';
    }

    /** Devuelve las columnas registradas, indexadas por fieldname. */
    public function columns(): array
    {
        return $this->columns;
    }

    /** Devuelve los registros de la página actual. */
    public function records(): array
    {
        return $this->records;
    }

    /** Devuelve el total de registros sin paginar. */
    public function count(): int
    {
        return $this->count;
    }

    /** Devuelve el desplazamiento actual (para la paginación). */
    public function offset(): int
    {
        return $this->offset;
    }

    /** Devuelve el límite de registros por página. */
    public function limit(): int
    {
        return $this->limit;
    }

    /** Devuelve el texto de búsqueda activo. */
    public function query(): string
    {
        return $this->query;
    }

    /** Devuelve los campos de búsqueda declarados. */
    public function searchFields(): array
    {
        return $this->searchFields;
    }

    /** Devuelve las opciones de ordenación declaradas. */
    public function orderOptions(): array
    {
        return $this->orderOptions;
    }

    /**
     * Devuelve el índice de la opción de ordenación activa, o -1 si no hay ninguna activa.
     *
     * Usado por la plantilla para marcar la opción del dropdown como activa y para
     * mantener el parámetro order en los enlaces de paginación.
     */
    public function orderIndex(): int
    {
        $index = (int) $this->request->inputOrQuery('order', -1);
        return isset($this->orderOptions[$index]) ? $index : -1;
    }

    public function isClickable(): bool
    {
        return false;
    }

    /**
     * Devuelve HTML de botones extra para la pestaña indicada.
     *
     * La implementación base retorna cadena vacía. Las subclases pueden sobreescribir
     * para añadir botones específicos por pestaña (p. ej. bloquear entradas, renumerar).
     * El HTML se renderiza crudo en el template con `{{ fsc.tabExtraButtons(tabName) | raw }}`.
     *
     * @param string $tabName Nombre de la pestaña activa en el loop del template.
     */
    public function tabExtraButtons(string $tabName): string
    {
        return '';
    }

    public function colorLegend(): string
    {
        $html = '';
        foreach ($this->colorConditions as $cond) {
            if (!empty($cond['title'])) {
                $label = Tools::lang()->trans($cond['title']);
                $textClass = str_replace('table-', 'text-', $cond['color']);
                $html .= '<span class="dropdown-item small">'
                    . '<i class="fa-solid fa-circle me-1 ' . htmlspecialchars($textClass) . '" aria-hidden="true"></i>'
                    . htmlspecialchars($label)
                    . '</span>';
            }
        }
        return $html;
    }

    /**
     * Lee la configuración de visibilidad de columnas guardada en pages_options
     * y la aplica a las columnas de cada pestaña registrada.
     *
     * El nombre de la pestaña (p. ej. 'ListFormaPago') coincide con el nombre
     * de la view XML del sistema antiguo, por lo que EditPageOption funciona de
     * forma transparente y sus cambios se reflejan aquí.
     */
    private function applyPageOptions(): void
    {
        $userLevel = (int)($this->user->level ?? 0);

        if (!empty($this->tabs)) {
            foreach ($this->tabs as $tabName => $tab) {
                $this->ensurePageOption($tabName, $tab->columns());
                [$displayMap, $orderMap] = $this->loadPageOptionMaps($tabName);
                $tab->applyColumnOptions($displayMap, $orderMap, $userLevel);
            }
            return;
        }

        // modo single: usar el nombre del propio controlador
        $pageName = $this->getPageData()['name'] ?? '';
        if (empty($pageName)) {
            return;
        }

        $this->ensurePageOption($pageName, $this->columns);
        [$displayMap, $orderMap] = $this->loadPageOptionMaps($pageName);

        foreach ($this->columns as $fieldname => $component) {
            if (isset($displayMap[$fieldname])) {
                $display = $displayMap[$fieldname];
                $component->setDisplay($display);
                if ($display !== 'none') {
                    $component->setAlign($display);
                }
            }
            if (isset($orderMap[$fieldname])) {
                $component->setOrder($orderMap[$fieldname]);
            }
            if ($component->level() > 0 && $userLevel < $component->level()) {
                $component->setDisplay('none');
            }
        }

        uasort($this->columns, fn($a, $b) => $a->order() <=> $b->order());
    }

    /**
     * Carga el PageOption para el nombre de vista dado y extrae dos mapas:
     * uno fieldname→display y otro fieldname→order.
     *
     * @return array{0: array<string,string>, 1: array<string,int>}
     */
    private function loadPageOptionMaps(string $viewName): array
    {
        $displayMap = [];
        $orderMap   = [];

        $pageOption = new PageOption();
        $where = [
            new DataBaseWhere('name', $viewName),
            new DataBaseWhere('nick', $this->user->nick),
            new DataBaseWhere('nick', null, 'IS', 'OR'),
        ];

        if (!$pageOption->loadWhere($where, ['nick' => 'ASC'])) {
            return [$displayMap, $orderMap];
        }

        foreach ((array)$pageOption->columns as $entry) {
            if (($entry['tag'] ?? '') === 'column') {
                // Columnas en nivel raíz (estructura generada por XMLView o installXML)
                $fieldname = $entry['children'][0]['fieldname'] ?? null;
                if ($fieldname !== null) {
                    $displayMap[$fieldname] = $entry['display'] ?? 'start';
                    if (isset($entry['order'])) {
                        $orderMap[$fieldname] = (int)$entry['order'];
                    }
                }
                continue;
            }

            // Columnas dentro de un grupo (estructura generada por ensurePageOption)
            foreach ((array)($entry['children'] ?? []) as $col) {
                $fieldname = $col['children'][0]['fieldname'] ?? null;
                if ($fieldname === null) {
                    continue;
                }
                $displayMap[$fieldname] = $col['display'] ?? 'start';
                if (isset($col['order'])) {
                    $orderMap[$fieldname] = (int)$col['order'];
                }
            }
        }

        return [$displayMap, $orderMap];
    }

    /**
     * Crea el PageOption por defecto (nick=null) para una vista si aún no existe en BD.
     *
     * Construye la estructura de columnas a partir de los FieldComponent registrados,
     * de modo que EditPageOption pueda mostrar y editar las columnas del UIListController
     * en lugar de intentar cargar el XML antiguo.
     *
     * @param string           $viewName Nombre de la vista / pestaña
     * @param FieldComponent[] $columns  Array fieldname → FieldComponent
     */
    private function ensurePageOption(string $viewName, array $columns): void
    {
        $pageOption = new PageOption();
        $where = [
            new DataBaseWhere('name', $viewName),
            new DataBaseWhere('nick', null, 'IS'),
        ];
        if ($pageOption->loadWhere($where)) {
            return;
        }

        $order = 10;
        $children = [];
        foreach ($columns as $fieldname => $component) {
            $schema = $component->schema();
            $children[$fieldname] = [
                'tag'      => 'column',
                'name'     => $fieldname,
                'title'    => $fieldname,
                'order'    => (string)($component->order() > 0 ? $component->order() : $order),
                'display'  => $component->isHidden() ? 'none' : 'start',
                'level'    => (string)$component->level(),
                'children' => [
                    [
                        'tag'       => 'widget',
                        'type'      => $schema['type'] ?? 'text',
                        'fieldname' => $fieldname,
                        'readonly'  => 'false',
                    ]
                ]
            ];
            $order += 10;
        }

        $pageOption->name    = $viewName;
        $pageOption->nick    = null;
        $pageOption->columns = [
            'main' => [
                'tag'      => 'group',
                'name'     => 'main',
                'title'    => '',
                'children' => $children,
            ]
        ];
        $pageOption->save();
    }
}
