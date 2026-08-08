<?php

namespace FacturaScripts\Test\Core\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

class GlobalScopeTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    private ?string $clienteOnCode;
    private ?string $clienteOffCode;

    protected function setUp(): void
    {
        // Crear dos clientes: uno activo (debaja = false) y otro dado de baja (debaja = true)
        $on = $this->getRandomCustomer();
        $on->riesgomax = 10.0;
        $this->assertTrue($on->save());
        $this->clienteOnCode = $on->codcliente;

        $off = $this->getRandomCustomer();
        $off->fechabaja = Tools::date();
        // Establece un valor numérico para probar totalSum
        $off->riesgomax = 10.0;
        $this->assertTrue($off->save());
        $this->clienteOffCode = $off->codcliente;

        // Registrar un Scope global que oculte clientes con debaja = true
        ModelClass::addGlobalScope(function (array &$where, string $modelClass): void {
            // Aplicar solo al modelo Cliente para evitar afectar a otros modelos que puedan usarse en otro lugar
            if ($modelClass === Cliente::class) {
                $where[] = new DataBaseWhere('debaja', false);
            }
        });
    }

    /**
     * Comprobamos que el metodo all() devuelve unicamente los que estan dado de alta
     * por lo que esta aplicando el Scope correctamente
     *
     * @return void
     */
    public function testAllIsFilteredByGlobalScope(): void
    {
        $all = Cliente::all();

        foreach ($all as $cli) {
            $this->assertFalse($cli->debaja);
        }
    }

    public function testCountIsFilteredByGlobalScope(): void
    {
        $model = new Cliente();
        $countAll = ModelClass::withoutGlobalScopes(function () use ($model) {
            return $model->count();
        });
        $countScoped = $model->count();
        $this->assertGreaterThan($countScoped - 1, $countAll);
        $this->assertGreaterThanOrEqual(1, $countScoped);
    }

    public function testGetAndLoadFromCodeRespectGlobalScope(): void
    {
        $c = new Cliente();

        // find() para el cliente dado de baja debe devolver false debido al Scope
        $this->assertNull($c->find($this->clienteOffCode));

        // load() para el cliente dado de baja debe devolver false y limpiar los atributos
        $this->assertFalse($c->load($this->clienteOffCode));

        // Sin Scopes, deberíamos poder obtenerlo
        $found = ModelClass::withoutGlobalScopes(function () {
            $tmp = new Cliente();
            return $tmp->find($this->clienteOffCode);
        });
        $this->assertNotNull($found);
    }

    public function testTotalSumIsFilteredByGlobalScope(): void
    {
        $model = new Cliente();
        $sumScoped = $model->totalSum('riesgomax');
        $sumAll = ModelClass::withoutGlobalScopes(function () use ($model) {
            return $model->totalSum('riesgomax');
        });

        $this->assertSame(10.0, (float)$sumScoped);
        $this->assertSame(20.0, (float)$sumAll);
    }

    protected function tearDown(): void
    {
        $this->logErrors();

        // Eliminar los clientes creados (saltando los Scopes globales para asegurar que podemos encontrarlos)
        ModelClass::withoutGlobalScopes(function () {
            if ($this->clienteOnCode) {
                $c = new Cliente();
                if ($c->load($this->clienteOnCode)) {
                    $c->delete();
                }
            }
            if ($this->clienteOffCode) {
                $c = new Cliente();
                if ($c->load($this->clienteOffCode)) {
                    $c->delete();
                }
            }
        });

        // Restablecer los Scopes globales mediante reflexión para evitar impactar a otras pruebas
        $ref = new \ReflectionClass(ModelClass::class);
        if ($ref->hasProperty('globalScopes')) {
            $prop = $ref->getProperty('globalScopes');
            $prop->setAccessible(true);
            $prop->setValue([]);
        }
        if ($ref->hasProperty('disableGlobalScopes')) {
            $prop2 = $ref->getProperty('disableGlobalScopes');
            $prop2->setAccessible(true);
            $prop2->setValue(false);
        }
    }
}
