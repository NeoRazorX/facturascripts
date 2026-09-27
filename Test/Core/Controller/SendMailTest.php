<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Controller;

use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Controller\SendMail;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\Email\NewMail;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class SendMailTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    /** @var string[] */
    private $files = [];

    /** @var string[] */
    private $folders = [];

    public function testAttachesTmpFile(): void
    {
        $fileName = 'test-send_mail_' . time() . '_' . Tools::randomString(10) . '.pdf';
        $this->createFile(NewMail::ATTACHMENTS_TMP_PATH . $fileName);

        // el adjunto se muestra con su nombre limpio, sin el sufijo temporal
        $controller = $this->runController(['fileName' => $fileName]);
        $this->assertSame(['test-send.pdf'], $controller->newMail->getAttachmentNames());

        // el nombre visible no puede contener rutas
        $controller = $this->runController(['fileName' => $fileName, 'attachName' => '../../factura.pdf']);
        $this->assertSame(['factura.pdf'], $controller->newMail->getAttachmentNames());
    }

    public function testRejectsFilesOutsideTmpFolder(): void
    {
        // archivo existente fuera de la carpeta temporal, pero dentro de MyFiles
        $outsideName = 'test-send-mail-' . Tools::randomString(10) . '.txt';
        $this->createFile('MyFiles/Tmp/' . $outsideName);

        $fileNames = [
            '../' . $outsideName,
            '../../../config.php',
            '../../../index.php',
            '/etc/passwd',
            '../../../../../../../../etc/passwd',
            'no-existe-' . Tools::randomString(10) . '.pdf',
        ];
        foreach ($fileNames as $fileName) {
            $controller = $this->runController(['fileName' => $fileName]);
            $this->assertEmpty($controller->newMail->getAttachmentNames(), 'attached: ' . $fileName);
        }
    }

    public function testSavedMailDoesNotMoveFilesOutsideTmpFolder(): void
    {
        // archivo fuera de la carpeta temporal, adjuntado con una ruta que empieza por ella
        $outsideName = 'test-send-mail-' . Tools::randomString(10) . '.txt';
        $outsidePath = $this->createFile('MyFiles/Tmp/' . $outsideName);
        Tools::folderCheckOrCreate(FS_FOLDER . '/' . NewMail::ATTACHMENTS_TMP_PATH);

        $mail = NewMail::create();
        $mail->fromEmail = 'test-' . Tools::randomString(10) . '@example.com';
        $this->folders[] = Tools::folder('MyFiles', 'Email', $mail->fromEmail);
        $mail->addAttachment(FS_FOLDER . '/' . NewMail::ATTACHMENTS_TMP_PATH . '../' . $outsideName, $outsideName);
        $this->assertSame([$outsideName], $mail->getAttachmentNames());

        $method = new ReflectionMethod($mail, 'saveMailSent');
        $method->setAccessible(true);
        $method->invoke($mail);

        // el archivo original debe seguir en su sitio
        $this->assertFileExists($outsidePath);
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        $this->files = [];

        foreach ($this->folders as $folder) {
            Tools::folderDelete($folder);
        }
        $this->folders = [];

        $this->logErrors();
    }

    private function createFile(string $path): string
    {
        $fullPath = FS_FOLDER . '/' . $path;
        Tools::folderCheckOrCreate(dirname($fullPath));
        file_put_contents($fullPath, 'test');
        $this->files[] = $fullPath;
        return $fullPath;
    }

    private function runController(array $query): SendMail
    {
        $controller = new SendMail('SendMail', '/SendMail');
        $controller->request = new Request(['query' => $query]);

        $permissions = new ControllerPermissions();
        $permissions->set(true, 1, false, true);

        $response = new Response();
        $controller->privateCore($response, $this->getRandomUser(), $permissions);
        return $controller;
    }
}
