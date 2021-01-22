<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Dinamic\Lib\Email\NewMail;
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

    /// 2 hours
    const MAX_FILE_AGE = 7200;
    const MODEL_NAMESPACE = '\\FacturaScripts\\Dinamic\\Model\\';

    /**
     * Model to use with select and autocomplete filters.
     *
     * @var CodeModel
     */
    public $codeModel;

    /**
     *
     * @var NewMail
     */
    public $newMail;

    /**
     * Return the basic data for this page.
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'send-mail';
        $data['icon'] = 'fas fa-envelope';
        $data['showonmenu'] = false;
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
        $this->codeModel = new CodeModel();
        $this->newMail = new NewMail();

        /// Check if the email is configurate
        if (false === $this->newMail->canSendMail()) {
            $this->toolBox()->i18nLog()->warning('email-not-configured');
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

        return parent::url() . '?' . \http_build_query($sendParams);
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
        foreach ($this->codeModel->search($data['source'], $data['field'], $data['title'], $data['term']) as $value) {
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
                $data = $this->autocompleteAction();
                $this->response->setContent(json_encode($data));
                break;

            case 'send':
                if ($this->send()) {
                    $this->toolBox()->i18nLog()->notice('send-mail-ok');
                    $this->updateFemail();
                    $this->redirAfter();
                    break;
                }
                $this->toolBox()->i18nLog()->error('send-mail-error');
                break;

            default:
                $this->removeOld();
                $this->setEmailAddress();
                $this->setAttachment();
                break;
        }
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
        return NewMail::splitEmails($this->request->request->get($field, ''));
    }

    /**
     * 
     */
    protected function redirAfter()
    {
        $className = self::MODEL_NAMESPACE . $this->request->get('modelClassName');
        if (false === \class_exists($className)) {
            return;
        }

        $model = new $className();
        $modelCode = $this->request->get('modelCode');
        if ($model->loadFromCode($modelCode) && property_exists($className, 'femail')) {
            $this->toolBox()->i18nLog()->notice('reloading');
            $this->redirect($model->url(), 3);
        }
    }

    /**
     * Remove old files.
     */
    protected function removeOld()
    {
        foreach (\glob(\FS_FOLDER . '/MyFiles/*_mail_*.pdf') as $fileName) {
            $parts = \explode('_', $fileName);
            $time = (int) \substr(end($parts), 0, -4);
            if ($time < (\time() - self::MAX_FILE_AGE)) {
                \unlink($fileName);
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
     * 
     * @return bool
     */
    protected function send()
    {
        $this->newMail->fromNick = $this->user->nick;
        $this->newMail->addReplyTo($this->user->email, $this->user->nick);

        $this->newMail->title = $this->request->request->get('subject', '');
        $this->newMail->text = $this->request->request->get('body', '');

        $this->newMail->setMailbox($this->request->request->get('email-from', ''));

        foreach ($this->getEmails('email') as $email) {
            $this->newMail->addAddress($email);
        }
        foreach ($this->getEmails('email-cc') as $email) {
            $this->newMail->addCC($email);
        }
        foreach ($this->getEmails('email-bcc') as $email) {
            $this->newMail->addBCC($email);
        }

        $this->setAttachment();
        foreach ($this->request->files->get('uploads', []) as $file) {
            $this->newMail->addAttachment($file->getPathname(), $file->getClientOriginalName());
        }

        if ($this->newMail->send()) {
            $fileName = $this->request->get('fileName', '');
            if (\file_exists(\FS_FOLDER . '/MyFiles/' . $fileName)) {
                \unlink(\FS_FOLDER . '/MyFiles/' . $fileName);
            }

            return true;
        }

        return false;
    }

    protected function setAttachment()
    {
        $fileName = $this->request->get('fileName', '');
        $this->newMail->addAttachment(\FS_FOLDER . '/MyFiles/' . $fileName, $fileName);
    }

    protected function setEmailAddress()
    {
        $className = self::MODEL_NAMESPACE . $this->request->get('modelClassName', '');
        if (false === \class_exists($className)) {
            return;
        }

        $model = new $className();
        $model->loadFromCode($this->request->get('modelCode', ''));
        if (\property_exists($model, 'email')) {
            $this->newMail->addAddress($model->email);
            return;
        }

        $proveedor = new Proveedor();
        if (\property_exists($model, 'codproveedor') && $proveedor->loadFromCode($model->codproveedor) && $proveedor->email) {
            $this->newMail->addAddress($proveedor->email, $proveedor->razonsocial);
            return;
        }

        $contact = new Contacto();
        if (\property_exists($model, 'idcontactofact') && $contact->loadFromCode($model->idcontactofact) && $contact->email) {
            $this->newMail->addAddress($contact->email, $contact->fullName());
            return;
        }

        $cliente = new Cliente();
        if (\property_exists($model, 'codcliente') && $cliente->loadFromCode($model->codcliente) && $cliente->email) {
            $this->newMail->addAddress($cliente->email, $cliente->razonsocial);
        }
    }

    /**
     * Update the property femail with actual date if exist param ModelClassName and ModelCode.
     */
    protected function updateFemail()
    {
        $className = self::MODEL_NAMESPACE . $this->request->get('modelClassName');
        if (false === \class_exists($className)) {
            return;
        }

        $model = new $className();
        $modelCode = $this->request->get('modelCode');
        if ($model->loadFromCode($modelCode) && \property_exists($className, 'femail')) {
            $model->femail = \date(Cliente::DATE_STYLE);
            if (false === $model->save()) {
                $this->toolBox()->i18nLog()->error('error-saving-data');
            }
        }
    }
}
