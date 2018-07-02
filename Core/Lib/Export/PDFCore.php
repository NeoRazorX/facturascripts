<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base;
use FacturaScripts\Core\Model;

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
     * @var Base\DivisaTools
     */
    protected $divisaTools;

    /**
     * Translator object
     *
     * @var Base\Translator
     */
    protected $i18n;

    /**
     *
     * @var bool
     */
    protected $insertedHeader;

    /**
     * Class with number tools (to format numbers)
     *
     * @var Base\NumberTools
     */
    protected $numberTools;

    /**
     * PDF object.
     *
     * @var \Cezpdf
     */
    protected $pdf;

    /**
     * PDF table width.
     *
     * @var int|float
     */
    protected $tableWidth;

    /**
     * PDFExport constructor.
     */
    public function __construct()
    {
        $this->divisaTools = new Base\DivisaTools();
        $this->i18n = new Base\Translator();
        $this->insertedHeader = false;
        $this->numberTools = new Base\NumberTools();
        $this->tableWidth = 0.0;
    }

    /**
     * Gets the name of the country with that code.
     *
     * @param string $code
     *
     * @return string
     */
    protected function getCountryName($code): string
    {
        if (empty($code)) {
            return '';
        }

        $country = new Model\Pais();
        return $country->loadFromCode($code) ? $country->nombre : '';
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

        /// Get the data
        foreach ($cursor as $key => $row) {
            foreach ($tableCols as $col) {
                if (!isset($row->{$col})) {
                    $tableData[$key][$col] = '';
                    continue;
                }

                $value = $row->{$col};
                if ($tableOptions['cols'][$col]['col-type'] === 'number') {
                    $value = $this->numberTools->format($value);
                } elseif ($tableOptions['cols'][$col]['col-type'] === 'money') {
                    $this->divisaTools->findDivisa($row);
                    $value = $this->divisaTools->format($value, FS_NF0, 'coddivisa');
                } elseif (is_bool($value)) {
                    $value = $this->i18n->trans($value === 1 ? 'yes' : 'no');
                } elseif (null === $value) {
                    $value = '';
                } elseif ($tableOptions['cols'][$col]['col-type'] === 'text') {
                    $value = Base\Utils::fixHtml($value);
                }

                $tableData[$key][$col] = $value;
            }
        }

        return $tableData;
    }

    /**
     * Insert company logo to PDF document or dies with a message to try to solve the problem.
     */
    protected function insertCompanyLogo()
    {
        if (!\function_exists('imagecreatefromstring')) {
            die('ERROR: function imagecreatefromstring() not founded. '
                . ' Do you have installed php-gd package and enabled support to allow us render images? .'
                . 'Note that the package name can differ between operating system or PHP version.');
        }

        $logoPath = \FS_FOLDER . '/Core/Assets/Images/logo.png';
        $logoSize = $this->calcLogoSize($logoPath);

        $xPos = $this->pdf->ez['pageWidth'] - $logoSize['width'] - $this->pdf->ez['topMargin'];
        $yPos = $this->pdf->ez['pageHeight'] - $logoSize['height'] - $this->pdf->ez['rightMargin'];
        $this->pdf->addPngFromFile($logoPath, $xPos, $yPos, $logoSize['width'], $logoSize['height']);
    }

    /**
     * Calculate logo size and return as array of width and height
     *
     * @param $logo
     *
     * @return array|bool
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
     * Set table data as key => value.
     *
     * @param array $tableColsTitle
     * @param array $tableDataAux
     * @param mixed $model
     */
    protected function setTablaData(&$tableColsTitle, &$tableDataAux, $model)
    {
        foreach ($tableColsTitle as $key => $colTitle) {
            $value = null;
            if (isset($model->{$key})) {
                $value = $model->{$key};
            }

            if (\is_bool($value)) {
                $txt = $this->i18n->trans($value ? 'yes' : 'no');
                $tableDataAux[] = ['key' => $colTitle, 'value' => $txt];
            } elseif ($value !== null && $value !== '') {
                $value = \is_string($value) ? Base\Utils::fixHtml($value) : $value;
                $tableDataAux[] = ['key' => $colTitle, 'value' => $value];
            }
        }
    }
}
