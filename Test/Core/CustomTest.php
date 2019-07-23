<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use PHPUnit\Framework\TestCase;

/**
 * Description of CustomTest
 *
 * @author Carlos García Gómez
 */
class CustomTest extends TestCase
{

    public $model;

    public function testInit()
    {
        $this->assertNotNull($this->model);
    }

    public function testTableName()
    {
        $this->assertInternalType(
            'string', $this->model->tableName()
        );
    }

    public function testPrimaryColumn()
    {
        $this->assertInternalType(
            'string', $this->model->primaryColumn()
        );
    }

    public function testPrimaryDescription()
    {
        $this->assertInternalType(
            'string', $this->model->primaryDescription()
        );
    }

    public function testFields()
    {
        $this->assertNotEmpty($this->model->getModelFields(), 'FAIL TO CREATE/CHECK TABLE');
    }

    public function testInstall()
    {
        $install = $this->model->install();
        $this->assertInternalType('string', $install);

        if (strlen($install) > 0) {
            $this->assertNotEmpty($this->model->all());
        }
    }

    public function testUrl()
    {
        $this->assertInternalType(
            'string', $this->model->url()
        );
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

    protected function tearDown()
    {
        $minilog = new MiniLog();
        $messages = $minilog->read();
        if (!empty($messages) && $this->getStatus() > 1) {
            array_unshift($messages, ['test' => get_called_class()]);
            $filename = \FS_FOLDER . DIRECTORY_SEPARATOR . 'MINILOG.json';
            $content = file_exists($filename) ? file_get_contents($filename) . "\n-----------\n" : '';
            $content .= json_encode($messages);

            file_put_contents($filename, $content);
        }

        $minilog->clear();
    }
}
