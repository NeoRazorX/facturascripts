<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\AjaxForms;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Series;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\DocFilesTrait;
use FacturaScripts\Core\Lib\ExtendedController\LogAuditTrait;
use FacturaScripts\Core\Lib\ExtendedController\PanelController;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\Base\SalesDocumentLine;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\RoleAccess;
use FacturaScripts\Dinamic\Model\Variante;

/**
 * Description of SalesController
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
abstract class SalesController extends PanelController
{
    use DocFilesTrait;
    use LogAuditTrait;

    const MAIN_VIEW_NAME = 'main';
    const MAIN_VIEW_TEMPLATE = 'Tab/SalesDocument';

    private $logLevels = ['critical', 'error', 'info', 'notice', 'warning'];

    abstract public function getModelClassName();

    public function getModel(bool $reload = false): SalesDocument
    {
        if ($reload) {
            $this->views[static::MAIN_VIEW_NAME]->model->clear();
        }

        // loaded record? just return it
        if ($this->views[static::MAIN_VIEW_NAME]->model->primaryColumnValue()) {
            return $this->views[static::MAIN_VIEW_NAME]->model;
        }

        // get the record identifier
        $code = $this->request->get('code');
        if (empty($code)) {
            // empty identifier? Then sets initial parameters to the new record and return it
            $formData = $this->request->query->all();
            SalesHeaderHTML::apply($this->views[static::MAIN_VIEW_NAME]->model, $formData);
            SalesFooterHTML::apply($this->views[static::MAIN_VIEW_NAME]->model, $formData);
            return $this->views[static::MAIN_VIEW_NAME]->model;
        }

        // existing record
        $this->views[static::MAIN_VIEW_NAME]->model->loadFromCode($code);
        return $this->views[static::MAIN_VIEW_NAME]->model;
    }

    /**
     * @param SalesDocument $model
     * @param SalesDocumentLine[] $lines
     *
     * @return string
     */
    public function renderSalesForm(SalesDocument $model, array $lines): string
    {
        $url = empty($model->primaryColumnValue()) ? $this->url() : $model->url();

        return '<div id="salesFormHeader">' . SalesHeaderHTML::render($model) . '</div>'
            . '<div id="salesFormLines">' . SalesLineHTML::render($lines, $model) . '</div>'
            . '<div id="salesFormFooter">' . SalesFooterHTML::render($model) . '</div>'
            . SalesModalHTML::render($model, $url);
    }

    public function series(string $type = ''): array
    {
        if (empty($type)) {
            return Series::all();
        }

        $list = [];
        foreach (Series::all() as $serie) {
            if ($serie->tipo == $type) {
                $list[] = $serie;
            }
        }

        return $list;
    }

    protected function autocompleteProductAction(): bool
    {
        $this->setTemplate(false);

        $list = [];
        $variante = new Variante();
        $query = (string)$this->request->get('term');
        $where = [
            new DataBaseWhere('p.bloqueado', 0),
            new DataBaseWhere('p.sevende', 1)
        ];
        foreach ($variante->codeModelSearch($query, 'referencia', $where) as $value) {
            $list[] = [
                'key' => Tools::fixHtml($value->code),
                'value' => Tools::fixHtml($value->description)
            ];
        }

        if (empty($list)) {
            $list[] = ['key' => null, 'value' => Tools::lang()->trans('no-data')];
        }

        $this->response->setContent(json_encode($list));
        return false;
    }

    protected function createViews()
    {
        $this->setTabsPosition('top');
        $this->createViewsDoc();
        $this->createViewDocFiles();
        $this->createViewLogAudit();
    }

    protected function createViewsDoc()
    {
        $pageData = $this->getPageData();
        $this->addHtmlView(static::MAIN_VIEW_NAME, static::MAIN_VIEW_TEMPLATE, $this->getModelClassName(), $pageData['title'], 'fa-solid fa-file');
        AssetManager::addCss(FS_ROUTE . '/node_modules/jquery-ui-dist/jquery-ui.min.css', 2);
        AssetManager::addJs(FS_ROUTE . '/node_modules/jquery-ui-dist/jquery-ui.min.js', 2);
        SalesHeaderHTML::assets();
        SalesLineHTML::assets();
        SalesFooterHTML::assets();
    }

    protected function deleteDocAction(): bool
    {
        $this->setTemplate(false);

        // comprobamos los permisos
        if (false === $this->permissions->allowDelete) {
            Tools::log()->warning('not-allowed-delete');
            $this->sendJsonWithLogs(['ok' => false]);
            return false;
        }

        $model = $this->getModel();
        if (false === $model->delete()) {
            $this->sendJsonWithLogs(['ok' => false]);
            return false;
        }

        $this->sendJsonWithLogs(['ok' => true, 'newurl' => $model->url('list')]);
        return false;
    }

    /**
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'add-file':
                return $this->addFileAction();

            case 'autocomplete-product':
                return $this->autocompleteProductAction();

            case 'add-product':
            case 'fast-line':
            case 'fast-product':
            case 'new-line':
            case 'recalculate':
            case 'rm-line':
            case 'set-customer':
                return $this->recalculateAction(true);

            case 'delete-doc':
                return $this->deleteDocAction();

            case 'delete-file':
                return $this->deleteFileAction();

            case 'edit-file':
                return $this->editFileAction();

            case 'find-customer':
                return $this->findCustomerAction();

            case 'find-product':
                return $this->findProductAction();

            case 'recalculate-line':
                return $this->recalculateAction(false);

            case 'save-doc':
                $this->saveDocAction();
                return false;

            case 'save-paid':
                return $this->savePaidAction();

            case 'save-status':
                return $this->saveStatusAction();

            case 'unlink-file':
                return $this->unlinkFileAction();
        }

        return parent::execPreviousAction($action);
    }

    protected function exportAction()
    {
        $this->setTemplate(false);

        $subjectLang = $this->views[static::MAIN_VIEW_NAME]->model->getSubject()->langcode;
        $requestLang = $this->request->request->get('langcode');
        $langCode = $requestLang ?? $subjectLang ?? '';

        $this->exportManager->newDoc(
            $this->request->get('option', ''),
            $this->title,
            (int)$this->request->request->get('idformat', ''),
            $langCode
        );
        $this->exportManager->addBusinessDocPage($this->views[static::MAIN_VIEW_NAME]->model);
        $this->exportManager->show($this->response);
    }

    protected function findCustomerAction(): bool
    {
        $this->setTemplate(false);

        // ¿El usuario tiene permiso para ver todos los clientes?
        $showAll = false;
        foreach (RoleAccess::allFromUser($this->user->nick, 'EditCliente') as $access) {
            if (false === $access->onlyownerdata) {
                $showAll = true;
            }
        }
        $where = [];
        if ($this->permissions->onlyOwnerData && !$showAll) {
            $where[] = new DataBaseWhere('codagente', $this->user->codagente);
            $where[] = new DataBaseWhere('codagente', null, 'IS NOT');
        }

        $list = [];
        $customer = new Cliente();
        $term = $this->request->get('term');
        foreach ($customer->codeModelSearch($term, '', $where) as $item) {
            $list[$item->code] = $item->code . ' | ' . Tools::fixHtml($item->description);
        }
        $this->response->setContent(json_encode($list));
        return false;
    }

    protected function findProductAction(): bool
    {
        $this->setTemplate(false);
        $model = $this->getModel();
        $formData = json_decode($this->request->request->get('data'), true);
        SalesHeaderHTML::apply($model, $formData);
        SalesFooterHTML::apply($model, $formData);
        SalesModalHTML::apply($model, $formData);
        $content = [
            'header' => '',
            'lines' => '',
            'linesMap' => [],
            'footer' => '',
            'products' => SalesModalHTML::renderProductList()
        ];
        $this->sendJsonWithLogs($content);
        return false;
    }

    /**
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $code = $this->request->get('code');

        switch ($viewName) {
            case 'docfiles':
                $this->loadDataDocFiles($view, $this->getModelClassName(), $code);
                break;

            case 'ListLogMessage':
                $this->loadDataLogAudit($view, $this->getModelClassName(), $code);
                break;

            case static::MAIN_VIEW_NAME:
                if (empty($code)) {
                    $this->getModel(true);
                    break;
                }

                // data not found?
                $view->loadData($code);
                $action = $this->request->request->get('action', '');
                if ('' === $action && empty($view->model->primaryColumnValue())) {
                    Tools::log()->warning('record-not-found');
                    break;
                }

                $this->title .= ' ' . $view->model->primaryDescription();
                $view->settings['btnPrint'] = true;
                $this->addButton($viewName, [
                    'action' => 'CopyModel?model=' . $this->getModelClassName() . '&code=' . $view->model->primaryColumnValue(),
                    'icon' => 'fa-solid fa-cut',
                    'label' => 'copy',
                    'type' => 'link'
                ]);
                break;
        }
    }

    protected function recalculateAction(bool $renderLines): bool
    {
        $this->setTemplate(false);
        $model = $this->getModel();
        $lines = $model->getLines();
        $formData = json_decode($this->request->request->get('data'), true);
        SalesHeaderHTML::apply($model, $formData,);
        SalesFooterHTML::apply($model, $formData);
        SalesLineHTML::apply($model, $lines, $formData);
        Calculator::calculate($model, $lines, false);

        $content = [
            'header' => SalesHeaderHTML::render($model),
            'lines' => $renderLines ? SalesLineHTML::render($lines, $model) : '',
            'linesMap' => $renderLines ? [] : SalesLineHTML::map($lines, $model),
            'footer' => SalesFooterHTML::render($model),
            'products' => '',
        ];
        $this->sendJsonWithLogs($content);
        return false;
    }

    protected function saveDocAction(): bool
    {
        $this->setTemplate(false);

        // comprobamos los permisos
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            $this->sendJsonWithLogs(['ok' => false]);
            return false;
        }

        $this->dataBase->beginTransaction();

        $model = $this->getModel();
        $formData = json_decode($this->request->request->get('data'), true);
        SalesHeaderHTML::apply($model, $formData);
        SalesFooterHTML::apply($model, $formData);

        if (false === $model->save()) {
            $this->sendJsonWithLogs(['ok' => false]);
            $this->dataBase->rollback();
            return false;
        }

        $lines = $model->getLines();
        SalesLineHTML::apply($model, $lines, $formData);
        Calculator::calculate($model, $lines, false);

        foreach ($lines as $line) {
            if (false === $line->save()) {
                $this->sendJsonWithLogs(['ok' => false]);
                $this->dataBase->rollback();
                return false;
            }
        }

        // remove missing lines
        foreach ($model->getLines() as $oldLine) {
            if (in_array($oldLine->idlinea, SalesLineHTML::getDeletedLines()) && false === $oldLine->delete()) {
                $this->sendJsonWithLogs(['ok' => false]);
                $this->dataBase->rollback();
                return false;
            }
        }

        $lines = $model->getLines();
        if (false === Calculator::calculate($model, $lines, true)) {
            $this->sendJsonWithLogs(['ok' => false]);
            $this->dataBase->rollback();
            return false;
        }

        $this->sendJsonWithLogs(['ok' => true, 'newurl' => $model->url() . '&action=save-ok']);
        $this->dataBase->commit();
        return true;
    }

    protected function savePaidAction(): bool
    {
        $this->setTemplate(false);

        // comprobamos los permisos
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            $this->sendJsonWithLogs(['ok' => false]);
            return false;
        }

        // guardamos el documento
        if ($this->getModel()->editable && false === $this->saveDocAction()) {
            return false;
        }

        // si la factura es de 0 €, la marcamos como pagada
        $model = $this->getModel();
        if (empty($model->total) && property_exists($model, 'pagada')) {
            $model->pagada = (bool)$this->request->request->get('selectedLine');
            $model->save();
            $this->sendJsonWithLogs(['ok' => true, 'newurl' => $model->url() . '&action=save-ok']);
            return false;
        }

        // comprobamos si tiene recibos
        $receipts = $model->getReceipts();
        if (empty($receipts)) {
            Tools::log()->warning('invoice-has-no-receipts');
            $this->sendJsonWithLogs(['ok' => false]);
            return false;
        }

        // marcamos los recibos como pagados, eso marca la factura como pagada
        $formData = json_decode($this->request->request->get('data'), true);
        foreach ($receipts as $receipt) {
            $receipt->nick = $this->user->nick;
            // si no está pagado, actualizamos fechapago y codpago
            if (false == $receipt->pagado){
                $receipt->fechapago = $formData['fechapagorecibo'] ?? Tools::date();
                $receipt->codpago = $model->codpago;
            }
            $receipt->pagado = (bool)$this->request->request->get('selectedLine');
            if (false === $receipt->save()) {
                $this->sendJsonWithLogs(['ok' => false]);
                return false;
            }
        }

        $this->sendJsonWithLogs(['ok' => true, 'newurl' => $model->url() . '&action=save-ok']);
        return false;
    }

    protected function saveStatusAction(): bool
    {
        $this->setTemplate(false);

        // comprobamos los permisos
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            $this->sendJsonWithLogs(['ok' => false]);
            return false;
        }

        if ($this->getModel()->editable && false === $this->saveDocAction()) {
            return false;
        }

        $model = $this->getModel();
        $model->idestado = (int)$this->request->request->get('selectedLine');
        if (false === $model->save()) {
            $this->sendJsonWithLogs(['ok' => false]);
            return false;
        }

        $this->sendJsonWithLogs(['ok' => true, 'newurl' => $model->url() . '&action=save-ok']);
        return false;
    }

    private function sendJsonWithLogs(array $data): void
    {
        $data['messages'] = [];
        foreach (Tools::log()::read('', $this->logLevels) as $message) {
            if ($message['channel'] != 'audit') {
                $data['messages'][] = $message;
            }
        }

        $this->response->setContent(json_encode($data));
    }
}
