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

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\ApiAccess;

/**
 * ApiKey model to manage the connection tokens through the api
 * that will be generated to synchronize different applications.
 *
 * @author Joe Nilson           <joenilson at gmail.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class ApiKey extends ModelClass
{
    use ModelTrait;

    /** @var string */
    public $apikey;

    /** @var string */
    public $creationdate;

    /** @var string */
    public $description;

    /** @var bool */
    public $enabled;

    /** @var bool */
    public $fullaccess;

    /** @var int */
    public $id;

    /** @var string */
    public $nick;

    /**
     * Adds a new API access entry for the given resource with the specified permissions.
     *
     * If the resource already exists for this API key, no changes are made.
     *
     * @param string $resource Resource name to grant access to.
     * @param bool $state Initial permission state (applied to all methods).
     *
     * @return bool True if created or already exists, false on failure.
     */
    public function addAccess(string $resource, bool $state = false): bool
    {
        if (null !== $this->getAccess($resource)) {
            return true; // already exists
        }

        $apiAccess = new ApiAccess();
        $apiAccess->idapikey = $this->id;
        $apiAccess->resource = $resource;
        $apiAccess->allowdelete = $state;
        $apiAccess->allowget = $state;
        $apiAccess->allowpost = $state;
        $apiAccess->allowput = $state;

        return $apiAccess->save();
    }

    public function clear(): void
    {
        parent::clear();
        $this->apikey = Tools::randomString(20);
        $this->creationdate = Tools::date();
        $this->enabled = true;
        $this->fullaccess = false;
    }

    public function getAccesses(): array
    {
        $where = [Where::eq('idapikey', $this->id)];
        return ApiAccess::all($where, [], 0, 0);
    }

    /**
     * Retrieves the API access entry for the specified resource.
     *
     * Use addResourceAccess() first if the resource does not exist.
     *
     * @param string $resource Resource name to look up.
     *
     * @return ?ApiAccess The ApiAccess object if found, false otherwise.
     */
    public function getAccess(string $resource): ?ApiAccess
    {
        $apiAccess = new ApiAccess();
        $where = [
            Where::eq('idapikey', $this->id),
            Where::eq('resource', $resource)
        ];
        if ($apiAccess->loadWhere($where)) {
            return $apiAccess;
        }

        return null;
    }

    public function hasAccess(string $resource, string $permission = 'get'): bool
    {
        if ($this->fullaccess) {
            return true;
        }

        $access = $this->getAccess($resource);
        if (null === $access) {
            return false;
        }

        return match ($permission) {
            'delete' => $access->allowdelete ?? false,
            'get' => $access->allowget ?? false,
            'post' => $access->allowpost ?? false,
            'put' => $access->allowput ?? false,
            default => false,
        };
    }

    public function primaryDescriptionColumn(): string
    {
        return 'description';
    }

    public static function tableName(): string
    {
        return 'api_keys';
    }

    public function test(): bool
    {
        // escapamos el html
        $this->apikey = Tools::noHtml($this->apikey);
        $this->description = Tools::noHtml($this->description);
        $this->nick = Tools::noHtml($this->nick);

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'EditSettings?activetab=List'): string
    {
        return parent::url($type, $list);
    }
}
