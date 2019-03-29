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
use FacturaScripts\Core\App\AppSettings;

/**
 * Description of SendMail
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Javier García Iceta <javigarciaiceta@gmail.com>
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
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
     * Table name where to look for email.
     *
     * @var string
     */
    public $addressee;

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
        $pageData['icon'] = 'fas fa-envelope';
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

        // Check if the email is configurate
        if (AppSettings::get('email', 'host', '') == "") {
            $this->miniLog->alert($this->i18n->trans('email-not-configure'));
        }

        $this->setAddressee();

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
        $sendParams = [
            'fileName' => $this->request->get('fileName', '')
        ];
        if (empty($sendParams['fileName'])) {
            return parent::url();
        }

        if ($this->request->get('modelClassName') && $this->request->get('modelCode')) {
            $sendParams['modelClassName'] = $this->request->get('modelClassName');
            $sendParams['modelCode'] = $this->request->get('modelCode');
        }

        return parent::url() . '?' . http_build_query($sendParams);
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
     * Get emails about type specify.
     *
     * @param string $typeEmail
     *
     * @return array
     */
    protected function getEmails(string $typeEmail): array
    {
        // Remove unneeded spaces and posible ending comma ,
        $emails = rtrim(trim($this->request->request->get($typeEmail, '')), ',');
        return \explode(',', $emails);
    }

    /**
     * Remove old files.
     */
    protected function removeOld()
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

    /**
     * Send and email with data posted from form.
     */
    protected function send()
    {
        $subject = $this->request->request->get('subject', '');
        $body = $this->request->request->get('body', '');
        $fileName = $this->request->get('fileName', '');

        $emailTools = new EmailTools();
        $mail = $emailTools->newMail();
        $mail->Subject = $subject;
        $mail->msgHTML($body);
        $mail->addAttachment(FS_FOLDER . '/MyFiles/' . $fileName);

        foreach ($this->getEmails('email') as $email) {
            $mail->addAddress($email);
        }
        foreach ($this->getEmails('email-cc') as $email) {
            $mail->addCC($email);
        }
        foreach ($this->getEmails('email-bcc') as $email) {
            $mail->addBCC($email);
        }
        foreach ($this->request->files->get('uploads', []) as $file) {
            $mail->addAttachment($file->getPathname(), $file->getClientOriginalName());
        }

        if ($emailTools->send($mail)) {
            if (\file_exists(FS_FOLDER . '/MyFiles/' . $fileName)) {
                unlink(FS_FOLDER . '/MyFiles/' . $fileName);
            }
            $this->updateFemail();
            $this->miniLog->notice($this->i18n->trans('send-mail-ok'));
        } else {
            $this->miniLog->error($this->i18n->trans('send-mail-error'));
        }
    }

    /**
     * Update the property femail with actual date if exist param ModelClassName and ModelCode
     *
     * @return void
     */
    protected function updateFemail()
    {
        $className = '\FacturaScripts\Core\Model\\' . $this->request->get('modelClassName');
        if (!class_exists($className)) {
            return;
        }

        $model = new $className();
        $modelCode = $this->request->get('modelCode');
        if ($model->loadFromCode($modelCode) && property_exists($className, 'femail')) {
            $model->femail = date('d-m-Y');
            if (!$model->save()) {
                $this->miniLog->alert($this->i18n->trans('error-saving-data'));
            }
        }
    }

    /**
     * Set default table name where to look for email.
     */
    protected function setAddressee()
    {
        $className = '\FacturaScripts\Core\Model\\' . $this->request->get('modelClassName', '');

        if (class_exists($className) && property_exists($className, 'codproveedor')) {
            $this->addressee = 'proveedores';
        } else {
            $this->addressee = 'clientes';
        }
    }
}
