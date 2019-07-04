<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib\Export;

use Cezpdf;
use FacturaScripts\Core\Base\DivisaTools;
use FacturaScripts\Core\Base\NumberTools;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Base\Utils;

/**
 * Description of PDFCore
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class PDFCore
{

    /**
     * X position to start writting.
     */
    const CONTENT_X = 30;

    /**
     * Font size.
     */
    const FONT_SIZE = 9;

    /**
     * Y position to start footer
     */
    const FOOTER_Y = 10;

    /**
     *
     * @var DivisaTools
     */
    protected $divisaTools;

    /**
     * Translator object
     *
     * @var Translator
     */
    protected $i18n;

    /**
     *
     * @var bool
     */
    protected $insertedHeader = false;

    /**
     * Class with number tools (to format numbers)
     *
     * @var NumberTools
     */
    protected $numberTools;

    /**
     * PDF object.
     *
     * @var Cezpdf
     */
    protected $pdf;

    /**
     * PDF table width.
     *
     * @var int|float
     */
    protected $tableWidth = 0.0;

    /**
     * PDFExport constructor.
     */
    public function __construct()
    {
        $this->divisaTools = new DivisaTools();
        $this->i18n = new Translator();
        $this->numberTools = new NumberTools();
    }

    /**
     * Calculate logo size and return as array of width and height
     *
     * @param $logo
     *
     * @return array
     */
    protected function calcLogoSize($logo)
    {
        $logoSize = $size = getimagesize($logo);
        if ($size[0] > 200) {
            $logoSize[0] = 200;
            $logoSize[1] = $logoSize[1] * $logoSize[0] / $size[0];
            $size[0] = $logoSize[0];
            $size[1] = $logoSize[1];
        }
        if ($size[1] > 80) {
            $logoSize[1] = 80;
            $logoSize[0] = $logoSize[0] * $logoSize[1] / $size[1];
        }
        return [
            'width' => $logoSize[0],
            'height' => $logoSize[1],
        ];
    }

    /**
     * 
     * @param string $value
     *
     * @return string
     */
    protected function fixValue($value)
    {
        return str_replace(['€', '₡', '₲', '£'], ['EUR', 'SVC', 'PYG', 'GBP'], Utils::fixHtml($value));
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
    protected function getTableData($cursor, $tableCols, $tableOptions)
    {
        $tableData = [];

        /// Extracts the data from the cursos
        foreach ($cursor as $key => $row) {
            foreach ($tableCols as $col) {
                if (!isset($row->{$col})) {
                    $tableData[$key][$col] = '';
                    continue;
                }

                $value = isset($tableOptions['cols'][$col]['widget']) ? $tableOptions['cols'][$col]['widget']->plainText($row) : $row->{$col};
                $tableData[$key][$col] = $this->fixValue($value);
            }
        }

        return $tableData;
    }

    /**
     * Generate a table with two key => value per row.
     *
     * @param array  $tableData
     * @param string $title
     * @param array  $options
     */
    protected function insertParalellTable($tableData, $title = '', $options = [])
    {
        $headers = ['data1' => 'data1', 'data2' => 'data2'];
        $rows = $this->paralellTableData($tableData);
        $this->pdf->ezTable($rows, $headers, $title, $options);
    }

    /**
     * Adds a new line to the PDF.
     */
    protected function newLine()
    {
        $posY = $this->pdf->y + 5;
        $this->pdf->line(self::CONTENT_X, $posY, $this->tableWidth + self::CONTENT_X, $posY);
    }

    /**
     * Adds a description of long titles to the PDF.
     *
     * @param array $titles
     */
    protected function newLongTitles(&$titles)
    {
        $txt = '';
        foreach ($titles as $key => $value) {
            if ($txt !== '') {
                $txt .= ', ';
            }

            $txt .= '*' . $key . ' = ' . $value;
        }

        if ($txt !== '') {
            $this->pdf->ezText($txt);
        }
    }

    /**
     * Adds a new page.
     *
     * @param string $orientation
     */
    protected function newPage($orientation = 'portrait')
    {
        if ($this->pdf === null) {
            $this->pdf = new Cezpdf('a4', $orientation);
            $this->pdf->addInfo('Creator', 'FacturaScripts');
            $this->pdf->addInfo('Producer', 'FacturaScripts');
            $this->pdf->tempPath = FS_FOLDER . '/MyFiles/Cache';

            $this->tableWidth = $this->pdf->ez['pageWidth'] - self::CONTENT_X * 2;

            $this->pdf->ezStartPageNumbers(self::CONTENT_X, self::FOOTER_Y, self::FONT_SIZE, 'left', '{PAGENUM} / {TOTALPAGENUM}');
        } elseif ($this->pdf->y < 200) {
            $this->pdf->ezNewPage();
            $this->insertedHeader = false;
        } else {
            $this->pdf->ezText("\n");
        }
    }

    /**
     * Returns a new table with 2 columns. Each column with colName1: colName2
     *
     * @param array  $table
     * @param string $colName1
     * @param string $colName2
     * @param string $finalColName1
     * @param string $finalColName2
     *
     * @return array
     */
    protected function paralellTableData($table, $colName1 = 'key', $colName2 = 'value', $finalColName1 = 'data1', $finalColName2 = 'data2')
    {
        $tableData = [];
        $key = 0;
        foreach ($table as $value) {
            $txt = '<b>' . $value[$colName1] . '</b>: ' . $value[$colName2];

            if (isset($tableData[$key])) {
                $tableData[$key][$finalColName2] = $txt;
                ++$key;
                continue;
            }

            $tableData[$key][$finalColName1] = $txt;
            $tableData[$key][$finalColName2] = '';
        }

        return $tableData;
    }

    /**
     * Remove the empty columns to save space.
     *
     * @param array $tableData
     * @param array $tableColsTitle
     * @param mixed $customEmptyValue
     */
    protected function removeEmptyCols(&$tableData, &$tableColsTitle, $customEmptyValue = '0')
    {
        foreach (array_keys($tableColsTitle) as $key) {
            $remove = true;
            foreach ($tableData as $row) {
                if (!empty($row[$key]) && $row[$key] != $customEmptyValue) {
                    $remove = false;
                    break;
                }
            }

            if ($remove) {
                unset($tableColsTitle[$key]);
            }
        }
    }

    /**
     * Adds to $longTitles, and replace all long titles from $titles
     *
     * @param array $longTitles
     * @param array $titles
     */
    protected function removeLongTitles(&$longTitles, &$titles)
    {
        $num = 1;
        foreach ($titles as $key => $value) {
            if (mb_strlen($value) > 12) {
                $longTitles[$num] = $value;
                $titles[$key] = '*' . $num;
                ++$num;
            }
        }
    }

    /**
     * Set the table content.
     *
     * @param array $columns
     * @param array $tableCols
     * @param array $tableColsTitle
     * @param array $tableOptions
     */
    protected function setTableColumns(&$columns, &$tableCols, &$tableColsTitle, &$tableOptions)
    {
        foreach ($columns as $col) {
            if (is_string($col)) {
                $tableCols[$col] = $col;
                $tableColsTitle[$col] = $col;
                continue;
            }

            if (isset($col->columns)) {
                $this->setTableColumns($col->columns, $tableCols, $tableColsTitle, $tableOptions);
                continue;
            }

            if (!$col->hidden()) {
                $tableCols[$col->widget->fieldname] = $col->widget->fieldname;
                $tableColsTitle[$col->widget->fieldname] = $this->i18n->trans($col->title);
                $tableOptions['cols'][$col->widget->fieldname] = [
                    'justification' => $col->display,
                    'widget' => $col->widget,
                ];
            }
        }
    }
}
