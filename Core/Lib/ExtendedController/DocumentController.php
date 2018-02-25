<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Lib\ExtendedController;

use FacturaScripts\Core\Base\Cache;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\Translator;

/**
 * Description of DocumentController
 *
 * @author Carlos García Gómez
 */
abstract class DocumentController extends PanelController
{

    /**
     * Default item limit for selects.
     */
    const ITEM_SELECT_LIMIT = 500;

    /**
     * Constructor.
     *
     * @param Cache      $cache
     * @param Translator $i18n
     * @param MiniLog    $miniLog
     * @param string     $className
     */
    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);
        $this->setTemplate('Master/DocumentController');
    }

    /**
     * Load views and document.
     */
    protected function createViews()
    {
        $view = new DocumentView('new', $this->getDocumentClassName(), $this->getDocumentLineClassName(), $this->getLineXMLView(), $this->user->nick);
        $this->addView('Document', $view, 'fa-file');
    }

    /**
     * Run the actions that alter data before reading it.
     *
     * @param BaseView $view
     * @param string   $action
     *
     * @return bool
     */
    protected function execPreviousAction($view, $action)
    {
        switch ($action) {
            case 'calculate-document':
                $this->setTemplate(false);

                $data = $this->request->request->all();
                $result = $view->calculateDocument($data);
                $this->response->setContent($result);
                return false;

            case 'save-document':
                $this->setTemplate(false);

                $data = $this->request->request->all();
                $result = $view->saveDocument($data);
                $this->response->setContent($result);
                return false;

            default:
                return parent::execPreviousAction($view, $action);
        }
    }

    /**
     * Run the controller after actions
     *
     * @param EditView $view
     * @param string $action
     */
    protected function execAfterAction($view, $action)
    {
        if ($action === 'export') {
            $this->setTemplate(false);
            $this->exportManager->newDoc($this->request->get('option'));
            foreach ($this->views as $selectedView) {
                $selectedView->export($this->exportManager);
                break;
            }
            $this->exportManager->show($this->response);
        } else {
            parent::execAfterAction($view, $action);
        }
    }

    /**
     * Load view data procedure
     *
     * @param string   $keyView
     * @param BaseView $view
     */
    protected function loadData($keyView, $view)
    {
        $iddoc = $this->request->get('code', '');
        if ($keyView === 'Document' && !empty($iddoc)) {
            $view->loadData($iddoc);
        }
    }

    /**
     * Return the document class name.
     *
     * @return string
     */
    abstract protected function getDocumentClassName();

    /**
     * Return the document line class name.
     *
     * @return string
     */
    abstract protected function getDocumentLineClassName();

    /**
     * Return the name of the xml file with the column configuration por lines.
     *
     * @return string
     */
    protected function getLineXMLView()
    {
        return 'CommonLineasDocumento';
    }

    /**
     * Returns an array with all data from selected model.
     *
     * @param string $modelName
     *
     * @return mixed
     */
    public function getSelectValues($modelName)
    {
        $values = [];
        $modelName = '\FacturaScripts\Dinamic\Model\\' . $modelName;
        $model = new $modelName();

        $order = [$model->primaryDescriptionColumn() => 'ASC'];
        foreach ($model->all([], $order, 0, self::ITEM_SELECT_LIMIT) as $newModel) {
            $values[$newModel->primaryColumnValue()] = $newModel->primaryDescription();
        }

        return $values;
    }
}
