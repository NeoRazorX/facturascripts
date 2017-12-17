<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Base\ExtendedController;

use FacturaScripts\Core\Model\Base;
use FacturaScripts\Core\Model\PageOption;

/**
 * Description of DocumentController
 *
 * @author Carlos García Gómez
 */
abstract class DocumentController extends PanelController
{

    /**
     * Header of document.
     *
     * @var Base\DocumentoVenta|Base\DocumentoCompra
     */
    public $document;

    /**
     * Line columns from xmlview.
     * 
     * @var array
     */
    private $lineOptions;

    /**
     * Lines of document, the body.
     *
     * @var Base\LineaDocumentoVenta[]|Base\LineaDocumentoCompra[]
     */
    public $lines;

    /**
     * Constructor.
     * 
     * @param Cache $cache
     * @param Translator $i18n
     * @param MiniLog $miniLog
     * @param string $className
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
        $className = $this->getDocumentClassName();
        $this->document = new $className();
        $this->lines = [];

        $iddoc = $this->request->get('code');
        if ($iddoc !== null && $iddoc !== '') {
            $this->document->loadFromCode($iddoc);
            if ($this->document) {
                $this->lines = $this->document->getLineas();
            }
        }

        $this->loadPrimaryTabOptions();
    }

    private function loadPrimaryTabOptions()
    {
        $PageOptions = new PageOption();
        $PageOptions->getForUser($this->getLineXMLView(), $this->user->nick);

        foreach ($PageOptions->columns['root']->columns as $col) {
            $this->lineOptions[] = $col;
        }
    }

    /**
     * Run the actions that alter data before reading it.
     *
     * @param BaseView $view
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($view, $action)
    {
        if ($action === 'delete-doc') {
            if ($this->document->delete()) {
                $this->document->clear();
                $this->lines = [];
                $this->miniLog->notice($this->i18n->trans('record-deleted-correctly'));
                return true;
            }

            return false;
        }

        return parent::execPreviousAction($view, $action);
    }

    /**
     * Load view data procedure
     *
     * @param string $keyView
     * @param BaseView $view
     */
    protected function loadData($keyView, $view)
    {
        /// Implement in children
    }

    /**
     * Run the controller after actions
     *
     * @param EditView $view
     * @param string $action
     */
    protected function execAfterAction($view, $action)
    {
        switch ($action) {
            case 'export':
                $this->setTemplate(false);
                $this->exportManager->newDoc($this->response, $this->request->get('option'));
                $this->exportManager->generateDocumentPage($this->document);
                $this->exportManager->show($this->response);
                break;
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
     * Returns the line headers.
     *
     * @return string
     */
    public function getLineHeaders()
    {
        $headers = [];
        foreach ($this->lineOptions as $col) {
            $headers[] = $this->i18n->trans($col->title);
        }

        return json_encode($headers);
    }

    /**
     * Returns the line columns.
     *
     * @return string
     */
    public function getLineColumns()
    {
        $moneyFormat = '0.';
        for ($num = 0; $num < FS_NF0; $num++) {
            $moneyFormat .= '0';
        }

        $columns = [];
        foreach ($this->lineOptions as $col) {
            $item = [
                'data' => $col->widget->fieldName,
                'type' => $col->widget->type,
            ];
            if ($item['type'] === 'number' || $item['type'] === 'money') {
                $item['type'] = 'numeric';
                $item['format'] = $moneyFormat;
            }

            $columns[] = $item;
        }

        return json_encode($columns);
    }

    /**
     * Returns the data of lines.
     *
     * @return string
     */
    public function getLineData()
    {
        $data = [];
        foreach ($this->lines as $line) {
            $data[] = (array) $line;
        }

        return json_encode($data);
    }

    public function getBreadcrumb()
    {
        $items = [
            ['title' => $this->empresa->nombre, 'url' => '#'],
            ['title' => $this->document->codalmacen, 'url' => '#']
        ];

        if (isset($this->document->codcliente)) {
            $items[] = ['title' => $this->document->nombrecliente, 'url' => '#'];
        } elseif (isset($this->document->codproveedor)) {
            $items[] = ['title' => $this->document->nombre, 'url' => '#'];
        }

        $items[] = ['title' => $this->document->codserie, 'url' => '#'];
        $items[] = ['title' => $this->document->fecha, 'url' => '#'];
        $items[] = ['title' => $this->document->hora, 'url' => '#'];
        return $items;
    }
}
