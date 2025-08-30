<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Template;

use FacturaScripts\Core\Base\DataBase;

/**
 * Template class for plugin migrations
 *
 * This class serves as a base for creating plugin-specific migrations that will be
 * executed only once and tracked in MyFiles/migrations.json to prevent re-execution.
 *
 * Usage example:
 *
 * In your plugin's Migrations directory, create a migration class:
 *
 * namespace PluginName\Migrations;
 *
 * use FacturaScripts\Core\Template\MigrationClass;
 *
 * class FixUsersTable extends MigrationClass
 * {
 *     const MIGRATION_NAME = 'fix_users_table_v1.2.0';
 *
 *     public function run(): void
 *     {
 *         if (!$this->db()->tableExists('users')) {
 *             return;
 *         }
 *
 *         $sql = "ALTER TABLE users ADD COLUMN new_field VARCHAR(50)";
 *         $this->db()->exec($sql);
 *     }
 * }
 *
 * Then in your plugin's Init.php update() method:
 *
 * use FacturaScripts\Core\Migrations;
 * use FacturaScripts\Plugins\PluginName\Migrations\FixUsersTable;
 *
 * public function update(): void
 * {
 *     Migrations::runPluginMigration(new FixUsersTable());
 * }
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
abstract class MigrationClass
{
    /**
     * Migration name identifier. Must be unique within the plugin.
     * It should be descriptive and include version/date information.
     * Example: 'my_plugin_fix_users_table_v1.2.0'
     */
    const MIGRATION_NAME = '';

    /** @var DataBase */
    private static $database;

    /**
     * Returns the migration name
     */
    public static function getMigrationName(): string
    {
        return static::MIGRATION_NAME;
    }

    /**
     * Returns the plugin name from the class namespace
     */
    public static function getPluginName(): string
    {
        $namespace = static::class;
        $parts = explode('\\', $namespace);
        return $parts[2] ?? 'Unknown';
    }

    /**
     * Returns the unique migration identifier including plugin name
     */
    public static function getFullMigrationName(): string
    {
        return self::getPluginName() . '::' . static::MIGRATION_NAME;
    }

    /**
     * Execute the migration logic
     */
    abstract public function run(): void;

    /**
     * Get database instance
     */
    public static function db(): DataBase
    {
        if (self::$database === null) {
            self::$database = new DataBase();
            self::$database->connect();
        }

        return self::$database;
    }
}
