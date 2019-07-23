<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Dinamic\Lib\EmailTools;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Dinamic\Model\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of SendMail
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Javier García Iceta      <javigarciaiceta@gmail.com>
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 */
class SendMail extends Controller
{

    const MAX_FILE_AGE = 7200;

    /**
     *
     * @var string
     */
    public $address;

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
        $data = parent::getPageData();
        $data['menu'] = 'reports';
        $data['showonmenu'] = false;
        $data['title'] = 'send-mail';
        $data['icon'] = 'fas fa-envelope';
        return $data;
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response              $response
     * @param User                  $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->address = $this->getEmailAddress();

        /// Check if the email is configurate
        if (AppSettings::get('email', 'host', '') == "") {
            $this->miniLog->alert($this->i18n->trans('email-not-configured'));
        }

        $action = $this->request->get('action', '');
        $this->execAction($action);
    }

    /**
     * Return the URL of the actual controller.
     *
     * @return string
     */
    public function url()
    {
        $sendParams = ['fileName' => $this->request->get('fileName', '')];
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
     */
    protected function execAction(string $action)
    {
        switch ($action) {
            case 'autocomplete':
                $this->setTemplate(false);
                $this->codeModel = new CodeModel();
                $results = $this->autocompleteAction();
                $this->response->setContent(json_encode($results));
                break;

            case 'send':
                $this->send();
                break;

            default:
                $this->removeOld();
                break;
        }
    }

    /**
     * 
     * @return string
     */
    protected function getEmailAddress()
    {
        $className = '\FacturaScripts\Dinamic\Model\\' . $this->request->get('modelClassName', '');
        if (!class_exists($className)) {
            return '';
        }

        $model = new $className();
        $model->loadFromCode($this->request->get('modelCode', ''));
        if (property_exists($model, 'email')) {
            return $model->email;
        }

        if (property_exists($model, 'codproveedor')) {
            $proveedor = new Proveedor();
            $proveedor->loadFromCode($model->codproveedor);
            return $proveedor->email;
        }

        if (property_exists($model, 'idcontactofact')) {
            $contact = new Contacto();
            $contact->loadFromCode($model->idcontactofact);
            if (!empty($contact->email)) {
                return $contact->email;
            }

            $cliente = new Cliente();
            $cliente->loadFromCode($model->codcliente);
            return $cliente->email;
        }

        return '';
    }

    /**
     * Get emails from field.
     *
     * @param string $field
     *
     * @return array
     */
    protected function getEmails(string $field): array
    {
        $emails = [];

        $string = trim($this->request->request->get($field, ''));
        foreach (explode(',', $string) as $email) {
            if (!empty($email)) {
                $emails[] = $email;
            }
        }

        return $emails;
    }

    /**
     * Remove old files.
     */
    protected function removeOld()
    {
        $regex = '/Mail_([0-9]+).pdf/';
        foreach (glob(\FS_FOLDER . '/MyFiles/Mail_*.pdf') as $fileName) {
            $fileTime = [];
            preg_match($regex, $fileName, $fileTime);
            if ($fileTime[1] < (time() - self::MAX_FILE_AGE)) {
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
        $emailTools = new EmailTools();
        $mail = $emailTools->newMail();
        $mail->Subject = $this->request->request->get('subject', '');
        $mail->msgHTML($this->request->request->get('body', ''));

        foreach ($this->getEmails('email') as $email) {
            $mail->addAddress($email);
        }
        foreach ($this->getEmails('email-cc') as $email) {
            $mail->addCC($email);
        }
        foreach ($this->getEmails('email-bcc') as $email) {
            $mail->addBCC($email);
        }

        $fileName = $this->request->get('fileName', '');
        $mail->addAttachment(\FS_FOLDER . '/MyFiles/' . $fileName);
        foreach ($this->request->files->get('uploads', []) as $file) {
            $mail->addAttachment($file->getPathname(), $file->getClientOriginalName());
        }

        if ($emailTools->send($mail)) {
            if (\file_exists(\FS_FOLDER . '/MyFiles/' . $fileName)) {
                unlink(\FS_FOLDER . '/MyFiles/' . $fileName);
            }

            $this->updateFemail();
            $this->miniLog->notice($this->i18n->trans('send-mail-ok'));
        } else {
            $this->miniLog->error($this->i18n->trans('send-mail-error'));
        }
    }

    /**
     * Update the property femail with actual date if exist param ModelClassName and ModelCode.
     */
    protected function updateFemail()
    {
        $className = '\FacturaScripts\Dinamic\Model\\' . $this->request->get('modelClassName');
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
}
