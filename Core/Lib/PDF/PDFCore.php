<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\PDF;

use Cezpdf;
use FacturaScripts\Core\Base\DivisaTools;
use FacturaScripts\Core\Base\NumberTools;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Lib\Export\ExportBase;
use FacturaScripts\Dinamic\Model\AttachedFile;

/**
 * Description of PDFCore
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class PDFCore extends ExportBase
{

    /**
     * X position to start writing.
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
     * Maximum title length
     */
    const MAX_TITLE_LEN = 12;

    /**
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
     * Sets default orientation.
     *
     * @param string $orientation
     */
    public function setOrientation(string $orientation)
    {
        $this->newPage($orientation);
    }

    /**
     * @param AttachedFile $file
     * @param int|float $xPos
     * @param int|float $yPos
     * @param int|float $width
     * @param int|float $height
     */
    protected function addImageFromAttachedFile(AttachedFile $file, $xPos, $yPos, $width, $height)
    {
        switch ($file->mimetype) {
            case 'image/gif':
                $this->pdf->addGifFromFile($file->path, $xPos, $yPos, $width, $height);
                break;

            case 'image/jpeg':
            case 'image/jpg':
                $this->pdf->addJpegFromFile($file->path, $xPos, $yPos, $width, $height);
                break;

            case 'image/png':
                $this->pdf->addPngFromFile($file->path, $xPos, $yPos, $width, $height);
                break;
        }
    }

    /**
     * @param string $filePath
     * @param int|float $xPos
     * @param int|float $yPos
     * @param int|float $width
     * @param int|float $height
     */
    protected function addImageFromFile(string $filePath, $xPos, $yPos, $width, $height)
    {
        $parts = explode('.', $filePath);
        $extension = strtolower(end($parts));
        switch ($extension) {
            case 'gif':
                $this->pdf->addGifFromFile($filePath, $xPos, $yPos, $width, $height);
                break;

            case 'jpeg':
            case 'jpg':
                $this->pdf->addJpegFromFile($filePath, $xPos, $yPos, $width, $height);
                break;

            case 'png':
                $this->pdf->addPngFromFile($filePath, $xPos, $yPos, $width, $height);
                break;
        }
    }

    /**
     * Calculate image size and return as array of width and height
     *
     * @param string $filePath
     *
     * @return array
     */
    protected function calcImageSize(string $filePath): array
    {
        $imageSize = $size = getimagesize($filePath);
        if ($size[0] > 200) {
            $imageSize[0] = 200;
            $imageSize[1] = $imageSize[1] * $imageSize[0] / $size[0];
            $size[0] = $imageSize[0];
            $size[1] = $imageSize[1];
        }
        if ($size[1] > 80) {
            $imageSize[1] = 80;
            $imageSize[0] = $imageSize[0] * $imageSize[1] / $size[1];
        }
        return [
            'width' => $imageSize[0],
            'height' => $imageSize[1]
        ];
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function fixValue(string $value): string
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
    protected function getTableData(array $cursor, array $tableCols, array $tableOptions): array
    {
        $tableData = [];

        /// Extracts the data from the cursos
        foreach ($cursor as $key => $row) {
            foreach ($tableCols as $col) {
                $value = $tableOptions['cols'][$col]['widget']->plainText($row);
                $tableData[$key][$col] = $this->fixValue($value);
            }
        }

        return $tableData;
    }

    /**
     * Generate a table with two key => value per row.
     *
     * @param array $tableData
     * @param string $title
     * @param array $options
     */
    protected function insertParallelTable(array $tableData, string $title = '', array $options = [])
    {
        $headers = ['data1' => 'data1', 'data2' => 'data2'];
        $rows = $this->parallelTableData($tableData);
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
     * @param array $columns
     */
    protected function newLongTitles(array &$titles, array $columns)
    {
        $txt = '';
        foreach ($titles as $key => $value) {
            if (!in_array('*' . $key, $columns)) {
                continue;
            }

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
    protected function newPage(string $orientation = 'portrait')
    {
        if ($this->pdf === null) {
            $this->pdf = new Cezpdf('a4', $orientation);
            $this->pdf->addInfo('Creator', 'FacturaScripts');
            $this->pdf->addInfo('Producer', 'FacturaScripts');
            $this->pdf->addInfo('Title', $this->getFileName());
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
     * @param array $table
     * @param string $colName1
     * @param string $colName2
     * @param string $finalColName1
     * @param string $finalColName2
     *
     * @return array
     */
    protected function parallelTableData(array $table, string $colName1 = 'key', string $colName2 = 'value', string $finalColName1 = 'data1', string $finalColName2 = 'data2'): array
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
    protected function removeEmptyCols(array &$tableData, array &$tableColsTitle, $customEmptyValue = '0')
    {
        $emptyValues = ['-', '0%', $customEmptyValue, $customEmptyValue . '%'];
        foreach (array_keys($tableColsTitle) as $key) {
            $remove = true;
            foreach ($tableData as $row) {
                if (!empty($row[$key]) && !in_array($row[$key], $emptyValues)) {
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
    protected function removeLongTitles(array &$longTitles, array &$titles)
    {
        $num = 1;
        foreach ($titles as $key => $value) {
            if (mb_strlen($value) > self::MAX_TITLE_LEN) {
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
    protected function setTableColumns(array &$columns, array &$tableCols, array &$tableColsTitle, array &$tableOptions)
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
