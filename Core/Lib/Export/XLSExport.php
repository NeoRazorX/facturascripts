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
namespace FacturaScripts\Core\Lib\Export;

use Symfony\Component\HttpFoundation\Response;

/**
 * Description of XLSExport
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class XLSExport implements ExportInterface
{

    use \FacturaScripts\Core\Base\Utils;

    const LIST_LIMIT = 1000;

    /**
     * XLSX object.
     * @var \XLSXWriter 
     */
    private $writer;

    public function getDoc()
    {
        return $this->writer->writeToString();
    }

    /**
     * Create the document and set headers.
     * @param Response $response
     */
    public function newDoc(&$response)
    {
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename=doc.xls');

        $this->writer = new \XLSXWriter();
        $this->writer->setAuthor('FacturaScripts');
    }

    /**
     * Adds a new page with the model data.
     * @param mixed $model
     * @param array $columns
     * @param string $title
     */
    public function generateModelPage($model, $columns, $title = '')
    {
        $tableData = [];
        foreach ((array) $model as $key => $value) {
            if (is_string($value)) {
                $tableData[] = ['key' => $key, 'value' => $value];
            }
        }

        $this->writer->writeSheet($tableData, $title, ['key' => 'string', 'value' => 'string']);
    }

    /**
     * Adds a new page with a table listing all models data.
     * @param mixed $model
     * @param array $where
     * @param array $order
     * @param int $offset
     * @param array $columns
     * @param string $title
     */
    public function generateListModelPage($model, $where, $order, $offset, $columns, $title = '')
    {
        /// Get the columns
        $tableCols = [];
        $sheetHeaders = [];
        $tableData = [];

        /// Get the columns
        $this->setTableColumns($columns, $tableCols, $sheetHeaders);

        $cursor = $model->all($where, $order, $offset, self::LIST_LIMIT);
        if (empty($cursor)) {
            $this->writer->writeSheet($tableData, $title, $sheetHeaders);
        }
        while (!empty($cursor)) {
            $tableData = $this->getTableData($cursor, $tableCols);
            $this->writer->writeSheet($tableData, $title, $sheetHeaders);

            /// Advance within the results
            $offset += self::LIST_LIMIT;
            $cursor = $model->all($where, $order, $offset, self::LIST_LIMIT);
        }
    }

    private function setTableColumns(&$columns, &$tableCols, &$sheetHeaders)
    {
        foreach ($columns as $col) {
            if (isset($col->columns)) {
                $this->setTableColumns($col->columns, $tableCols, $sheetHeaders);
                continue;
            }

            if (isset($col->widget->fieldName)) {
                $tableCols[$col->widget->fieldName] = $col->widget->fieldName;
                $sheetHeaders[$col->widget->fieldName] = 'string';
            }
        }
    }

    /**
     * Returns the table data
     *
     * @param array $cursor
     * @param array $tableCols
     *
     * @return array
     */
    private function getTableData($cursor, $tableCols)
    {
        $tableData = [];

        /// Get the data
        foreach ($cursor as $key => $row) {
            foreach ($tableCols as $col) {
                $value = '';
                if (isset($row->{$col})) {
                    $value = $row->{$col};
                    if (is_null($value)) {
                        $value = '';
                    }
                }

                $tableData[$key][$col] = $value;
            }
        }

        return $tableData;
    }
}
