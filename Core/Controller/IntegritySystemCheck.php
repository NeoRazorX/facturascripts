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

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Model\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * IntegritySystemCheck.
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class IntegritySystemCheck extends Base\Controller
{

    /**
     * List of files with integrity problems.
     *
     * @var array
     */
    public $integrity;

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'admin';
        $pageData['submenu'] = 'control-panel';
        $pageData['title'] = 'integrity-check';
        $pageData['icon'] = 'fas fa-shield';
        $pageData['showonmenu'] = false;

        return $pageData;
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response                   $response
     * @param User                       $user
     * @param Base\ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        // Store action to execute
        $action = $this->request->get('action', '');

        // Operations with data, before execute action
        if (!$this->execPreviousAction($action)) {
            return;
        }
        $this->integrity = Base\IntegrityCheck::compareIntegrity();

        // This check must be do it to have more real time notification if it's failling
        if ($this->user->admin && !empty($this->integrity)) {
            $this->miniLog->critical(
                '<i class="fas fa-exclamation-triangle" aria-hidden="true"></i>&nbsp;'
                . $this->i18n->trans('not-passed-integrity-check')
            );
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
            case 'system':
                Base\IntegrityCheck::saveIntegrity();
                return false;

            default:
                Base\IntegrityCheck::saveIntegrity(Base\IntegrityCheck::INTEGRITY_USER_FILE);
                break;
        }

        return true;
    }
}
