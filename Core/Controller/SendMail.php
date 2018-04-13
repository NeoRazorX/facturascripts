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
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Lib\EmailTools;

/**
 * Description of SendMail
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class SendMail extends Controller
{

    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'reports';
        $pageData['title'] = 'send-mail';
        $pageData['icon'] = 'fa-envelope';
        $pageData['showonmenu'] = false;

        return $pageData;
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $action = $this->request->get('action', '');
        if (!empty($action)) {
            $this->execAction($action);
        }
    }

    public function url()
    {
        $fileName = $this->request->get('fileName', '');
        if (empty($fileName)) {
            return parent::url();
        }

        return parent::url() . '?fileName=' . $fileName;
    }

    protected function execAction(string $action)
    {
        switch ($action) {
            case 'send':
                return $this->send();

            default:
                $this->removeOld();
                return false;
        }
    }

    private function removeOld()
    {
        /// TODO: remove MAIL_XXX files whith XXX minor than time() - 3600
    }

    protected function send()
    {
        $sendTo = $this->request->request->get('email', '');
        $subject = $this->request->request->get('subject', '');
        $body = $this->request->request->get('body', '');
        $fileName = $this->request->get('fileName', '');

        $emailTools = new EmailTools();
        $mail = $emailTools->newMail();
        $mail->addAddress($sendTo);
        $mail->Subject = $subject;
        $mail->msgHTML($body);
        $mail->addAttachment(FS_FOLDER . '/MyFiles/' . $fileName);

        if ($emailTools->send($mail)) {
            unlink(FS_FOLDER . '/MyFiles/' . $fileName);
            $this->miniLog->info('send-mail-ok');
        } else {
            $this->miniLog->error('send-mail-error');
        }
    }
}
