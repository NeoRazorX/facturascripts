<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\App\WebRender;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\Translator;
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
     * @var Translator
     */
    protected $i18n;

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
     * @var MiniLog
     */
    protected $miniLog;

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

    public function __construct()
    {
        $this->empresa = new Empresa();
        $this->empresa->loadFromCode(AppSettings::get('default', 'idempresa'));

        $this->fromName = $this->empresa->nombrecorto;
        $this->i18n = new Translator();

        $this->mail = new PHPMailer();
        $this->mail->CharSet = 'UTF-8';
        $this->mail->WordWrap = 50;
        $this->mail->Mailer = AppSettings::get('email', 'mailer');
        $this->mail->SMTPAuth = true;
        $this->mail->SMTPSecure = AppSettings::get('email', 'enc');
        $this->mail->Host = AppSettings::get('email', 'host');
        $this->mail->Port = AppSettings::get('email', 'port');
        $this->mail->Username = AppSettings::get('email', 'user') ? AppSettings::get('email', 'user') : AppSettings::get('email', 'email');
        $this->mail->Password = AppSettings::get('email', 'password');

        $this->miniLog = new MiniLog();
        $this->signature = AppSettings::get('email', 'signature', '');
        $this->template = self::DEFAULT_TEMPLATE;
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
        $this->footerBlocks[] = $block;
    }

    /**
     * 
     * @param BaseBlock $block
     */
    public function addMainBlock($block)
    {
        $this->mainBlocks[] = $block;
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
        if (empty(AppSettings::get('email', 'host'))) {
            $this->miniLog->alert($this->i18n->trans('email-not-configured'));
            return false;
        }

        $this->mail->setFrom(AppSettings::get('email', 'email'), $this->fromName);
        $this->mail->Subject = $this->title;
        $this->mail->msgHTML($this->renderHTML());

        if ($this->mail->smtpConnect($this->smtpOptions()) && $this->mail->send()) {
            $this->saveMailSent();
            return true;
        }

        $this->miniLog->warning($this->i18n->trans('error', ['%error%' => $this->mail->ErrorInfo]));
        return false;
    }

    /**
     * Test the PHPMailer connection. Return the result of the connection.
     *
     * @return bool
     */
    public function test(): bool
    {
        if (AppSettings::get('email', 'mailer') === 'smtp') {
            return $this->mail->smtpConnect($this->smtpOptions());
        }

        return true;
    }

    /**
     * 
     * @return array
     */
    protected function getFooterBlocks(): array
    {
        return array_merge([new TextBlock($this->signature)], $this->footerBlocks);
    }

    /**
     * 
     * @return array
     */
    protected function getMainBlocks(): array
    {
        return array_merge([new TextBlock($this->text)], $this->mainBlocks);
    }

    /**
     * 
     * @return string
     */
    private function renderHTML(): string
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

    private function saveMailSent()
    {
        /// get all email address
        $addresses = array_merge($this->getToAddresses(), $this->getCcAddresses(), $this->getBccAddresses());

        /// save email sent
        foreach (array_unique($addresses) as $address) {
            $emailSent = new EmailSent();
            $emailSent->addressee = $address;
            $emailSent->body = $this->text;
            $emailSent->nick = $this->fromNick;
            $emailSent->subject = $this->title;
            $emailSent->save();
        }
    }

    /**
     * Returns the SMTP Options.
     *
     * @return array
     */
    private function smtpOptions(): array
    {
        if (AppSettings::get('email', 'lowsecure')) {
            return [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];
        }

        return [];
    }
}
