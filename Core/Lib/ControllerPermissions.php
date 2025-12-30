<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Model\RoleAccess;
use FacturaScripts\Core\Model\User;

/**
 * Manages user permissions for controller access and operations.
 * Determines what actions a user can perform on a specific page/controller.
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
final class ControllerPermissions
{
    private const DEFAULT_ACCESS_MODE = 1;
    private const ADMIN_ACCESS_MODE = 99;

    /** @var int Access level for the user (1 = basic, 99 = admin) */
    public $accessMode = self::DEFAULT_ACCESS_MODE;

    /** @var bool Whether the user can access this controller */
    public $allowAccess = false;

    /** @var bool Whether the user can delete records */
    public $allowDelete = false;

    /** @var bool Whether the user can export data */
    public $allowExport = false;

    /** @var bool Whether the user can import data */
    public $allowImport = false;

    /** @var bool Whether the user can update/edit records */
    public $allowUpdate = false;

    /** @var bool Whether the user can only see their own data */
    public $onlyOwnerData = false;

    public function __construct(?User $user = null, ?string $pageName = null)
    {
        if (!$this->hasValidParameters($user, $pageName)) {
            return;
        }

        if ($user->admin) {
            $this->grantAdminPermissions();
        } else {
            $this->loadUserPermissions($user->nick, $pageName);
        }
    }

    /**
     * Manually set permissions for this controller.
     *
     * @param bool $access Whether to allow access
     * @param int $accessMode The access level
     * @param bool $delete Whether to allow delete operations
     * @param bool $update Whether to allow update operations
     * @param bool $onlyOwner Whether to restrict to owner data only
     */
    public function set(bool $access, int $accessMode, bool $delete, bool $update, bool $onlyOwner = false): void
    {
        $this->accessMode = $accessMode;
        $this->allowAccess = $access;
        $this->allowDelete = $delete;
        $this->allowUpdate = $update;
        $this->onlyOwnerData = $onlyOwner;
    }

    /**
     * Set multiple permission parameters from an associative array.
     *
     * @param array $params Array with permission property names as keys
     */
    public function setParams(array $params): void
    {
        foreach ($params as $property => $value) {
            if (!$this->isValidProperty($property)) {
                continue;
            }

            $this->setProperty($property, $value);
        }
    }

    /**
     * Check if the provided parameters are valid for initialization.
     */
    private function hasValidParameters(?User $user, ?string $pageName): bool
    {
        return !empty($user) && !empty($pageName);
    }

    /**
     * Grant full admin permissions.
     */
    private function grantAdminPermissions(): void
    {
        $this->accessMode = self::ADMIN_ACCESS_MODE;
        $this->allowAccess = true;
        $this->allowDelete = true;
        $this->allowExport = true;
        $this->allowImport = true;
        $this->allowUpdate = true;
        $this->onlyOwnerData = false;
    }

    /**
     * Load and apply permissions for a regular user.
     */
    private function loadUserPermissions(string $userNick, string $pageName): void
    {
        foreach ($this->getUserAccess($userNick, $pageName) as $access) {
            $this->applyAccessRules($access);
        }
    }

    /**
     * Apply individual access rules from a RoleAccess object.
     */
    private function applyAccessRules($access): void
    {
        $this->allowAccess = true;
        $this->allowDelete = $access->allowdelete || $this->allowDelete;
        $this->allowExport = $access->allowexport || $this->allowExport;
        $this->allowImport = $access->allowimport || $this->allowImport;
        $this->allowUpdate = $access->allowupdate || $this->allowUpdate;
        $this->onlyOwnerData = $access->onlyownerdata || $this->onlyOwnerData;
    }

    /**
     * Check if a property exists and is valid for setting.
     */
    private function isValidProperty(string $property): bool
    {
        return property_exists($this, $property);
    }

    /**
     * Set a property value with appropriate type casting.
     */
    private function setProperty(string $property, $value): void
    {
        if ($property === 'accessMode') {
            $this->{$property} = (int)$value;
        } else {
            $this->{$property} = (bool)$value;
        }
    }

    /**
     * Get user access rules from cache or database.
     */
    protected function getUserAccess(string $nick, string $pageName): array
    {
        $cacheKey = $this->buildCacheKey($nick, $pageName);

        return Cache::remember($cacheKey, function () use ($nick, $pageName) {
            return RoleAccess::allFromUser($nick, $pageName);
        });
    }

    /**
     * Build a cache key for user access permissions.
     */
    private function buildCacheKey(string $nick, string $pageName): string
    {
        return 'model-RoleAccess-' . $nick . '-' . $pageName;
    }
}
