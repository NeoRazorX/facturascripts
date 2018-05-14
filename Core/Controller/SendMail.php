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
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Lib\EmailTools;
use FacturaScripts\Core\Model\CodeModel;

/**
 * Description of SendMail
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Javier García Iceta <javigarciaiceta@gmail.com>
 */
class SendMail extends Controller
{

    /**
     * Model to use with select and autocomplete filters.
     *
     * @var CodeModel
     */
    public $codeModel;

    /**
     * Return the basic data for this page.
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'reports';
        $pageData['title'] = 'send-mail';
        $pageData['icon'] = 'fa-envelope';
        $pageData['showonmenu'] = false;

        return $pageData;
    }

    /**
     * Runs the controller's private logic.
     *
     * @param \Symfony\Component\HttpFoundation\Response      $response
     * @param \FacturaScripts\Core\Model\User                 $user
     * @param \FacturaScripts\Core\Base\ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        // Get any operations that have to be performed
        $action = $this->request->get('action', '');
        if (empty($action)) {
            return;
        }

        // Run operations on the data before reading it
        if (!$this->execPreviousAction($action)) {
            return;
        }

        $this->execAction($action);
    }

    /**
     * Return the URL of the actual controller.
     *
     * @return string
     */
    public function url()
    {
        $fileName = $this->request->get('fileName', '');
        if (empty($fileName)) {
            return parent::url();
        }

        return parent::url() . '?fileName=' . $fileName;
    }

    /**
     * Execute main actions.
     *
     * @param string $action
     *
     * @return bool
     */
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

    /**
     * Remove old files.
     */
    private function removeOld()
    {
        $regex = '/Mail_([0-9]+).pdf/';
        foreach (glob(FS_FOLDER . '/MyFiles/Mail_*.pdf') as $fileName) {
            $fileTime = [];
            preg_match($regex, $fileName, $fileTime);
            if ($fileTime[1] < (time() - 3600)) {
                unlink($fileName);
            }
        }
    }

    /**
     * Send and email with data posted from form.
     */
    protected function send()
    {
        $fieldsEmail = ['email', 'email-cc', 'email-bcc'];
        $sendTo = [];
        foreach ($fieldsEmail as $field) {
            // Remove unneeded spaces
            $emails = trim($this->request->request->get($field, ''));
            // Autocomplete adds a comma at the end, remove it if exists (maybe user remove it)
            $emails = $emails[\strlen($emails) - 1] === ',' ? substr($emails, 0, -1) : $emails;
            $sendTo[$field] = \explode(',', $emails);
        }
        $subject = $this->request->request->get('subject', '');
        $body = $this->request->request->get('body', '');
        $fileName = $this->request->get('fileName', '');

        $emailTools = new EmailTools();
        $mail = $emailTools->newMail();
        foreach ($sendTo['email'] as $email) {
            $mail->addAddress($email);
        }
        foreach ($sendTo['email-cc'] as $email) {
            $mail->addCC($email);
        }
        foreach ($sendTo['email-bcc'] as $email) {
            $mail->addBCC($email);
        }
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

    /**
     * Run the actions that alter data before reading it.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'autocomplete':
                $this->setTemplate(false);
                $this->codeModel = new CodeModel();
                $results = $this->autocompleteAction();
                $this->response->setContent(json_encode($results));
                return false;
        }

        return true;
    }

    /**
     * Run the autocomplete action.
     * Returns a JSON string for the searched values.
     *
     * @return array
     */
    protected function autocompleteAction(): array
    {
        $results = [];
        $data = $this->requestGet(['source', 'field', 'title', 'term']);
        foreach ($this->codeModel::search($data['source'], $data['field'], $data['title'], $data['term']) as $value) {
            $results[] = ['key' => $value->code, 'value' => $value->description];
        }
        return $results;
    }

    /**
     * Return array with parameters values
     *
     * @param array $keys
     *
     * @return array
     */
    protected function requestGet($keys): array
    {
        $result = [];
        foreach ($keys as $value) {
            $result[$value] = $this->request->get($value);
        }
        return $result;
    }
}
