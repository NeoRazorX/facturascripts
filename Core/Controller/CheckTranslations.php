<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
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

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Model\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller to generate random data
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class CheckTranslations extends Base\Controller
{
    /**
     * Default messages FacturaScripts language
     * @var array
     */
    public $msgDefaultLang;

    /**
     * Default messages user language
     * @var array
     */
    public $msgUserLanguage;

    /**
     * Default missing messages user language
     * @var array
     */
    public $misMsgUserLanguage;
    /**
     * Runs the controller's private logic.
     *
     * @param Response $response
     * @param User|null $user
     */
    public function privateCore(&$response, $user)
    {
        parent::privateCore($response, $user);
        $translator = new Base\Translator('en_EN');

        $this->msgDefaultLang = $translator->getMessages();
        $this->msgUserLanguage = $this->i18n->getMessages($this->i18n->getLangCode());
        $this->misMsgUserLanguage = $this->i18n->getMissingMessages($this->i18n->getLangCode());
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'admin';
        $pageData['title'] = 'check-translations';
        $pageData['icon'] = 'fa-language';
        $pageData['showonmenu'] = false;

        return $pageData;
    }
}
