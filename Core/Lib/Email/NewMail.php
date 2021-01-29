<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib\Email;

use FacturaScripts\Core\App\WebRender;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Dinamic\Model\EmailSent;
use FacturaScripts\Dinamic\Model\Empresa;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Description of NewMail
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class NewMail
{

    const DEFAULT_TEMPLATE = 'NewTemplate.html.twig';

    /**
     *
     * @var Empresa
     */
    public $empresa;

    /**
     *
     * @var string
     */
    public $fromEmail;

    /**
     *
     * @var string
     */
    public $fromName;

    /**
     *
     * @var string
     */
    public $fromNick;

    /**
     *
     * @var BaseBlock[]
     */
    protected $footerBlocks = [];

    /**
     *
     * @var PHPMailer
     */
    protected $mail;

    /**
     *
     * @var BaseBlock[]
     */
    protected $mainBlocks = [];

    /**
     *
     * @var string
     */
    public $signature;

    /**
     *
     * @var string
     */
    public $template;

    /**
     *
     * @var string
     */
    public $text;

    /**
     *
     * @var string
     */
    public $title;

    /**
     *
     * @var string
     */
    public $verificode;

    public function __construct()
    {
        $appSettings = $this->toolBox()->appSettings();

        $this->empresa = new Empresa();
        $this->empresa->loadFromCode($appSettings->get('default', 'idempresa'));

        $this->fromEmail = $appSettings->get('email', 'email');
        $this->fromName = $this->empresa->nombrecorto;

        $this->mail = new PHPMailer();
        $this->mail->CharSet = PHPMailer::CHARSET_UTF8;
        $this->mail->Mailer = $appSettings->get('email', 'mailer');
        $this->mail->SMTPAuth = true;
        $this->mail->AuthType = $appSettings->get('email', 'authtype', '');
        $this->mail->SMTPSecure = $appSettings->get('email', 'enc', '');
        $this->mail->Host = $appSettings->get('email', 'host');
        $this->mail->Port = $appSettings->get('email', 'port');
        $this->mail->Username = $appSettings->get('email', 'user') ? $appSettings->get('email', 'user') : $appSettings->get('email', 'email');
        $this->mail->Password = $appSettings->get('email', 'password');

        foreach (static::splitEmails($appSettings->get('email', 'emailcc')) as $email) {
            $this->addCC($email);
        }

        foreach (static::splitEmails($appSettings->get('email', 'emailbcc')) as $email) {
            $this->addBCC($email);
        }

        $this->signature = $appSettings->get('email', 'signature', '');
        $this->template = self::DEFAULT_TEMPLATE;
        $this->verificode = $this->toolBox()->utils()->randomString(20);
    }

    /**
     * 
     * @param string $email
     * @param string $name
     */
    public function addAddress(string $email, string $name = '')
    {
        $this->mail->addAddress($email, $name);
    }

    /**
     * Add attachments to the email.
     * 
     * @param string $path
     * @param string $name
     */
    public function addAttachment(string $path, string $name)
    {
        $this->mail->addAttachment($path, $name);
    }

    /**
     * 
     * @param string $email
     * @param string $name
     */
    public function addBCC(string $email, string $name = '')
    {
        $this->mail->addBCC($email, $name);
    }

    /**
     * 
     * @param string $email
     * @param string $name
     */
    public function addCC(string $email, string $name = '')
    {
        $this->mail->addCC($email, $name);
    }

    /**
     * 
     * @param BaseBlock $block
     */
    public function addFooterBlock($block)
    {
        $block->setVerificode($this->verificode);
        $this->footerBlocks[] = $block;
    }

    /**
     * 
     * @param BaseBlock $block
     */
    public function addMainBlock($block)
    {
        $block->setVerificode($this->verificode);
        $this->mainBlocks[] = $block;
    }

    /**
     * 
     * @param string $address
     * @param string $name
     */
    public function addReplyTo(string $address, string $name = '')
    {
        $this->mail->addReplyTo($address, $name);
    }

    /**
     * Check if the email is configured
     * 
     * @return bool
     */
    public function canSendMail()
    {
        return !empty($this->fromEmail) && !empty($this->mail->Password) && !empty($this->mail->Host);
    }

    /**
     * 
     * @return array
     */
    public function getAttachmentNames()
    {
        $names = [];
        foreach ($this->mail->getAttachments() as $attach) {
            $names[] = $attach[1];
        }

        return $names;
    }

    /**
     * Returns an array with available email trays
     * 
     * @return array
     */
    public function getAvailableMailboxes()
    {
        return empty($this->fromEmail) ? [] : [$this->fromEmail];
    }

    /**
     * 
     * @return array
     */
    public function getBCCAddresses(): array
    {
        $addresses = [];
        foreach ($this->mail->getBccAddresses() as $addr) {
            $addresses[] = $addr[0];
        }

        return $addresses;
    }

    /**
     * 
     * @return array
     */
    public function getCCAddresses(): array
    {
        $addresses = [];
        foreach ($this->mail->getCcAddresses() as $addr) {
            $addresses[] = $addr[0];
        }

        return $addresses;
    }

    /**
     * 
     * @return array
     */
    public function getToAddresses(): array
    {
        $addresses = [];
        foreach ($this->mail->getToAddresses() as $addr) {
            $addresses[] = $addr[0];
        }

        return $addresses;
    }

    /**
     * 
     * @return bool
     */
    public function send(): bool
    {
        if (empty($this->mail->Username) || empty($this->mail->Password)) {
            $this->toolBox()->i18nLog()->warning('email-not-configured');
            return false;
        }

        $this->mail->setFrom($this->fromEmail, $this->fromName);
        $this->mail->Subject = $this->title;
        $this->mail->msgHTML($this->renderHTML());

        if ('smtp' === $this->mail->Mailer && false === $this->mail->smtpConnect($this->smtpOptions())) {
            $this->toolBox()->i18nLog()->error('error', ['%error%' => $this->mail->ErrorInfo]);
            return false;
        }

        if ($this->mail->send()) {
            $this->saveMailSent();
            return true;
        }

        $this->toolBox()->i18nLog()->error('error', ['%error%' => $this->mail->ErrorInfo]);
        return false;
    }

    /**
     * 
     * @param string $emailFrom
     */
    public function setMailbox($emailFrom)
    {
        ;
    }

    /**
     * 
     * @param string $emails
     *
     * @return array
     */
    public static function splitEmails($emails): array
    {
        $return = [];
        foreach (\explode(',', $emails) as $part) {
            $email = \trim($part);
            if (!empty($part)) {
                $return[] = $email;
            }
        }

        return $return;
    }

    /**
     * Test the PHPMailer connection. Return the result of the connection.
     *
     * @return bool
     */
    public function test(): bool
    {
        switch ($this->toolBox()->appSettings()->get('email', 'mailer', '')) {
            case 'smtp':
                $this->mail->SMTPDebug = 3;
                return $this->mail->smtpConnect($this->smtpOptions());

            default:
                $this->toolBox()->i18nLog()->warning('not-implemented');
                return false;
        }
    }

    /**
     * 
     * @return array
     */
    protected function getFooterBlocks(): array
    {
        return \array_merge([new TextBlock($this->signature)], $this->footerBlocks);
    }

    /**
     * 
     * @return array
     */
    protected function getMainBlocks(): array
    {
        return \array_merge([new TextBlock($this->text)], $this->mainBlocks);
    }

    /**
     * 
     * @return string
     */
    protected function renderHTML(): string
    {
        $webRender = new WebRender();
        $webRender->loadPluginFolders();

        $params = [
            'empresa' => $this->empresa,
            'footerBlocks' => $this->getFooterBlocks(),
            'mainBlocks' => $this->getMainBlocks(),
            'title' => $this->title
        ];
        return $webRender->render('Email/' . $this->template, $params);
    }

    protected function saveMailSent()
    {
        /// get all email address
        $addresses = \array_merge($this->getToAddresses(), $this->getCcAddresses(), $this->getBccAddresses());

        /// save email sent
        foreach (\array_unique($addresses) as $address) {
            $emailSent = new EmailSent();
            $emailSent->addressee = $address;
            $emailSent->body = $this->text;
            $emailSent->nick = $this->fromNick;
            $emailSent->subject = $this->title;
            $emailSent->verificode = $this->verificode;
            $emailSent->save();
        }
    }

    /**
     * Returns the SMTP Options.
     *
     * @return array
     */
    protected function smtpOptions(): array
    {
        if ($this->toolBox()->appSettings()->get('email', 'lowsecure')) {
            return [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
        }

        return [];
    }

    /**
     * 
     * @return ToolBox
     */
    protected function toolBox()
    {
        return new ToolBox();
    }
}
