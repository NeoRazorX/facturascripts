<?php
/**
 * Copyright (C) 2021-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Core\Base\AjaxForms;

use FacturaScripts\Core\Base\Calculator;
use FacturaScripts\Core\DataSrc\Series;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\DocFilesTrait;
use FacturaScripts\Core\Lib\ExtendedController\LogAuditTrait;
use FacturaScripts\Core\Lib\ExtendedController\PanelController;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\Base\SalesDocumentLine;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Model\Cliente;

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
            SalesHeaderHTML::apply($this->views[static::MAIN_VIEW_NAME]->model, $formData, $this->user);
            SalesFooterHTML::apply($this->views[static::MAIN_VIEW_NAME]->model, $formData, $this->user);
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
        return '<div id="salesFormHeader">' . SalesHeaderHTML::render($model) . '</div>'
            . '<div id="salesFormLines">' . SalesLineHTML::render($lines, $model) . '</div>'
            . '<div id="salesFormFooter">' . SalesFooterHTML::render($model) . '</div>'
            . SalesModalHTML::render($model, $this->url());
    }

    public function series(): array
    {
        return Series::all();
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
        AssetManager::add('js', FS_ROUTE . '/Dinamic/Assets/JS/WidgetAutocomplete.js');
    }

    protected function deleteDocAction(): bool
    {
        $this->setTemplate(false);

        $model = $this->getModel();
        if (false === $model->delete()) {
            $this->response->setContent(
                json_encode(['ok' => false, 'messages' => self::toolBox()::log()::read('', $this->logLevels)])
            );
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

            case 'add-product':
            case 'fast-line':
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
                return $this->saveDocAction();

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
        $this->exportManager->newDoc(
            $this->request->get('option', ''),
            $this->title,
            (int)$this->request->request->get('idformat', ''),
            $this->request->request->get('langcode', '')
        );
        $this->exportManager->addBusinessDocPage($this->views[static::MAIN_VIEW_NAME]->model);
        $this->exportManager->show($this->response);
    }

    protected function findCustomerAction(): bool
    {
        $this->setTemplate(false);
        $customer = new Cliente();
        $list = [];
        $term = $this->request->get('term');
        foreach ($customer->codeModelSearch($term) as $item) {
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
        SalesHeaderHTML::apply($model, $formData, $this->user);
        SalesFooterHTML::apply($model, $formData, $this->user);
        SalesModalHTML::apply($model, $formData);
        $content = [
            'header' => '',
            'lines' => '',
            'linesMap' => [],
            'footer' => '',
            'products' => SalesModalHTML::renderProductList(),
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
        SalesHeaderHTML::apply($model, $formData, $this->user);
        SalesFooterHTML::apply($model, $formData, $this->user);
        SalesLineHTML::apply($model, $lines, $formData);
        Calculator::calculate($model, $lines, false);

        $content = [
            'header' => SalesHeaderHTML::render($model),
            'lines' => $renderLines ? SalesLineHTML::render($lines, $model) : '',
            'linesMap' => $renderLines ? [] : SalesLineHTML::map($lines, $model),
            'footer' => SalesFooterHTML::render($model),
            'products' => '',
            'messages' => self::toolBox()::log()::read('', $this->logLevels)
        ];
        $this->response->setContent(json_encode($content));
        return false;
    }

    protected function saveDocAction(): bool
    {
        $this->setTemplate(false);
        $this->dataBase->beginTransaction();

        $model = $this->getModel();
        $formData = json_decode($this->request->request->get('data'), true);
        SalesHeaderHTML::apply($model, $formData, $this->user);
        SalesFooterHTML::apply($model, $formData, $this->user);

        if (false === $model->save()) {
            $this->response->setContent(json_encode(['ok' => false, 'messages' => self::toolBox()::log()::read('', $this->logLevels)]));
            $this->dataBase->rollback();
            return false;
        }

        $lines = $model->getLines();
        SalesLineHTML::apply($model, $lines, $formData);
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
            if (in_array($oldLine->idlinea, SalesLineHTML::getDeletedLines()) && false === $oldLine->delete()) {
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
        return false;
    }

    protected function savePaidAction(): bool
    {
        $this->setTemplate(false);

        $model = $this->getModel();
        foreach ($model->getReceipts() as $receipt) {
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
