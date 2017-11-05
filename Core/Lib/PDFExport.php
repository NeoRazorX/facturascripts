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
namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Base\ExportInterface;
use FacturaScripts\Core\Base\NumberTools;
use FacturaScripts\Core\Base\Translator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of PDF
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class PDFExport implements ExportInterface
{

    use \FacturaScripts\Core\Base\Utils;

    const LIST_LIMIT = 1000;

    /**
     * Clase para formatear números
     *
     * @var NumberTools
     */
    private $numberTools;

    /**
     * Objeto traductor
     *
     * @var Translator
     */
    private $i18n;

    /**
     * PDFExport constructor.
     */
    public function __construct()
    {
        $this->numberTools = new NumberTools();
        $this->i18n = new Translator();
    }

    /**
     * Nuevo documento
     *
     * @param $model
     * @return string
     */
    public function newDoc($model)
    {
        $tableData = [];
        foreach ((array) $model as $key => $value) {
            if (is_string($value)) {
                $tableData[] = ['key' => $key, 'value' => $this->fixHtml($value)];
            }
        }

        $pdf = new \Cezpdf('a4', 'portrait');
        $pdf->addInfo('Creator', 'FacturaScripts');
        $pdf->addInfo('Producer', 'FacturaScripts');
        $pdf->ezTable($tableData);
        return $pdf->ezStream(['Content-Disposition' => 'doc_' . $model->tableName() . '.pdf']);
    }

    /**
     * Nueva lista de documentos
     *
     * @param $model
     * @param array $where
     * @param array $order
     * @param int $offset
     * @param array $columns
     *
     * @return array
     */
    public function newListDoc($model, $where, $order, $offset, $columns)
    {
        $orientation = 'portrait';
        $tableCols = [];
        $tableColsTitle = [];
        $tableOptions = ['cols' => []];
        $tableData = [];

        /// obtenemos las columnas
        foreach ($columns as $col) {
            if ($col->display != 'none') {
                $tableCols[$col->widget->fieldName] = $col->widget->fieldName;
                $tableColsTitle[$col->widget->fieldName] = $this->i18n->trans($col->title);
                $tableOptions['cols'][$col->widget->fieldName] = [
                    'justification' => $col->display,
                    'col-type' => $col->widget->type,
                ];
            }
        }

        if (count($tableCols) > 5) {
            $orientation = 'landscape';
        }

        $pdf = new \Cezpdf('a4', $orientation);
        $pdf->addInfo('Creator', 'FacturaScripts');
        $pdf->addInfo('Producer', 'FacturaScripts');

        $cursor = $model->all($where, $order, $offset, self::LIST_LIMIT);
        if (empty($cursor)) {
            $pdf->ezTable($tableData, $tableColsTitle, '', $tableOptions);
        }
        while (!empty($cursor)) {
            $tableData = $this->getTableData($cursor, $tableCols, $tableOptions);
            $pdf->ezTable($tableData, $tableColsTitle, '', $tableOptions);

            /// avanzamos en los resultados
            $offset += self::LIST_LIMIT;
            $cursor = $model->all($where, $order, $offset, self::LIST_LIMIT);
        }

        return $pdf->ezStream(['Content-Disposition' => 'list_' . $model->tableName() . '.pdf']);
    }

    /**
     * Devuelvo los datos de la tabla
     *
     * @param array $cursor
     * @param array $tableCols
     * @param array $tableOptions
     *
     * @return array
     */
    private function getTableData($cursor, $tableCols, $tableOptions)
    {
        $tableData = [];

        /// obtenemos los datos
        foreach ($cursor as $key => $row) {
            foreach ($tableCols as $col) {
                $value = '';
                if (isset($row->{$col})) {
                    $value = $row->{$col};

                    if (in_array($tableOptions['cols'][$col]['col-type'], ['money', 'number'])) {
                        $value = $this->numberTools->format($value, 2);
                    } elseif (is_string($value)) {
                        $value = $this->fixHtml($value);
                    } elseif (is_bool($value)) {
                        $value = $value == 1 ? $this->i18n->trans('enabled') : $this->i18n->trans('disabled');
                    } elseif (is_null($value)) {
                        $value = '';
                    }
                }

                $tableData[$key][$col] = $value;
            }
        }

        return $tableData;
    }

    /**
     * Asigna la cabecera
     *
     * @param Response $response
     */
    public function setHeaders(&$response)
    {
        $response->headers->set('Content-type', 'application/pdf');
    }
}
