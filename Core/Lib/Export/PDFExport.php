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
     * Translator object
     *
     * @var Translator
     */
    private $i18n;

    /**
     * Class with number tools (to format numbers)
     *
     * @var NumberTools
     */
    private $numberTools;

    /**
     * PDF object.
     * @var \Cezpdf 
     */
    private $pdf;

    /**
     * PDF table width.
     * @var int|float 
     */
    private $tableWidth;

    /**
     * PDFExport constructor.
     */
    public function __construct()
    {
        $this->i18n = new Translator();
        $this->numberTools = new NumberTools();
        $this->tableWidth = 0.0;
    }

    public function getDoc()
    {
        if ($this->pdf === null) {
            $this->newPage();
            $this->pdf->ezText('');
        }

        return $this->pdf->ezStream(['Content-Disposition' => 'doc_' . mt_rand(1, 999999) . '.pdf']);
    }

    /**
     * Set headers.
     * @param Response $response
     */
    public function newDoc(&$response)
    {
        $response->headers->set('Content-type', 'application/pdf');
    }

    /**
     * Adds a new page with the model data.
     * @param mixed $model
     * @param array $columns
     * @param string $title
     */
    public function generateModelPage($model, $columns, $title = '')
    {
        $this->newPage();
        $tableCols = [];
        $tableColsTitle = [];
        $tableOptions = ['width' => $this->tableWidth, 'showHeadings' => 0, 'cols' => []];
        $tableData = [];

        /// Get the columns
        $this->setTableColumns($columns, $tableCols, $tableColsTitle, $tableOptions);

        foreach ($tableColsTitle as $key => $colTitle) {
            $value = null;
            if (isset($model->{$key})) {
                $value = $model->{$key};
            }

            if (is_bool($value)) {
                $txt = $value ? $this->i18n->trans('yes') : $this->i18n->trans('no');
                $tableData[] = ['key' => $colTitle, 'value' => $txt];
            } else if ($value !== null && $value !== '') {
                $tableData[] = ['key' => $colTitle, 'value' => $value];
            }
        }

        $this->pdf->ezTable($tableData, ['key' => 'key', 'value' => 'value'], $title, $tableOptions);
    }

    /**
     * Adds a new page with a table listing the models data.
     * @param mixed $model
     * @param array $where
     * @param array $order
     * @param int $offset
     * @param array $columns
     * @param string $title
     */
    public function generateListModelPage($model, $where, $order, $offset, $columns, $title = '')
    {
        $orientation = 'portrait';
        $tableCols = [];
        $tableColsTitle = [];
        $tableOptions = ['cols' => [], 'shadeHeadingCol' => [0.8, 0.8, 0.8]];
        $tableData = [];

        /// Get the columns
        $this->setTableColumns($columns, $tableCols, $tableColsTitle, $tableOptions);
        if (count($tableCols) > 5) {
            $orientation = 'landscape';
        }

        $this->newPage($orientation);
        $tableOptions['width'] = $this->tableWidth;

        $cursor = $model->all($where, $order, $offset, self::LIST_LIMIT);
        if (empty($cursor)) {
            $this->pdf->ezTable($tableData, $tableColsTitle, '', $tableOptions);
        }
        while (!empty($cursor)) {
            $tableData = $this->getTableData($cursor, $tableCols, $tableOptions);
            $this->removeEmptyCols($tableData, $tableColsTitle);
            $this->pdf->ezTable($tableData, $tableColsTitle, $title, $tableOptions);

            /// Advance within the results
            $offset += self::LIST_LIMIT;
            $cursor = $model->all($where, $order, $offset, self::LIST_LIMIT);
        }
    }

    /**
     * Adds a new page.
     * @param string $orientation
     */
    private function newPage($orientation = 'portrait')
    {
        if ($this->pdf === null) {
            $this->pdf = new \Cezpdf('a4', $orientation);
            $this->pdf->addInfo('Creator', 'FacturaScripts');
            $this->pdf->addInfo('Producer', 'FacturaScripts');

            $this->tableWidth = $this->pdf->ez['pageWidth'] - 60;

            $this->pdf->ezStartPageNumbers($this->pdf->ez['pageWidth'] / 2, 10, 9, 'left', '{PAGENUM} / {TOTALPAGENUM}');
        } else if ($this->pdf->y < 200) {
            $this->pdf->ezNewPage();
        } else {
            $this->pdf->ezText("\n");
        }
    }

    private function setTableColumns(&$columns, &$tableCols, &$tableColsTitle, &$tableOptions)
    {
        foreach ($columns as $col) {
            if (isset($col->columns)) {
                $this->setTableColumns($col->columns, $tableCols, $tableColsTitle, $tableOptions);
                continue;
            }

            if (isset($col->display) && $col->display != 'none' && isset($col->widget->fieldName)) {
                $tableCols[$col->widget->fieldName] = $col->widget->fieldName;
                $tableColsTitle[$col->widget->fieldName] = $this->i18n->trans($col->title);
                $tableOptions['cols'][$col->widget->fieldName] = [
                    'justification' => $col->display,
                    'col-type' => $col->widget->type,
                ];
            }
        }
    }

    /**
     * Returns the table data
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

        /// Get the data
        foreach ($cursor as $key => $row) {
            foreach ($tableCols as $col) {
                $value = '';
                if (isset($row->{$col})) {
                    $value = $row->{$col};

                    if (in_array($tableOptions['cols'][$col]['col-type'], ['money', 'number'])) {
                        $value = $this->numberTools->format($value, 2);
                    } elseif (is_bool($value)) {
                        $value = $value == 1 ? $this->i18n->trans('yes') : $this->i18n->trans('no');
                    } elseif (is_null($value)) {
                        $value = '';
                    }
                }

                $tableData[$key][$col] = $value;
            }
        }

        return $tableData;
    }

    private function removeEmptyCols(&$tableData, &$tableColsTitle)
    {
        foreach (array_keys($tableColsTitle) as $key) {
            $remove = true;
            foreach ($tableData as $row) {
                if ($row[$key] !== null && $row[$key] !== '') {
                    $remove = false;
                    break;
                }
            }

            if ($remove) {
                unset($tableColsTitle[$key]);
            }
        }
    }
}
