<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core;

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Model\Base\ModelClass;
use PHPUnit\Framework\TestCase;

/**
 * Description of CustomTest
 *
 * @author Carlos García Gómez
 */
abstract class CustomTest extends TestCase
{

    /**
     * @var ModelClass
     */
    public $model;

    public function testInit()
    {
        $this->assertNotNull($this->model);
    }

    public function testTableName()
    {
        $this->assertIsString($this->model->tableName());
    }

    public function testPrimaryColumn()
    {
        $this->assertIsString($this->model->primaryColumn());
    }

    public function testPrimaryDescription()
    {
        $this->assertIsString($this->model->primaryDescription());
    }

    public function testFields()
    {
        $this->assertNotEmpty($this->model->getModelFields(), 'FAIL TO CREATE/CHECK TABLE');
    }

    public function testInstall()
    {
        $install = $this->model->install();
        $this->assertIsString($install);

        if (strlen($install) > 0) {
            $this->assertNotEmpty($this->model->all());
        }
    }

    public function testUrl()
    {
        $this->assertIsString($this->model->url());
    }

    public function testExists()
    {
        $this->model->clear();
        $this->assertFalse($this->model->exists());
    }

    public function testAll()
    {
        foreach ($this->model->all() as $model) {
            $this->assertTrue($model->test());
            $this->assertTrue($model->exists());
        }
    }

    protected function tearDown(): void
    {
        $logger = new MiniLog();
        $messages = $logger->read();
        if (!empty($messages) && $this->getStatus() > 1) {
            array_unshift($messages, ['test' => get_called_class()]);
            $filename = FS_FOLDER . DIRECTORY_SEPARATOR . 'MINILOG.json';
            $content = file_exists($filename) ? file_get_contents($filename) . "\n-----------\n" : '';
            $content .= json_encode($messages);

            file_put_contents($filename, $content);
        }

        $logger->clear();
    }
}
