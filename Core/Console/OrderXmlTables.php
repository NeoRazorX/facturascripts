<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Console;

use DOMDocument;
use SimpleXMLElement;

/**
 * Class OrderXMLTable
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class OrderXmlTables extends ConsoleAbstract
{
    /**
     * Constant values for return, to easy know how execution ends.
     */
    const RETURN_SUCCESS = 0;
    const RETURN_SRC_FOLDER_NOT_SET = 1;
    const RETURN_DST_FOLDER_NOT_SET = 2;
    const RETURN_TAGNAME_NOT_SET = 3;
    const RETURN_CANT_CREATE_FOLDER = 4;
    const RETURN_FAIL_SAVING_FILE = 5;
    const RETURN_NO_FILES = 6;
    const RETURN_SRC_FOLDER_NOT_EXISTS = 7;

    /**
     * Folder source path.
     *
     * @var string
     */
    private $folderSrcPath;

    /**
     * Folder destiny path.
     *
     * @var string
     */
    private $folderDstPath;

    /**
     * Tagname used for order.
     *
     * @var string
     */
    private $tagName;

    /**
     * Set default source folder.
     *
     * @param string $folderSrcPath
     */
    public function setFolderSrcPath($folderSrcPath)
    {
        $this->folderSrcPath = $folderSrcPath;
    }

    /**
     * Set default destiny folder.
     *
     * @param string $folderDstPath
     */
    public function setFolderDstPath($folderDstPath)
    {
        $this->folderDstPath = $folderDstPath;
    }

    /**
     * Set tag name.
     *
     * @param string $tagName
     */
    public function setTagName($tagName)
    {
        $this->tagName = $tagName;
    }

    /**
     * Run the OrderXMLTable script.
     *
     * @param array $params
     *
     * @return int
     */
    public function run(...$params): int
    {
        $this->checkOptions($params);

        $this->setFolderSrcPath($params[0] ?? \FS_FOLDER . '/Core/Table/');
        $this->setFolderDstPath($params[1] ?? \FS_FOLDER . '/Core/Table/');
        $this->setTagName($params[2] ?? 'name');

        echo 'Options setted:' . \PHP_EOL;
        echo '   Source path: ' . $this->folderSrcPath . \PHP_EOL;
        echo '   Destiny path: ' . $this->folderDstPath . \PHP_EOL;
        echo '   Tag name: ' . $this->tagName . \PHP_EOL;
        if (!$this->areYouSure()) {
            echo 'Run as: php ' . __CLASS__ . ' [SRC] [DST] [TAG]' . \PHP_EOL;
            return self::RETURN_SUCCESS;
        }

        $status = $this->check();
        if ($status > 0) {
            return $status;
        }

        $files = array_diff(scandir($this->folderSrcPath, SCANDIR_SORT_ASCENDING), ['.', '..']);

        if (\count($files) === 0) {
            echo 'ERROR: No files on folder' . \PHP_EOL;
            return self::RETURN_NO_FILES;
        }

        foreach ($files as $fileName) {
            $xml = simplexml_load_string(file_get_contents($this->folderSrcPath . $fileName));
            // Get all children of table into an array
            $table = (array) $xml->children();
            if (!isset($table['column'])) {
                echo 'File is incorrect ' . $this->folderSrcPath . $fileName . \PHP_EOL;
                break;
            }
            $columns = $table['column'];
            if (isset($table['constraint'])) {
                $constraints = $table['constraint'];
            }

            // Call usort on the array
            if (!\is_object($columns)) {
                usort($columns, [$this, 'sortName']);
            }

            /** @noinspection NotOptimalIfConditionsInspection */
            /** @noinspection PhpUndefinedVariableInspection */
            if (isset($table['constraint']) && !\is_object($constraints)) {
                usort($constraints, [$this, 'sortName']);
            }

            // Generate string XML result
            $strXML = $this->generateXMLHeader($fileName);
            $strXML .= $this->generateXMLContent($columns);
            if (isset($table['constraint'])) {
                /** @noinspection PhpUndefinedVariableInspection */
                $strXML .= $this->generateXMLContent($constraints, 'constraint');
            }
            $strXML .= $this->generateXMLFooter();

            $dom = new DOMDocument();
            $dom->loadXML($strXML);
            $filePath = $this->folderDstPath . '/' . $fileName;
            if ($dom->save($filePath) === false) {
                echo "ERROR: Can't save file " . $filePath . \PHP_EOL;
                return self::RETURN_FAIL_SAVING_FILE;
            }
        }

        echo 'Finished! Look at "' . $this->folderDstPath . '"' . \PHP_EOL;
        return self::RETURN_SUCCESS;
    }

    /**
     * Return description about this class.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'Order XML content files by tag name.';
    }

    /**
     * Print help information to the user.
     */
    public function showHelp()
    {
        $array = \explode('\\', __CLASS__);
        $class = array_pop($array);
        echo 'Use as: php console.php ' . $class . ' [OPTIONS]' . \PHP_EOL;
        echo 'Available options:' . \PHP_EOL;
        echo '   -h, --help        Show this help.' . \PHP_EOL;
        echo \PHP_EOL;
        echo '   OPTION1           Source path' . \PHP_EOL;
        echo '   OPTION2           Destiny path' . \PHP_EOL;
        echo '   OPTION3           Tag name' . \PHP_EOL;
        echo \PHP_EOL;
    }

    /**
     * Returns an associative array of available methods for the user.
     * Add more options if you want to add support for custom methods.
     *      [
     *          '-h'        => 'showHelp',
     *          '--help'    => 'showHelp',
     *      ]
     *
     * @return array
     */
    public function getUserMethods(): array
    {
        return [
            '-h' => 'showHelp',
            '--help' => 'showHelp'
        ];
    }

    /**
     * @param array $params
     */
    private function checkOptions(array $params = [])
    {
        if (isset($params[0])) {
            switch ($params[0]) {
                case '-h':
                case '--help':
                    $this->showHelp();
                    break;
            }
            die();
        }
    }

    /**
     * Launch basic checks.
     *
     * @return int
     */
    private function check(): int
    {
        if ($this->folderSrcPath === null) {
            echo 'ERROR: Source folder not setted.' . \PHP_EOL;
            return self::RETURN_SRC_FOLDER_NOT_SET;
        }

        if ($this->folderDstPath === null) {
            echo 'ERROR: Destiny folder not setted.' . \PHP_EOL;
            return self::RETURN_DST_FOLDER_NOT_SET;
        }

        if ($this->tagName === null) {
            echo 'ERROR: Tag name not setted.' . \PHP_EOL;
            return self::RETURN_TAGNAME_NOT_SET;
        }

        if (!is_dir($this->folderSrcPath)) {
            echo 'ERROR: Source folder ' . $this->folderSrcPath . ' not exists.' . \PHP_EOL;
            return self::RETURN_SRC_FOLDER_NOT_EXISTS;
        }


        if (!is_file($this->folderDstPath) && !@mkdir($this->folderDstPath) && !is_dir($this->folderDstPath)) {
            echo "ERROR: Can't create folder " . $this->folderDstPath;
            return self::RETURN_CANT_CREATE_FOLDER;
        }

        return self::RETURN_SUCCESS;
    }

    /**
     * Custom function to re-order with usort.
     *
     * @param SimpleXMLElement $xmlA
     * @param SimpleXMLElement $xmlB
     *
     * @return int
     */
    private function sortName($xmlA, $xmlB): int
    {
        return strcasecmp($xmlA->{$this->tagName}, $xmlB->{$this->tagName});
    }

    /**
     * Generate the content of the XML.
     *
     * @param array|object $data
     * @param string       $tagName
     *
     * @return string
     */
    private function generateXMLContent($data, $tagName = 'column'): string
    {
        $str = '';
        if (\is_array($data)) {
            foreach ($data as $field) {
                $str .= '    <' . $tagName . '>' . PHP_EOL;
                foreach ((array) $field as $key => $value) {
                    $str .= '        <' . $key . '>' . $value . '</' . $key . '>' . PHP_EOL;
                }
                $str .= '    </' . $tagName . '>' . PHP_EOL;
            }
        }
        if (\is_object($data)) {
            $str .= '    <' . $tagName . '>' . PHP_EOL;
            foreach ((array) $data as $key => $value) {
                $str .= '        <' . $key . '>' . $value . '</' . $key . '>' . PHP_EOL;
            }
            $str .= '    </' . $tagName . '>' . PHP_EOL;
        }

        return $str;
    }

    /**
     * Generate the header of the XML.
     *
     * @param string $fileName
     *
     * @return string
     */
    private function generateXMLHeader($fileName): string
    {
        $str = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $str .= '<!--' . PHP_EOL;
        $str .= '    Document   : ' . $fileName . PHP_EOL;
        $str .= '    Author     : Carlos Garcia Gomez' . PHP_EOL;
        $str .= '    Description: Structure for the ' . str_replace('.xml', '', $fileName) . ' table.' . PHP_EOL;
        $str .= '-->' . PHP_EOL;
        $str .= '<table>' . PHP_EOL;

        return $str;
    }

    /**
     * Generate the footer of the XML.
     *
     * @return string
     */
    private function generateXMLFooter(): string
    {
        return '</table>';
    }
}
