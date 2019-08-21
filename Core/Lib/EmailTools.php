<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\App\WebRender;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Dinamic\Model\EmailSent;
use FacturaScripts\Dinamic\Model\Settings;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Tools for send emails.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa    <jcuello@artextrading.com>
 */
class EmailTools
{

    const DEFAULT_TEMPLATE = 'BasicTemplate.html.twig';

    /**
     * Settings properties for email
     *
     * @var array
     */
    private static $settings;

    /**
     * EmailTools constructor.
     */
    public function __construct()
    {
        if (!isset(self::$settings)) {
            $this->reloadConfig();
        }
    }

    /**
     * Add attachments to the email.
     *
     * @param PHPMailer    $mail
     * @param string|array $files
     */
    public function addAttachment(&$mail, $files)
    {
        if (is_array($files)) {
            foreach ($files as $file) {
                $mail->addAttachment($file->getPathname(), $file->getClientOriginalName());
            }

            return;
        }

        $mail->addAttachment(\FS_FOLDER . '/MyFiles/' . $files);
    }

    /**
     * Add the recipients of the emails
     * (List of email addresses separated by a comma)
     *
     * @param PHPMailer $mail
     * @param string    $emails
     * @param string    $emailsCC
     * @param string    $emailsBCC
     */
    public function addEmails(&$mail, string $emails, string $emailsCC = '', string $emailsBCC = '')
    {
        foreach ($this->getEmails($emails) as $email) {
            $mail->addAddress($email);
        }

        foreach ($this->getEmails($emailsCC) as $email) {
            $mail->addCC($email);
        }

        foreach ($this->getEmails($emailsBCC) as $email) {
            $mail->addBCC($email);
        }
    }

    /**
     * Returns the HTML code for the email from a template.
     *
     * @param array  $params
     * @param string $template
     * @param array  $objects
     *
     * @return string
     */
    public function getTemplateHtml(array $params, string $template = self::DEFAULT_TEMPLATE, array $objects = []): string
    {
        /// If it's a basic template, load basic data
        if ($template === self::DEFAULT_TEMPLATE) {
            $params['body'] = $params['body'] ?? '-';
            $params['company'] = $params['company'] ?? '-';
            $params['footer'] = $params['footer'] ?? '-';
            $params['title'] = $params['title'] ?? '-';
        }

        /// Load template and render html
        $webRender = new WebRender();
        $webRender->loadPluginFolders();
        $html = $webRender->render('Email/' . $template, $objects);

        /// replace values
        $search = [];
        $replace = [];
        foreach ($params as $key => $value) {
            $search[] = '[[' . $key . ']]';
            $replace[] = $value;
        }

        return str_replace($search, $replace, $html);
    }

    /**
     * Create new PHPMailer connection with stored settings.
     * 
     * @param string $fromName
     *
     * @return PHPMailer
     */
    public function newMail($fromName = '')
    {
        $mail = new PHPMailer();
        $mail->CharSet = 'UTF-8';
        $mail->WordWrap = 50;
        $mail->Mailer = $this->getSetting('mailer');
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = $this->getSetting('enc');
        $mail->Host = $this->getSetting('host');
        $mail->Port = $this->getSetting('port');
        $mail->Username = $this->getSetting('user') ? $this->getSetting('user') : $this->getSetting('email');
        $mail->Password = $this->getSetting('password');
        $mail->setFrom($this->getSetting('email'), $fromName);

        return $mail;
    }

    /**
     * Reload all email settings properties.
     */
    public function reloadConfig()
    {
        $settingsModel = new Settings();
        $emailSettings = $settingsModel->get('email');
        if ($emailSettings) {
            self::$settings = $emailSettings->properties;
        }
    }

    /**
     * Send an email, returns True on success, False on failure.
     *
     * @param PHPMailer $mail
     *
     * @return bool
     */
    public function send($mail)
    {
        if (null === $this->getSetting('host')) {
            return false;
        }

        if ($this->getSetting('mailer') === 'smtp' && !$mail->smtpConnect($this->smtpOptions())) {
            $this->toolBox()->i18nLog()->error('error', ['%error%' => $mail->ErrorInfo]);
            return false;
        }

        if ($mail->send()) {
            /// get all email address
            $addresses = [];
            foreach ($mail->getToAddresses() as $addr) {
                $addresses[] = $addr[0];
            }
            foreach ($mail->getCcAddresses() as $addr) {
                $addresses[] = $addr[0];
            }
            foreach ($mail->getBccAddresses() as $addr) {
                $addresses[] = $addr[0];
            }

            /// save email sent
            foreach (array_unique($addresses) as $address) {
                $emailSent = new EmailSent();
                $emailSent->addressee = $address;
                $emailSent->body = $mail->Body;
                $emailSent->subject = $mail->Subject;
                $emailSent->save();
            }
            return true;
        }

        $this->toolBox()->i18nLog()->error('error', ['%error%' => $mail->ErrorInfo]);
        return false;
    }

    /**
     * Send an email according to the information provided
     *
     * @param array $data
     *   subject: (string) Message Subject. (required)
     *   body     : (string) Message Body. It can be an html text. (required if you do not use template)
     *   email    : (string) List of email addresses separated by a comma. (required)
     *   email-cc : (string) List of cc-email addresses separated by a comma.
     *   email-bcc: (string) List of bcc-email (or cco-email) addresses separated by a comma.
     *   files    : (string|array) Path file or List of "file" objects to attach to the mail.
     *
     *   template : (string) Template twig with which to make the body of the message. Filename into /Dinamic/Assets/Email/
     *
     *   template-data: (array) List of values to use in the template.
     *      Custom data example: [ 'mydate' => '01/01/2019', 'myvalue' => 'custom value' ]
     *
     *   template-object: (array) List of objects to be passed to the template. These will be available in the Twig template
     *      Object list example: [ 'fsc' => $this, 'company' => $company ]
     *
     * @return bool
     */
    public static function sendMail(array $data): bool
    {
        /// Prepare email object
        $emailTools = new EmailTools();
        $mail = $emailTools->newMail();
        $mail->Subject = $data['subject'];

        /// Set email list
        $data['email-cc'] = $data['email-cc'] ?? '';
        $data['email-bcc'] = $data['email-bcc'] ?? '';
        $emailTools->addEmails($mail, $data['email'], $data['email-cc'], $data['email-bcc']);

        /// Set attachment files
        if (isset($data['files'])) {
            $emailTools->addAttachment($mail, $data['files']);
        }

        /// Load template and set data.
        $data['template-data'] = $data['template-data'] ?? [];
        $data['template-object'] = $data['template-object'] ?? [];
        $body = $emailTools->getTemplateHtml($data['template-data'], $data['template'], $data['template-object']);

        $mail->msgHTML($body);

        /// Send Email
        if ($emailTools->send($mail)) {
            /// Remove upload files
            if (!empty($data['fileName']) && file_exists(\FS_FOLDER . '/MyFiles/' . $data['fileName'])) {
                unlink(\FS_FOLDER . '/MyFiles/' . $data['fileName']);
            }

            static::toolBox()->i18nLog()->notice('send-mail-ok');
            return true;
        }

        return false;
    }

    /**
     * Test the PHPMailer connection. Return the result of the connection.
     *
     * @return bool
     */
    public function test()
    {
        switch ($this->getSetting('mailer')) {
            case 'smtp':
                return $this->newMail()->smtpConnect($this->smtpOptions());

            default:
                return true;
        }
    }

    /**
     * Converts a mailing list string into an array.
     *
     * @param string $emails
     *
     * @return array
     */
    protected function getEmails(string $emails): array
    {
        if (empty($emails)) {
            return [];
        }

        // Remove unneeded spaces and posible ending comma, before to convert into array
        return explode(',', rtrim(trim($emails), ','));
    }

    /**
     * Get value from Application Email Settings
     *
     * @param string $key
     *
     * @return mixed
     */
    protected function getSetting(string $key)
    {
        return isset(self::$settings[$key]) ? self::$settings[$key] : null;
    }

    /**
     * Returns the SMTP Options.
     *
     * @return array
     */
    protected function smtpOptions()
    {
        $SMTPOptions = [];
        if (isset(self::$settings['lowsecure']) && self::$settings['lowsecure']) {
            $SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];
        }

        return $SMTPOptions;
    }

    /**
     * 
     * @return ToolBox
     */
    protected static function toolBox()
    {
        return new ToolBox();
    }
}
