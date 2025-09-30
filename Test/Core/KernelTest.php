<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core;

use FacturaScripts\Core\Kernel;
use FacturaScripts\Core\Tools;
use PHPUnit\Framework\TestCase;

final class KernelTest extends TestCase
{
    protected function tearDown(): void
    {
        // Limpiar rutas después de cada test para evitar interferencias
        Kernel::clearRoutes();
        parent::tearDown();
    }

    public function testVersion(): void
    {
        $this->assertIsFloat(Kernel::version());
        $this->assertGreaterThan(2023.0, Kernel::version());
    }

    public function testTimers(): void
    {
        // iniciamos un temporizador
        $name = 'test-timer';
        Kernel::startTimer($name);

        // comprobamos que el temporizador existe
        $timers = Kernel::getTimers();
        $this->assertArrayHasKey($name, $timers);

        // comprobamos que el temporizador tiene un tiempo mayor que 0
        $this->assertGreaterThan(0, $timers[$name]['start']);

        // esperamos 1 segundo
        sleep(1);

        // paramos el temporizador
        $total = Kernel::stopTimer($name);

        // comprobamos que el temporizador tiene un tiempo mayor o igual a 1 segundo
        $this->assertGreaterThanOrEqual(1, $total);

        // ahora obtenemos el temporizador
        $timer = Kernel::getTimer($name);

        // comprobamos que el temporizador tiene un tiempo mayor o igual a 1 segundo
        $this->assertGreaterThanOrEqual(1, $timer);
    }

    public function testLockAndUnlock(): void
    {
        $processName = 'test-process-' . uniqid();
        
        // Test: adquirir lock por primera vez debe funcionar
        $this->assertTrue(Kernel::lock($processName), 'No se pudo adquirir el lock inicial');
        
        // Test: intentar adquirir el mismo lock debe fallar
        $this->assertFalse(Kernel::lock($processName), 'Se adquirió el lock duplicado');
        
        // Test: desbloquear debe funcionar
        $this->assertTrue(Kernel::unlock($processName), 'No se pudo desbloquear');
        
        // Test: intentar desbloquear un lock inexistente debe fallar
        $this->assertFalse(Kernel::unlock($processName), 'Se desbloqueó un lock inexistente');
        
        // Test: después de desbloquear, se puede volver a adquirir el lock
        $this->assertTrue(Kernel::lock($processName), 'No se pudo readquirir el lock');
        
        // Limpieza
        Kernel::unlock($processName);
    }

    public function testLockWithOldFile(): void
    {
        $processName = 'test-old-lock-' . uniqid();
        $lockFile = Tools::folder('MyFiles', 'lock_' . md5($processName) . '.lock');
        
        // Crear directorio MyFiles si no existe
        Tools::folderCheckOrCreate(Tools::folder('MyFiles'));
        
        // Crear un archivo lock antiguo (más de 8 horas)
        file_put_contents($lockFile, $processName);
        touch($lockFile, time() - 30000); // 8.33 horas atrás
        
        // Test: debe poder adquirir el lock eliminando el antiguo
        $this->assertTrue(Kernel::lock($processName), 'No se pudo adquirir lock con archivo antiguo');
        
        // Verificar que el nuevo archivo lock existe
        $this->assertFileExists($lockFile);
        
        // Verificar que el archivo tiene timestamp reciente
        $this->assertGreaterThan(time() - 60, filemtime($lockFile));
        
        // Limpieza
        Kernel::unlock($processName);
    }

    public function testSaveRoutes(): void
    {
        // Añadir algunas rutas de prueba
        Kernel::addRoute('/test-route-1', 'TestController1');
        Kernel::addRoute('/test-route-2', 'TestController2', 1);
        Kernel::addRoute('/test-route-3', 'TestController3', 0, 'custom-id-test');
        
        // Test: guardar rutas debe funcionar
        $this->assertTrue(Kernel::saveRoutes(), 'No se pudieron guardar las rutas');
        
        // Verificar que el archivo existe
        $routesFile = Tools::folder('MyFiles', 'routes.json');
        $this->assertFileExists($routesFile);
        
        // Verificar que el contenido es JSON válido
        $content = file_get_contents($routesFile);
        $routes = json_decode($content, true);
        $this->assertIsArray($routes, 'El archivo routes.json no contiene JSON válido');
        
        // Verificar que las rutas de prueba están en el archivo
        $this->assertArrayHasKey('/test-route-1', $routes);
        $this->assertArrayHasKey('/test-route-2', $routes);
        $this->assertArrayHasKey('/test-route-3', $routes);
        
        // Verificar la estructura de las rutas guardadas
        $this->assertEquals('TestController1', $routes['/test-route-1']['controller']);
        $this->assertEquals(1, $routes['/test-route-2']['position']);
        $this->assertEquals('custom-id-test', $routes['/test-route-3']['customId']);
    }

    public function testAddRouteWithCustomId(): void
    {
        $customId = 'test-custom-id-' . uniqid();
        
        // Añadir ruta con customId
        Kernel::addRoute('/test-route-a', 'ControllerA', 0, $customId);
        
        // Guardar y verificar
        $this->assertTrue(Kernel::saveRoutes());
        $routesFile = Tools::folder('MyFiles', 'routes.json');
        $routes = json_decode(file_get_contents($routesFile), true);
        $this->assertEquals('ControllerA', $routes['/test-route-a']['controller']);
        
        // Añadir otra ruta con el mismo customId (debe reemplazar la anterior)
        Kernel::addRoute('/test-route-b', 'ControllerB', 0, $customId);
        
        // Guardar y verificar que la ruta anterior fue eliminada
        $this->assertTrue(Kernel::saveRoutes());
        $routes = json_decode(file_get_contents($routesFile), true);
        $this->assertArrayNotHasKey('/test-route-a', $routes, 'La ruta anterior no fue eliminada');
        $this->assertArrayHasKey('/test-route-b', $routes);
        $this->assertEquals('ControllerB', $routes['/test-route-b']['controller']);
    }
}
