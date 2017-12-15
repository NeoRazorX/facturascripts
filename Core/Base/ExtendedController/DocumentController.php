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
     * Lines of document, the body.
     *
     * @var Base\LineaDocumentoVenta[]|Base\LineaDocumentoCompra[]
     */
    public $lines;

    /**
     * Load views
     */
    protected function createViews()
    {
        if ($this->document === null) {
            $this->setTemplate('Master/DocumentController');
            $iddoc = $this->request->get('code');
            $className = $this->getDocumentClassName();

            $this->document = new $className();
            $this->document->loadFromCode($iddoc);
            if ($this->document) {
                $this->lines = $this->document->getLineas();
            }
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
     * Returns the line headers.
     *
     * @return string
     */
    public function getLineHeaders()
    {
        $headers = [
            'Referencia', 'Descripción', 'Cantidad', 'Precio', '% Dto.',
            '% IVA', '% RE', '% IRPF', 'Subtotal'
        ];
        return json_encode($headers);
    }

    /**
     * Returns the line columns.
     *
     * @return string
     */
    public function getLineColumns()
    {
        $columns = [
            ['data' => 'referencia', 'type' => 'text'],
            ['data' => 'descripcion', 'type' => 'text'],
            ['data' => 'cantidad', 'type' => 'numeric', 'format' => '0.00'],
            ['data' => 'pvpunitario', 'type' => 'numeric', 'format' => '0.0000'],
            ['data' => 'dtopor', 'type' => 'numeric', 'format' => '0.00'],
            ['data' => 'iva', 'type' => 'numeric', 'format' => '0.00'],
            ['data' => 'recargo', 'type' => 'numeric', 'format' => '0.00'],
            ['data' => 'irpf', 'type' => 'numeric', 'format' => '0.00'],
            ['data' => 'subtotal', 'type' => 'numeric', 'format' => '0.00'],
        ];

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
            $data[] = [
                'referencia' => $line->referencia,
                'descripcion' => $line->descripcion,
                'cantidad' => $line->cantidad,
                'pvpunitario' => $line->pvpunitario,
                'dtopor' => $line->dtopor,
                'iva' => $line->iva,
                'recargo' => $line->recargo,
                'irpf' => $line->irpf,
                'subtotal' => $line->pvptotal
            ];
        }

        return json_encode($data);
    }

    /**
     * Load view data procedure
     *
     * @param string $keyView
     * @param BaseView $view
     */
    protected function loadData($keyView, $view)
    {
    }
}
