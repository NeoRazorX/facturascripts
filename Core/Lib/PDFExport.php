<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of PDF
 *
 * @author carlos
 */
class PDFExport implements ExportInterface
{

    use \FacturaScripts\Core\Base\Utils;

    const LIST_LIMIT = 1000;

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
        return $pdf->ezStream(array('Content-Disposition' => 'doc_'.$model->tableName().'.pdf'));
    }

    public function newListDoc($model, $where, $order, $offset, $columns)
    {
        $orientation = 'portrait';
        $tableCols = [];

        /// obtenemos las columnas
        foreach ($columns as $col) {
            if ($col->display != 'none') {
                $tableCols[$col->widget->fieldName] = $col->widget->fieldName;
            }
        }

        if (count($tableCols) > 5) {
            $orientation = 'landscape';
        }

        $pdf = new \Cezpdf('a4', $orientation);
        $pdf->addInfo('Creator', 'FacturaScripts');
        $pdf->addInfo('Producer', 'FacturaScripts');

        $cursor = $model->all($where, $order, $offset, self::LIST_LIMIT);
        while (!empty($cursor)) {
            $tableData = $this->getTableData($cursor, $tableCols);
            $pdf->ezTable($tableData, $tableCols);

            /// avanzamos en los resultados
            $offset += self::LIST_LIMIT;
            $cursor = $model->all($where, $order, $offset, self::LIST_LIMIT);
        }

        return $pdf->ezStream(array('Content-Disposition' => 'list_'.$model->tableName().'.pdf'));
    }

    private function getTableData($cursor, $tableCols)
    {
        $tableData = [];

        /// obtenemos los datos
        foreach ($cursor as $key => $row) {
            foreach ($tableCols as $col) {
                $value = $row->{$col};
                if (is_string($value)) {
                    $value = $this->fixHtml($value);
                }

                $tableData[$key][$col] = $value;
            }
        }

        return $tableData;
    }
    
    /**
     * 
     * @param Response $response
     */
    public function setHeaders(&$response)
    {
        $response->headers->set('Content-type' , 'application/pdf');
    }
}
