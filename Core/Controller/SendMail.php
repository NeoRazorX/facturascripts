<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Validator;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\Email\NewMail;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Dinamic\Model\EmailNotification;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\Proveedor;

/**
 * Description of SendMail
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Javier García Iceta      <javigarciaiceta@gmail.com>
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 */
class SendMail extends Controller
{
    const MAX_FILE_AGE = 2592000; // 30 days
    const MODEL_NAMESPACE = '\\FacturaScripts\\Dinamic\\Model\\';

    /** @var CodeModel */
    public $codeModel;

    /** @var NewMail */
    public $newMail;

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'send-mail';
        $data['icon'] = 'fa-solid fa-envelope';
        $data['showonmenu'] = false;
        return $data;
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $this->codeModel = new CodeModel();
        $this->newMail = NewMail::create()
            ->setUser($this->user);

        $action = $this->request->inputOrQuery('action', '');
        $this->execAction($action);
    }

    /**
     * Construye la url para el formulario de la vista.
     *
     * @return string
     */
    public function url(): string
    {
        $sendParams = ['fileName' => $this->request->queryOrInput('fileName', '')];
        if (empty($sendParams['fileName'])) {
            return parent::url();
        }

        if ($this->request->has('modelClassName') && $this->request->has('modelCode')) {
            $sendParams['modelClassName'] = $this->request->queryOrInput('modelClassName');
            $sendParams['modelCode'] = $this->request->queryOrInput('modelCode');
            if ($this->request->has('modelCodes')) {
                $sendParams['modelCodes'] = urldecode($this->request->queryOrInput('modelCodes'));
            }
        }

        return parent::url() . '?' . http_build_query($sendParams);
    }

    /**
     * Devuelve los valores al buscar un email en el campo Para, CC o CCO.
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

        $this->pipe('autocompleteAction', $results, $data);
        return $results;
    }

    /**
     * Comprueba condiciones especiales del documento.
     *
     * @return void
     */
    protected function checkDocument(): void
    {
        $this->pipe('checkDocument');

        if ($this->request->query('modelClassName') !== 'FacturaCliente') {
            return;
        }

        $invoice = new FacturaCliente();
        if ($invoice->load($this->request->query->getAlnum('modelCode')) && $invoice->editable) {
            Tools::log()->warning('sketch-invoice-warning');
        }
    }

    protected function execAction(string $action): void
    {
        switch ($action) {
            case 'autocomplete':
                $this->setTemplate(false);
                $data = $this->autocompleteAction();
                $this->response->json($data);
                break;

            case 'send':
                // valid request?
                if (false === $this->validateFormToken()) {
                    break;
                }
                if ($this->send()) {
                    Tools::log()->notice('send-mail-ok');
                    $this->updateFemail();
                    $this->redirectAfter();
                    break;
                }
                Tools::log()->error('send-mail-error');
                break;

            default:
                $this->removeOld();
                $this->setEmail();
                $this->setAttachment();
                $this->checkDocument();

                // Comprobar si el email está bien configurado
                if (false === $this->newMail->canSendMail()) {
                    Tools::log()->warning('email-not-configured');
                }

                break;
        }

        $this->pipe('execAction', $action);
    }

    protected function getEmails(string $field): array
    {
        return NewMail::splitEmails($this->request->input($field, ''));
    }

    protected function loadDataDefault($model): void
    {
        // si el email ya tiene asunto o cuerpo, no hacemos nada
        if (!empty($this->newMail->title) || !empty($this->newMail->body)) {
            return;
        }

        $subject = '';
        $body = '';

        // buscamos el texto de la notificación para usar el asunto y el cuerpo
        $notificationModel = new EmailNotification();
        $where = [
            Where::eq('name', 'sendmail-' . $model->modelClassName()),
            Where::eq('enabled', true)
        ];
        if ($notificationModel->loadWhere($where)) {
            // hemos encontrado una notificación, usamos su asunto y cuerpo
            $shortCodes = ['{code}', '{name}', '{date}', '{total}', '{number2}'];
            $shortValues = [$model->codigo, '', $model->fecha, $model->total, ''];

            $shortValues[1] = $model->hasColumn('nombrecliente')
                ? $model->nombrecliente
                : $model->nombre;

            $shortValues[4] = $model->hasColumn('numero2')
                ? $model->numero2
                : $model->numproveedor;

            $subject = str_replace($shortCodes, $shortValues, $notificationModel->subject);
            $body = str_replace($shortCodes, $shortValues, $notificationModel->body);
        } else {
            // si no hay notificación, usamos los datos de las traducciones
            switch ($model->modelClassName()) {
                case 'AlbaranCliente':
                case 'AlbaranProveedor':
                    $subject = Tools::trans('delivery-note-email-subject', ['%code%' => $model->codigo]);
                    $body = Tools::trans('delivery-note-email-text', ['%code%' => $model->codigo]);
                    break;

                case 'FacturaCliente':
                case 'FacturaProveedor':
                    $subject = Tools::trans('invoice-email-subject', ['%code%' => $model->codigo]);
                    $body = Tools::trans('invoice-email-text', ['%code%' => $model->codigo]);
                    break;

                case 'PedidoCliente':
                case 'PedidoProveedor':
                    $subject = Tools::trans('order-email-subject', ['%code%' => $model->codigo]);
                    $body = Tools::trans('order-email-text', ['%code%' => $model->codigo]);
                    break;

                case 'PresupuestoCliente':
                case 'PresupuestoProveedor':
                    $subject = Tools::trans('estimation-email-subject', ['%code%' => $model->codigo]);
                    $body = Tools::trans('estimation-email-text', ['%code%' => $model->codigo]);
                    break;
            }
        }

        if (!empty($subject)) {
            $this->newMail->subject($subject);
        }

        if (!empty($body)) {
            $this->newMail->body($body);
        }

        $this->pipe('loadDataDefault', $model);
    }

    protected function redirectAfter(): void
    {
        $pipeUrl = $this->pipe('redirectAfter');
        if (is_string($pipeUrl)) {
            Tools::log()->notice('reloading');
            $this->redirect($pipeUrl, 3);
            return;
        }

        // si no existe la clase del modelo, recargamos la página de envío de email
        $className = self::MODEL_NAMESPACE . $this->request->queryOrInput('modelClassName');
        if (false === class_exists($className)) {
            Tools::log()->notice('reloading');
            $this->redirect('SendMail', 3);
            return;
        }

        // si existe el modelo, recargamos la página del modelo
        $model = new $className();
        $modelCode = $this->request->queryOrInput('modelCode');
        if ($model->load($modelCode) && $model->hasColumn('femail')) {
            Tools::log()->notice('reloading');
            $this->redirect($model->url(), 3);
        }
    }

    /**
     * Remove old files.
     */
    protected function removeOld(): void
    {
        foreach (glob(FS_FOLDER . '/MyFiles/*_mail_*.pdf') as $fileName) {
            $parts = explode('_', $fileName);
            $time = (int)substr(end($parts), 0, -4);
            if ($time < (time() - self::MAX_FILE_AGE)) {
                unlink($fileName);
            }
        }

        $this->pipe('removeOld');
    }

    /**
     * Return array with parameters values
     *
     * @param array $keys
     *
     * @return array
     */
    protected function requestGet(array $keys): array
    {
        $result = [];
        foreach ($keys as $value) {
            $result[$value] = $this->request->queryOrInput($value);
        }

        return $result;
    }

    protected function send(): bool
    {
        if ($this->newMail->fromEmail !== $this->user->email && $this->request->input('replyto', '0')) {
            $this->newMail->replyTo($this->user->email, $this->user->nick);
        }

        $emailFrom = $this->request->input('email-from', '');
        if (false === Validator::email($emailFrom)) {
            Tools::log()->error('invalid-email-from', ['%email%' => $emailFrom]);
            return false;
        }

        $this->newMail->setMailbox($emailFrom)
            ->subject($this->request->input('email-subject', ''))
            ->body($this->request->input('email-body', ''));

        // solo añadimos los emails que no estén ya en la lista
        $emailAddedTo = $this->newMail->getToAddresses();
        $emailInputTo = $this->getEmails('email-to');
        foreach ($emailInputTo as $email) {
            if (empty($email)) {
                continue;
            }

            if (false === Validator::email($email)) {
                Tools::log()->error('invalid-email-to', ['%email%' => $email]);
                return false;
            }

            if (false === in_array($email, $emailAddedTo)) {
                $this->newMail->to($email);
            }
        }

        // añadimos los emails en copia que no estén ya en la lista
        $emailAddedCC = $this->newMail->getCCAddresses();
        $emailInputCC = $this->getEmails('email-cc');
        foreach ($emailInputCC as $email) {
            if (empty($email)) {
                continue;
            }

            if (false === Validator::email($email)) {
                Tools::log()->error('invalid-email-cc', ['%email%' => $email]);
                return false;
            }

            if (false === in_array($email, $emailAddedCC)) {
                $this->newMail->cc($email);
            }
        }

        // añadimos los emails en copia oculta que no estén ya en la lista
        $emailAddedBCC = $this->newMail->getBCCAddresses();
        $emailInputBCC = $this->getEmails('email-bcc');
        foreach ($emailInputBCC as $email) {
            if (empty($email)) {
                continue;
            }

            if (false === Validator::email($email)) {
                Tools::log()->error('invalid-email-bcc', ['%email%' => $email]);
                return false;
            }

            if (false === in_array($email, $emailAddedBCC)) {
                $this->newMail->bcc($email);
            }
        }

        $this->setAttachment();
        $this->pipe('send');
        return $this->newMail->send();
    }

    protected function setAttachment(): void
    {
        $fileName = $this->request->queryOrInput('fileName', '');
        Tools::folderCheckOrCreate(NewMail::ATTACHMENTS_TMP_PATH);
        $this->newMail->addAttachment(FS_FOLDER . '/' . NewMail::ATTACHMENTS_TMP_PATH . $fileName, $fileName);

        foreach ($this->request->files->getArray('uploads') as $file) {
            // guardamos el adjunto en una carpeta temporal
            if ($file->move(NewMail::ATTACHMENTS_TMP_PATH, $file->getClientOriginalName())) {
                // añadimos el adjunto al email
                $filePath = FS_FOLDER . '/' . NewMail::ATTACHMENTS_TMP_PATH . $file->getClientOriginalName();
                $this->newMail->addAttachment($filePath, $file->getClientOriginalName());
            }
        }

        $this->pipe('setAttachment', $fileName);
    }

    protected function setEmail(): void
    {
        // estableceos el email de origen
        $emailFrom = $this->request->queryOrInput('email-from', '');
        if (Validator::email($emailFrom)) {
            foreach ($this->newMail->getAvailableMailboxes() as $mailbox) {
                if ($mailbox === $emailFrom) {
                    $this->newMail->setMailbox($emailFrom);
                    break;
                }
            }
        }

        // establecemos los destinatarios
        $emailTo = $this->request->queryOrInput('email-to', '');
        $emailTo = explode(',', str_replace(' ', '', $emailTo));
        foreach ($emailTo as $email) {
            if (Validator::email($email)) {
                $this->newMail->to($email);
            }
        }

        // establecemos los destinatarios en copia
        $emailCC = $this->request->queryOrInput('email-cc', '');
        $emailCC = explode(',', str_replace(' ', '', $emailCC));
        foreach ($emailCC as $email) {
            if (Validator::email($email)) {
                $this->newMail->cc($email);
            }
        }

        // establecemos los destinatarios en copia oculta
        $emailBCC = $this->request->queryOrInput('email-bcc', '');
        $emailBCC = explode(',', str_replace(' ', '', $emailBCC));
        foreach ($emailBCC as $email) {
            if (Validator::email($email)) {
                $this->newMail->bcc($email);
            }
        }

        // establecemos el asunto
        $emailSubject = $this->request->queryOrInput('email-subject', '');
        if (!empty($emailSubject)) {
            $this->newMail->subject($emailSubject);
        }

        // establecemos el cuerpo
        $emailBody = $this->request->queryOrInput('email-body', '');
        if (!empty($emailBody)) {
            $this->newMail->body($emailBody);
        }

        $this->pipe('setEmail');

        // comprobamos si existe la clase
        $className = self::MODEL_NAMESPACE . $this->request->queryOrInput('modelClassName', '');
        if (false === class_exists($className)) {
            return;
        }

        // comprobamos si existe el registro del modelo
        $model = new $className();
        if (false === $model->load($this->request->queryOrInput('modelCode', ''))) {
            return;
        }

        // cargamos los datos por defecto del modelo
        $this->loadDataDefault($model);

        if ($model->hasColumn('email') && Validator::email($model->email)) {
            $this->newMail->to($model->email);
            return;
        }

        $proveedor = new Proveedor();
        if ($model->hasColumn('codproveedor') && $proveedor->load($model->codproveedor) && Validator::email($proveedor->email)) {
            $this->newMail->to($proveedor->email, $proveedor->razonsocial);
            return;
        }

        $contact = new Contacto();
        if ($model->hasColumn('idcontactofact') && $contact->load($model->idcontactofact) && Validator::email($contact->email)) {
            $this->newMail->to($contact->email, $contact->fullName());
            return;
        }

        $cliente = new Cliente();
        if ($model->hasColumn('codcliente') && $cliente->load($model->codcliente) && Validator::email($cliente->email)) {
            $this->newMail->to($cliente->email, $cliente->razonsocial);
        }
    }

    /**
     * @deprecated Use setEmail() instead
     */
    protected function setEmailAddress(): void
    {
        $this->setEmail();
    }

    /**
     * Update the property femail with actual date if exist param ModelClassName and ModelCode.
     */
    protected function updateFemail(): void
    {
        $this->pipe('updateFemail');

        $className = self::MODEL_NAMESPACE . $this->request->queryOrInput('modelClassName');
        if (false === class_exists($className)) {
            return;
        }

        // marcamos la fecha del envío del email
        $model = new $className();
        $modelCode = $this->request->queryOrInput('modelCode');
        if ($model->load($modelCode) && $model->hasColumn('femail')) {
            $model->femail = Tools::date();
            if (false === $model->save()) {
                Tools::log()->error('record-save-error');
                return;
            }

            // si el sujeto no tiene email, le asignamos el del destinatario
            $subject = $model->getSubject();
            if (empty($subject->email)) {
                foreach ($this->newMail->getToAddresses() as $email) {
                    $subject->email = $email;
                    $subject->save();
                    break;
                }
            }
        }

        // si hay más documentos, marcamos también la fecha de envío
        $modelCodes = $this->request->queryOrInput('modelCodes', '');
        foreach (explode(',', $modelCodes) as $modelCode) {
            if ($model->load($modelCode) && $model->hasColumn('femail')) {
                $model->femail = Tools::date();
                $model->save();
            }
        }
    }
}
