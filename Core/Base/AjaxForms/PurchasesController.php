<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Base\AjaxForms;

use FacturaScripts\Core\Base\Calculator;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Series;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\DocFilesTrait;
use FacturaScripts\Core\Lib\ExtendedController\LogAuditTrait;
use FacturaScripts\Core\Lib\ExtendedController\PanelController;
use FacturaScripts\Core\Model\Base\PurchaseDocument;
use FacturaScripts\Core\Model\Base\PurchaseDocumentLine;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Dinamic\Model\Variante;

/**
 * Description of PurchasesController
 *
 * @author Carlos Garcia Gomez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
abstract class PurchasesController extends PanelController
{
    use DocFilesTrait;
    use LogAuditTrait;

    const MAIN_VIEW_NAME = 'main';
    const MAIN_VIEW_TEMPLATE = 'Tab/PurchasesDocument';

    private $logLevels = ['critical', 'error', 'info', 'notice', 'warning'];

    abstract public function getModelClassName();

    public function getModel(bool $reload = false): PurchaseDocument
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
            PurchasesHeaderHTML::apply($this->views[static::MAIN_VIEW_NAME]->model, $formData, $this->user);
            return $this->views[static::MAIN_VIEW_NAME]->model;
        }

        // existing record
        $this->views[static::MAIN_VIEW_NAME]->model->loadFromCode($code);
        return $this->views[static::MAIN_VIEW_NAME]->model;
    }

    /**
     * @param PurchaseDocument $model
     * @param PurchaseDocumentLine[] $lines
     *
     * @return string
     */
    public function renderPurchasesForm(PurchaseDocument $model, array $lines): string
    {
        return '<div id="purchasesFormHeader">' . PurchasesHeaderHTML::render($model) . '</div>'
            . '<div id="purchasesFormLines">' . PurchasesLineHTML::render($lines, $model) . '</div>'
            . '<div id="purchasesFormFooter">' . PurchasesFooterHTML::render($model) . '</div>'
            . PurchasesModalHTML::render($model, $this->url());
    }

    public function series(): array
    {
        return Series::all();
    }

    protected function autocompleteProductAction(): bool
    {
        $this->setTemplate(false);

        $list = [];
        $variante = new Variante();
        $query = (string)$this->request->get('term');
        $where = [
            new DataBaseWhere('p.bloqueado', 0),
            new DataBaseWhere('p.secompra', 1)
        ];
        foreach ($variante->codeModelSearch($query, 'referencia', $where) as $value) {
            $list[] = [
                'key' => $this->toolBox()->utils()->fixHtml($value->code),
                'value' => $this->toolBox()->utils()->fixHtml($value->description)
            ];
        }

        if (empty($list)) {
            $list[] = ['key' => null, 'value' => $this->toolBox()->i18n()->trans('no-data')];
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
        $this->addHtmlView(static::MAIN_VIEW_NAME, static::MAIN_VIEW_TEMPLATE, $this->getModelClassName(), $pageData['title'], 'fas fa-file');
        AssetManager::add('css', FS_ROUTE . '/node_modules/jquery-ui-dist/jquery-ui.min.css', 2);
        AssetManager::add('js', FS_ROUTE . '/node_modules/jquery-ui-dist/jquery-ui.min.js', 2);
        PurchasesHeaderHTML::assets();
        PurchasesLineHTML::assets();
        PurchasesFooterHTML::assets();
    }

    protected function deleteDocAction(): bool
    {
        $this->setTemplate(false);

        // comprobamos los permisos
        if (false === $this->permissions->allowDelete) {
            self::toolBox()::i18nLog()->warning('not-allowed-delete');
            $this->response->setContent(
                json_encode(['ok' => false, 'messages' => self::toolBox()::log()::read('', $this->logLevels)])
            );
            return false;
        }

        $model = $this->getModel();
        if (false === $model->delete()) {
            $this->response->setContent(json_encode(['ok' => false, 'messages' => self::toolBox()::log()::read('', $this->logLevels)]));
            return false;
        }

        $this->response->setContent(json_encode(['ok' => true, 'newurl' => $model->url('list')]));
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
            case 'set-supplier':
                return $this->recalculateAction(true);

            case 'delete-doc':
                return $this->deleteDocAction();

            case 'delete-file':
                return $this->deleteFileAction();

            case 'edit-file':
                return $this->editFileAction();

            case 'find-supplier':
                return $this->findSupplierAction();

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

    protected function findSupplierAction(): bool
    {
        $this->setTemplate(false);
        $supplier = new Proveedor();
        $list = [];
        $term = $this->request->get('term');
        foreach ($supplier->codeModelSearch($term) as $item) {
            $list[$item->code] = $item->code . ' | ' . $this->toolBox()->utils()->fixHtml($item->description);
        }
        $this->response->setContent(json_encode($list));
        return false;
    }

    protected function findProductAction(): bool
    {
        $this->setTemplate(false);
        $model = $this->getModel();
        $formData = json_decode($this->request->request->get('data'), true);
        PurchasesHeaderHTML::apply($model, $formData, $this->user);
        PurchasesFooterHTML::apply($model, $formData, $this->user);
        PurchasesModalHTML::apply($model, $formData);
        $content = [
            'header' => '',
            'lines' => '',
            'linesMap' => [],
            'footer' => '',
            'products' => PurchasesModalHTML::renderProductList(),
            'messages' => self::toolBox()::log()::read('', $this->logLevels)
        ];
        $this->response->setContent(json_encode($content));
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
                    $this->toolBox()->i18nLog()->warning('record-not-found');
                    break;
                }

                $this->title .= ' ' . $view->model->primaryDescription();
                $view->settings['btnPrint'] = true;
                $this->addButton($viewName, [
                    'action' => 'CopyModel?model=' . $this->getModelClassName() . '&code=' . $view->model->primaryColumnValue(),
                    'icon' => 'fas fa-cut',
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
        PurchasesHeaderHTML::apply($model, $formData, $this->user);
        PurchasesFooterHTML::apply($model, $formData, $this->user);
        PurchasesLineHTML::apply($model, $lines, $formData);
        Calculator::calculate($model, $lines, false);

        $content = [
            'header' => PurchasesHeaderHTML::render($model),
            'lines' => $renderLines ? PurchasesLineHTML::render($lines, $model) : '',
            'linesMap' => $renderLines ? [] : PurchasesLineHTML::map($lines, $model),
            'footer' => PurchasesFooterHTML::render($model),
            'products' => '',
            'messages' => self::toolBox()::log()::read('', $this->logLevels)
        ];
        $this->response->setContent(json_encode($content));
        return false;
    }

    protected function saveDocAction(): bool
    {
        $this->setTemplate(false);

        // comprobamos los permisos
        if (false === $this->permissions->allowUpdate) {
            self::toolBox()::i18nLog()->warning('not-allowed-modify');
            $this->response->setContent(
                json_encode(['ok' => false, 'messages' => self::toolBox()::log()::read('', $this->logLevels)])
            );
            return false;
        }

        $this->dataBase->beginTransaction();

        $model = $this->getModel();
        $formData = json_decode($this->request->request->get('data'), true);
        PurchasesHeaderHTML::apply($model, $formData, $this->user);
        PurchasesFooterHTML::apply($model, $formData, $this->user);

        if (false === $model->save()) {
            $this->response->setContent(json_encode(['ok' => false, 'messages' => self::toolBox()::log()::read('', $this->logLevels)]));
            $this->dataBase->rollback();
            return false;
        }

        $lines = $model->getLines();
        PurchasesLineHTML::apply($model, $lines, $formData);
        Calculator::calculate($model, $lines, false);

        foreach ($lines as $line) {
            if (false === $line->save()) {
                $this->response->setContent(json_encode(['ok' => false, 'messages' => self::toolBox()::log()::read('', $this->logLevels)]));
                $this->dataBase->rollback();
                return false;
            }
        }

        // remove missing lines
        foreach ($model->getLines() as $oldLine) {
            if (in_array($oldLine->idlinea, PurchasesLineHTML::getDeletedLines()) && false === $oldLine->delete()) {
                $this->response->setContent(json_encode(['ok' => false, 'messages' => self::toolBox()::log()::read('', $this->logLevels)]));
                $this->dataBase->rollback();
                return false;
            }
        }

        if (false === $model->save()) {
            $this->response->setContent(json_encode(['ok' => false, 'messages' => self::toolBox()::log()::read('', $this->logLevels)]));
            $this->dataBase->rollback();
            return false;
        }

        $this->response->setContent(json_encode(['ok' => true, 'newurl' => $model->url() . '&action=save-ok']));
        $this->dataBase->commit();
        return true;
    }

    protected function savePaidAction(): bool
    {
        $this->setTemplate(false);

        // comprobamos los permisos
        if (false === $this->permissions->allowUpdate) {
            self::toolBox()::i18nLog()->warning('not-allowed-modify');
            $this->response->setContent(
                json_encode(['ok' => false, 'messages' => self::toolBox()::log()::read('', $this->logLevels)])
            );
            return false;
        }

        // si la factura es de 0 €, la marcamos como pagada
        $model = $this->getModel();
        if (empty($model->total) && property_exists($model, 'pagada')) {
            $model->pagada = (bool)$this->request->request->get('selectedLine');
            $model->save();
            $this->response->setContent(json_encode(['ok' => true, 'newurl' => $model->url() . '&action=save-ok']));
            return false;
        }

        // comprobamos si tiene recibos
        $receipts = $model->getReceipts();
        if (empty($receipts)) {
            self::toolBox()::i18nLog()->warning('invoice-has-no-receipts');
            $this->response->setContent(json_encode(['ok' => false, 'messages' => self::toolBox()::log()::read('', $this->logLevels)]));
            return false;
        }

        // marcamos los recibos como pagados, eso marcará la factura como pagada
        foreach ($receipts as $receipt) {
            $receipt->nick = $this->user->nick;
            $receipt->pagado = (bool)$this->request->request->get('selectedLine');
            if (false === $receipt->save()) {
                $this->response->setContent(json_encode(['ok' => false, 'messages' => self::toolBox()::log()::read('', $this->logLevels)]));
                return false;
            }
        }

        $this->response->setContent(json_encode(['ok' => true, 'newurl' => $model->url() . '&action=save-ok']));
        return false;
    }

    protected function saveStatusAction(): bool
    {
        $this->setTemplate(false);

        // comprobamos los permisos
        if (false === $this->permissions->allowUpdate) {
            self::toolBox()::i18nLog()->warning('not-allowed-modify');
            $this->response->setContent(
                json_encode(['ok' => false, 'messages' => self::toolBox()::log()::read('', $this->logLevels)])
            );
            return false;
        }

        if ($this->getModel()->editable && false === $this->saveDocAction()) {
            return false;
        }

        $model = $this->getModel();
        $model->idestado = (int)$this->request->request->get('selectedLine');
        if (false === $model->save()) {
            $this->response->setContent(json_encode(['ok' => false, 'messages' => self::toolBox()::log()::read('', $this->logLevels)]));
            return false;
        }

        $this->response->setContent(json_encode(['ok' => true, 'newurl' => $model->url() . '&action=save-ok']));
        return false;
    }
}
