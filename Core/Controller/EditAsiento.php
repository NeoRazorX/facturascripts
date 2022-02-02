<?php
/**
 * Copyright (C) 2021 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\DocFilesTrait;
use FacturaScripts\Core\Lib\ExtendedController\LogAuditTrait;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Lib\ExtendedController\PanelController;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\Partida;
use FacturaScripts\Core\Lib\Accounting\AccountingFooterHTML;
use FacturaScripts\Core\Lib\Accounting\AccountingHeaderHTML;
use FacturaScripts\Core\Lib\Accounting\AccountingLineHTML;
use FacturaScripts\Core\Lib\Accounting\AccountingModalHTML;

/**
 * Description of EditAsiento
 *
 * @author Carlos Garcia Gomez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class EditAsiento extends PanelController
{

    use DocFilesTrait;
    use LogAuditTrait;

    const MAIN_VIEW_NAME = 'main';
    const MAIN_VIEW_TEMPLATE = 'Tab/AccountingEntry';

    private $logLevels = ['critical', 'error', 'info', 'notice', 'warning'];

    /**
     * Gets the main model and loads the data based on the primary key.
     *
     * @return Asiento
     */
    public function getModel()
    {
        // loaded record? just return it
        if ($this->views[static::MAIN_VIEW_NAME]->model->primaryColumnValue()) {
            return $this->views[static::MAIN_VIEW_NAME]->model;
        }

        // get the record identifier
        $primaryKey = $this->request->request->get($this->views[static::MAIN_VIEW_NAME]->model->primaryColumn());
        $code = $this->request->query->get('code', $primaryKey);
        if (empty($code)) {
            // new record
            return $this->views[static::MAIN_VIEW_NAME]->model;
        }

        // existing record
        $this->views[static::MAIN_VIEW_NAME]->model->loadFromCode($code);
        return $this->views[static::MAIN_VIEW_NAME]->model;
    }

    /**
     * Returns the class name of the main model.
     *
     * @return string
     */
    public function getModelClassName()
    {
        return 'Asiento';
    }

    /**
     * Return the basic data for this page.
     *
     * @return array
     */
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'accounting-entry';
        $data['icon'] = 'fas fa-balance-scale';
        $data['showonmenu'] = false;
        return $data;
    }

    /**
     * Gets the HTML code to render the main form.
     *
     * @param Asiento $model
     * @param Partida[] $lines
     *
     * @return string
     */
    public function renderAccEntryForm($model, $lines): string
    {
        return '<div id="accEntryFormHeader">' . AccountingHeaderHTML::render($model) . '</div>'
            . '<div id="accEntryFormLines">' . AccountingLineHTML::render($lines, $model) . '</div>'
            . '<div id="accEntryFormFooter">' . AccountingFooterHTML::render($model) . '</div>'
            . AccountingModalHTML::render($model);
    }

    /**
     * Apply the changes made to the form to the models.
     *
     * @param Asiento $model
     * @param Partida[] $lines
     * @param bool $applyModal
     */
    private function applyMainFormData(&$model, &$lines, $applyModal = false)
    {
        $formData = json_decode($this->request->request->get('data'), true);
        AccountingHeaderHTML::apply($model, $formData);
        AccountingFooterHTML::apply($model, $formData);
        AccountingLineHTML::apply($model, $lines, $formData);
        if ($applyModal) {
            AccountingModalHTML::apply($model, $formData);
        }
    }

    /**
     * Inserts the views or tabs to display.
     */
    protected function createViews()
    {
        $this->setTabsPosition('top');
        $this->createViewsMain();
        $this->createViewDocFiles();
        $this->createViewLogAudit();
    }

    /**
     * Add main view (Accounting)
     */
    private function createViewsMain()
    {
        $this->addHtmlView(static::MAIN_VIEW_NAME, static::MAIN_VIEW_TEMPLATE, $this->getModelClassName(), $this->title, 'fas fa-balance-scale');
        AssetManager::add('css', FS_ROUTE . '/node_modules/jquery-ui-dist/jquery-ui.min.css', 2);
        AssetManager::add('js', FS_ROUTE . '/node_modules/jquery-ui-dist/jquery-ui.min.js', 2);
        AssetManager::add('js', FS_ROUTE . '/Dinamic/Assets/JS/WidgetAutocomplete.js');
    }

    /**
     * Unlink the main model.
     *
     * @return bool
     */
    protected function deleteDocAction(): bool
    {
        $this->setTemplate(false);
        if (false === $this->permissions->allowDelete) {
            self::toolBox()::i18nLog()->warning('not-allowed-delete');
            return $this->sendJsonError();
        } elseif (false === $this->validateFileActionToken()) {
            return $this->sendJsonError();
        }

        $model = $this->getModel();
        if (false === $model->delete()) {
            return $this->sendJsonError();
        }

        $this->response->setContent(json_encode(['ok' => true, 'newurl' => $model->url('list')]));
        return false;
    }

    /**
     * Run the actions that alter data before reading it.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'add-file':
                return $this->addFileAction();

            case 'delete-file':
                return $this->deleteFileAction();

            case 'delete-doc':
                return $this->deleteDocAction();

            case 'edit-file':
                return $this->editFileAction();

            case 'find-subaccount':
                return $this->findSubaccountAction();

            case 'lock-doc':
                return $this->unlockAction(false);

            case 'new-line':
            case 'rm-line':
            case 'recalculate':
                return $this->recalculateAction($action != 'recalculate');

            case 'save-doc':
                return $this->saveDocAction();

            case 'unlink-file':
                return $this->unlinkFileAction();

            case 'unlock-doc':
                return $this->unlockAction(true);
        }

        return parent::execPreviousAction($action);
    }

    /**
     * Recalculate the list of ledger subaccounts.
     *
     * @return bool
     */
    protected function findSubaccountAction(): bool
    {
        $this->setTemplate(false);
        $model = $this->getModel();
        $lines = [];
        $this->applyMainFormData($model, $lines, true);
        $content = [
            'header' => '',
            'lines' => '',
            'footer' => '',
            'list' => AccountingModalHTML::renderSubaccountList($model),
            'messages' => self::toolBox()::log()::read('', $this->logLevels)
        ];
        $this->response->setContent(json_encode($content));
        return false;
    }

    /**
     * Load the data from the indicated view.
     *
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $primaryKey = $this->request->request->get($view->model->primaryColumn());
        $code = $this->request->query->get('code', $primaryKey);

        switch ($viewName) {
            case 'docfiles':
                $this->loadDataDocFiles($view, $this->getModelClassName(), $code);
                break;

            case 'ListLogMessage':
                $this->loadDataLogAudit($view, $this->getModelClassName(), $code);
                break;

            case static::MAIN_VIEW_NAME:
                if (empty($code)) {
                    $view->model->clear();
                    break;
                }

                // data not found?
                $view->loadData($code);
                $action = $this->request->request->get('action', '');
                if ('' === $action && false === $view->model->exists()) {
                    $this->toolBox()->i18nLog()->warning('record-not-found');
                    break;
                }

                $this->title .= ' ' . $view->model->primaryDescription();
                break;
        }
    }

    /**
     * Recalculate the models and get the new html code to
     * represent the data in the view.
     *
     * @param bool $renderLines
     *
     * @return bool
     */
    protected function recalculateAction(bool $renderLines): bool
    {
        $this->setTemplate(false);
        $model = $this->getModel();
        $lines = $model->getLines();
        $this->applyMainFormData($model, $lines);
        $content = [
            'header' => AccountingHeaderHTML::render($model),
            'lines' => $renderLines ? AccountingLineHTML::render($lines, $model) : '',
            'footer' => AccountingFooterHTML::render($model),
            'list' => '',
            'messages' => self::toolBox()::log()::read('', $this->logLevels)
        ];
        $this->response->setContent(json_encode($content));
        return false;
    }

    /**
     * Save the data in the database.
     *
     * @return bool
     */
    protected function saveDocAction(): bool
    {
        $this->setTemplate(false);
        if (false === $this->permissions->allowUpdate) {
            self::toolBox()::i18nLog()->warning('not-allowed-modify');
            return $this->sendJsonError();
        } elseif (false === $this->validateFileActionToken()) {
            return $this->sendJsonError();
        }

        $this->dataBase->beginTransaction();
        $model = $this->getModel();
        $lines = $model->getLines();
        $this->applyMainFormData($model, $lines);
        if (false === $model->save()) {
            $this->dataBase->rollback();
            return $this->sendJsonError();
        }

        foreach ($lines as $line) {
            $line->idasiento = $line->idasiento ?? $model->idasiento;
            if (false === $line->save()) {
                $this->dataBase->rollback();
                return $this->sendJsonError();
            }
        }

        // remove missing lines
        foreach ($model->getLines() as $oldLine) {
            if (in_array($oldLine->idpartida, AccountingLineHTML::getDeletedLines()) && false === $oldLine->delete()) {
                $this->dataBase->rollback();
                return $this->sendJsonError();
            }
        }

        $this->response->setContent(json_encode(['ok' => true, 'newurl' => $model->url() . '&action=save-ok']));
        $this->dataBase->commit();
        return false;
    }

    /**
     * @return bool
     */
    protected function sendJsonError(): bool
    {
        $this->response->setContent(json_encode(['ok' => false, 'messages' => self::toolBox()::log()::read('', $this->logLevels)]));
        return false;
    }

    /**
     * @param bool $value
     *
     * @return bool
     */
    protected function unlockAction(bool $value): bool
    {
        $this->setTemplate(false);
        if (false === $this->permissions->allowUpdate) {
            self::toolBox()::i18nLog()->warning('not-allowed-modify');
            return $this->sendJsonError();
        } elseif (false === $this->validateFileActionToken()) {
            return $this->sendJsonError();
        }

        $model = $this->getModel();
        $model->editable = $value;
        if (false === $model->save()) {
            return $this->sendJsonError();
        }

        $this->response->setContent(json_encode(['ok' => true, 'newurl' => $model->url() . '&action=save-ok']));
        return false;
    }
}
