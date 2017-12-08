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

/**
 * Description of DocumentController
 *
 * @author Carlos García Gómez
 */
abstract class DocumentController extends PanelController
{

    public $document;
    public $lines;

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

    abstract protected function getDocumentClassName();

    abstract protected function getDocumentLineClassName();

    public function getLineHeaders()
    {
        $headers = [
            "Referencia", "Descripción", "Cantidad", "Precio", "% Dto.",
            "% IVA", "% RE", "% IRPF", "Subtotal"
        ];
        return json_encode($headers);
    }

    public function getLineColumns()
    {
        $columns = [
            ["data" => "referencia", "type" => "text"],
            ["data" => "descripcion", "type" => "text"],
            ["data" => "cantidad", "type" => "numeric", "format" => "0.00"],
            ["data" => "pvpunitario", "type" => "numeric", "format" => "0.0000"],
            ["data" => "dtopor", "type" => "numeric", "format" => "0.00"],
            ["data" => "iva", "type" => "numeric", "format" => "0.00"],
            ["data" => "recargo", "type" => "numeric", "format" => "0.00"],
            ["data" => "irpf", "type" => "numeric", "format" => "0.00"],
            ["data" => "subtotal", "type" => "numeric", "format" => "0.00"],
        ];

        return json_encode($columns);
    }

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

    protected function loadData($keyView, $view)
    {
        ;
    }
}
