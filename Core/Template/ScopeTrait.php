<?php

namespace FacturaScripts\Core\Template;

trait ScopeTrait
{
    /**
     * Callbacks de Scopes que se aplicarán a las consultas de todos los modelos que extiendan esta base.
     * Cada callback tiene la firma: function(array &$where, string $modelClass): void
     * y se espera que añada condiciones DataBaseWhere a $where.
     * @var array<int, callable>
     */
    protected static array $globalScopes = [];

    /**
     * Bandera para deshabilitar temporalmente los Scopes dentro de un contexto de ejecución dado.
     *
     * @var bool
     */
    protected static bool $disableGlobalScopes = false;

    /**
     * Ejecutar un callback sin aplicar los Scopes.
     *
     * @param callable $callback
     * @return mixed
     */
    public static function withoutGlobalScopes(callable $callback): mixed
    {
        $prev = self::$disableGlobalScopes;
        self::$disableGlobalScopes = true;
        try {
            return $callback();
        } finally {
            self::$disableGlobalScopes = $prev;
        }
    }

    /**
     * Registrar un callable de Scope que pueda modificar el array "where" de las consultas.
     *
     * @param callable $scope function(array &$where, string $modelClass): void
     */
    public static function addGlobalScope(callable $scope): void
    {
        self::$globalScopes[] = $scope;
    }

    /**
     * Aplica los Scopes registrados al array "where" proporcionado.
     *
     * @param array $where
     * @return array
     */
    protected static function applyGlobalScopesToWhere(array $where = []): array
    {
        if (self::$disableGlobalScopes || empty(self::$globalScopes)) {
            return $where;
        }

        foreach (self::$globalScopes as $scope) {
            // scope is expected to append DataBaseWhere instances or similar structures
            $scope($where, static::class);
        }

        return $where;
    }

}