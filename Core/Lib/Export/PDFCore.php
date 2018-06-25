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
 * @author carlos
 */
class PDFCore
{
    /**
     * X position to start render logo
     */
    const LOGO_X = 540;

    /**
     * Y position to start render logo
     */
    const LOGO_Y = 777;

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
     * Gets the name of an specify divisa
     *
     * @param string $code
     *
     * @return string
     */
    protected function getDivisaName($code): string
    {
        if (empty($code)) {
            return '';
        }

        $divisa = new Model\Divisa();
        return $divisa->loadFromCode($code) ? $divisa->descripcion : '';
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
     * Insert footer details.
     */
    protected function insertFooter()
    {
        $now = $this->i18n->trans('generated-at', ['%when%' => date('d-m-Y H:i')]);
        $this->pdf->addText($this->tableWidth + self::CONTENT_X, self::FOOTER_Y, self::FONT_SIZE, $now, 0, 'right');
    }

    /**
     * Insert header details.
     *
     * @param int $idempresa
     */
    protected function insertHeader($idempresa = null)
    {
        if ($this->insertedHeader) {
            return;
        }

        $this->insertedHeader = true;
        $code = $idempresa ?? AppSettings::get('default', 'idempresa', '');
        $company = new Model\Empresa();
        if ($company->loadFromCode($code)) {
            $this->pdf->ezText($company->nombre, self::FONT_SIZE + 9);
            $address = $company->direccion;
            $address .= empty($company->codpostal) ? '' : ' - CP: ' . $company->codpostal . ' - ';
            $address .= empty($company->ciudad) ? '' : $company->ciudad;
            $address .= empty($company->provincia) ? '' : ' (' . $company->provincia . ') ' . $this->getCountryName($company->codpais);
            $contactData = empty($company->telefono1) ? '' : $company->telefono1 . ' ';
            $contactData .= empty($company->telefono2) ? '' : $company->telefono2 . ' ';
            $contactData .= empty($company->email) ? '' : $company->email . ' ';
            $contactData .= empty($company->web) ? '' : $company->web;

            $lineText = $company->cifnif . ' - ' . $address . ' - ' . $contactData;
            $this->pdf->ezText($lineText . "\n", self::FONT_SIZE);
            $this->insertCompanyLogo();
        }
    }

    /**
     * Insert company logo to PDF document or dies with a message to try to solve the problem.
     */
    protected function insertCompanyLogo()
    {
        $logoPath = \FS_FOLDER . '/Core/Assets/Images/logo.png';
        if (!\file_exists($logoPath)) {
            die('ERROR: logo file not founded on path: ' . $logoPath);
        }

        if (!\function_exists('imagecreatefromstring')) {
            die('ERROR: function imagecreatefromstring() not founded. '
                . ' Do you have installed php-gd package and enabled support to allow us render images? .'
                . 'Note that the package name can differ between operating system or PHP version.');
        }

        $this->addImage($logoPath);
    }

    /**
     * Adds the image to the PDF.
     *
     * @param $logoPath
     */
    protected function addImage($logoPath)
    {
        $myLogoPath = \FS_FOLDER . '/MyFiles/logo.png';
        $logoSize = $this->calcLogoSize($logoPath);
        /// Calculated position to place logo.
        $xPos = self::LOGO_X - $logoSize['width'];
        $yPos = self::LOGO_Y - $logoSize['height'];
        if (strtolower(substr($logoPath, -4)) === '.png') {
            $this->pdf->addPngFromFile($logoPath, self::LOGO_X, self::LOGO_Y, $logoSize['width'], $logoSize['height']);
        } elseif (\function_exists('imagepng')) {
            /// ezPDF library have problems resizing JPEG files, we do a conversion to PNG to bypass this issues
            if (imagepng(imagecreatefromstring(file_get_contents($logoPath)), $myLogoPath)) {
                $this->pdf->addPngFromFile($myLogoPath, $xPos, $yPos, $logoSize['width'], $logoSize['height']);
            } else {
                $this->pdf->addJpegFromFile($logoPath, $xPos, $yPos, $logoSize['width'], $logoSize['height']);
            }
        } else {
            $this->pdf->addJpegFromFile($logoPath, $xPos, $yPos, $logoSize['width'], $logoSize['height']);
        }
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
     * Generate a table with two key => value per row.
     *
     * @param        $tableData
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
            $this->pdf = new \Cezpdf('a4', $orientation);
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

            if (isset($col->display) && $col->display !== 'none' && isset($col->widget->fieldName)) {
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
