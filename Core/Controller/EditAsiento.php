<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Lib\AjaxForms\AccountingFooterHTML;
use FacturaScripts\Core\Lib\AjaxForms\AccountingHeaderHTML;
use FacturaScripts\Core\Lib\AjaxForms\AccountingLineHTML;
use FacturaScripts\Core\Lib\AjaxForms\AccountingModalHTML;
use FacturaScripts\Core\Lib\Export\AsientoExport;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\DocFilesTrait;
use FacturaScripts\Core\Lib\ExtendedController\LogAuditTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Lib\ExtendedController\PanelController;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\Partida;

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
    public function getModel(): Asiento
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

    public function getModelClassName(): string
    {
        return 'Asiento';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'accounting-entry';
        $data['icon'] = 'fa-solid fa-balance-scale';
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
    public function renderAccEntryForm(Asiento $model, array $lines): string
    {
        AccountingLineHTML::calculateUnbalance($model, $lines);
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
    private function applyMainFormData(Asiento &$model, array &$lines, bool $applyModal = false)
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
        $this->addHtmlView(
            static::MAIN_VIEW_NAME,
            static::MAIN_VIEW_TEMPLATE,
            $this->getModelClassName(),
            'accounting-entry',
            'fa-solid fa-balance-scale'
        );

        // activamos el botón de imprimir
        $this->setSettings(static::MAIN_VIEW_NAME, 'btnPrint', true);

        // cargamos css y javascript
        AssetManager::addCss(FS_ROUTE . '/node_modules/jquery-ui-dist/jquery-ui.min.css', 2);
        AssetManager::addJs(FS_ROUTE . '/node_modules/jquery-ui-dist/jquery-ui.min.js', 2);
        AssetManager::addJs(FS_ROUTE . '/Dinamic/Assets/JS/WidgetAutocomplete.js');
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
            Tools::log()->warning('not-allowed-delete');
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

    protected function exportAction()
    {
        if (false === $this->views[$this->active]->settings['btnPrint'] || false === $this->permissions->allowExport) {
            Tools::log()->warning('no-print-permission');
            return;
        }

        $this->setTemplate(false);
        AsientoExport::show(
            $this->getModel(),
            $this->request->get('option', ''),
            $this->title,
            (int)$this->request->request->get('idformat', ''),
            $this->request->request->get('langcode', ''),
            $this->response
        );
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
            'messages' => Tools::log()::read('', $this->logLevels)
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
                    Tools::log()->warning('record-not-found');
                    break;
                }

                // unbalanced?
                if (false === $view->model->isBalanced()) {
                    Tools::log()->warning('unbalanced-entry');
                    break;
                }

                $this->title .= ' ' . $view->model->primaryDescription();
                $this->addButton($viewName, [
                    'action' => 'CopyModel?model=' . $this->getModelClassName() . '&code=' . $view->model->primaryColumnValue(),
                    'icon' => 'fa-solid fa-cut',
                    'label' => 'copy',
                    'type' => 'link'
                ]);
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
            'messages' => Tools::log()::read('', $this->logLevels)
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
            Tools::log()->warning('not-allowed-modify');
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

    protected function sendJsonError(): bool
    {
        $this->response->setContent(json_encode(['ok' => false, 'messages' => Tools::log()::read('', $this->logLevels)]));
        return false;
    }

    protected function unlockAction(bool $value): bool
    {
        $this->setTemplate(false);
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
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
