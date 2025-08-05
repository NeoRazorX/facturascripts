<p align="center">
  <a href="https://facturascripts.com">
    <img src="https://upload.wikimedia.org/wikipedia/commons/d/de/Logo-FacturaScripts.png" width="300" title="Logo de FacturaScripts" alt="Logo de FacturaScripts">
  </a>
</p>

<p align="center">
  <strong>Software ERP y Contabilidad de Código Abierto</strong><br>
  Construido con PHP moderno y Bootstrap 5
</p>

<p align="center">
  <a href="https://opensource.org/licenses/LGPL"><img src="https://img.shields.io/badge/license-LGPL-green.svg?color=2670c9&style=for-the-badge&label=License&logoColor=000000&labelColor=ececec" alt="Licencia: LGPL"></a>
  <a href="https://github.com/NeoRazorX/facturascripts/releases/latest"><img src="https://img.shields.io/github/v/release/NeoRazorX/facturascripts?style=for-the-badge&logo=github&logoColor=white" alt="Última Versión"></a>
  <a href="https://github.com/NeoRazorX/facturascripts/releases"><img src="https://img.shields.io/github/downloads/NeoRazorX/facturascripts/total?style=for-the-badge&logo=github&logoColor=white" alt="Descargas Totales"></a>
  <a href="https://github.com/NeoRazorX/facturascripts/pulls"><img alt="Se aceptan Pull Request" src="https://img.shields.io/badge/PRs_Welcome-brightgreen?style=for-the-badge"></a>
</p>

<p align="center">
  <a href="https://facturascripts.com/probar-online">🚀 Probar Demo</a> •
  <a href="#-documentación">📚 Documentación</a> •
  <a href="https://discord.gg/qKm7j9AaJT">💬 Discord</a> •
  <a href="README.md">🇬🇧 English</a>
</p>

---

## 🎯 ¿Qué es FacturaScripts?

FacturaScripts es un **software ERP y de contabilidad de código abierto** integral diseñado para pequeñas y medianas empresas. Crea facturas, gestiona inventario, maneja la contabilidad y mucho más con una interfaz intuitiva y moderna.

### ✨ Características Principales

- 🧾 **Gestión de Facturas y Presupuestos** - Sistema profesional de facturación
- 📊 **Contabilidad y Finanzas** - Módulo completo de contabilidad
- 📦 **Gestión de Inventario** - Control de stock y gestión de productos
- 👥 **Gestión de Clientes y Proveedores** - Funcionalidad CRM
- 📈 **Informes y Análisis** - Información empresarial y reportes
- 🔌 **Sistema de Plugins** - Arquitectura extensible
- 🌍 **Multi-idioma** - Disponible en múltiples idiomas
- 📱 **Diseño Responsivo** - Funciona en escritorio y móvil

## ⚠️ Aviso sobre la Versión de Desarrollo

- Este repositorio contiene la **versión de desarrollo activo**
- Es probable que haya errores y cambios importantes
- Para **uso en producción**, descarga la versión estable desde [facturascripts.com/descargar](https://facturascripts.com/descargar)

## 🚀 Inicio Rápido

### Requisitos del Sistema
- PHP 8.0 o superior
- MySQL/MariaDB o PostgreSQL
- Composer
- Node.js y npm

### Instalación

```bash
# Clonar el repositorio
git clone https://github.com/NeoRazorX/facturascripts.git
cd facturascripts

# Instalar dependencias de PHP
composer install

# Instalar dependencias de JavaScript
npm install
```

### Ejecutar la Aplicación

**Opción 1: Servidor PHP Integrado (Desarrollo)**
```bash
# Iniciar el servidor de desarrollo
php -S localhost:8000 index.php
```
Luego visita http://localhost:8000 en tu navegador.

**Opción 2: Apache**
- Copia el proyecto a la raíz de documentos de Apache (ej. `/var/www/html/`)
- Asegúrate de que mod_rewrite esté habilitado
- Configura un virtual host que apunte al directorio del proyecto

**Opción 3: Nginx**
- Configura tu bloque de servidor Nginx para que apunte al directorio del proyecto
- Asegúrate de que PHP-FPM esté configurado correctamente
- Establece la raíz del documento en la carpeta del proyecto

## 📚 Documentación

- **Guía de Usuario**: [facturascripts.com/ayuda](https://facturascripts.com/ayuda)
- **Documentación para Desarrolladores**: [facturascripts.com/ayuda-dev](https://facturascripts.com/ayuda-dev)
- **Hoja de Ruta**: [facturascripts.com/roadmap](https://facturascripts.com/roadmap)

## 🧪 Pruebas

Ejecuta las pruebas para asegurar que todo funciona correctamente:

```bash
# Ejecutar pruebas PHPUnit
vendor/bin/phpunit

# Ejecutar análisis estático
vendor/bin/phpstan analyse Core
```

## 🤝 Contribuir

¡Damos la bienvenida a las contribuciones! Por favor, consulta nuestra [guía de contribución](https://facturascripts.com/colabora) antes de enviar pull requests.

## 💬 Soporte y Comunidad

- **Comunidad Discord**: [discord.gg/qKm7j9AaJT](https://discord.gg/qKm7j9AaJT)
- **Contacto y Soporte**: [facturascripts.com/contacto](https://facturascripts.com/contacto)
- **Traducciones**: [facturascripts.com/traducciones](https://facturascripts.com/traducciones)

### 🔒 Vulnerabilidades de Seguridad

Si descubres una vulnerabilidad de seguridad, por favor envía un correo electrónico a Carlos García a [carlos@facturascripts.com](mailto:carlos@facturascripts.com)

## 🔗 Recursos Útiles

- [📹 Curso de YouTube](https://www.youtube.com/watch?v=rGopZA3ErzE&list=PLNxcJ5CWZ8V6nfeVu6vieKI_d8a_ObLfY)
- [🧾 Programa para hacer facturas gratis](https://facturascripts.com/programa-para-hacer-facturas)
- [📋 Programa para hacer presupuestos gratis](https://facturascripts.com/programa-de-presupuestos)
- [📊 Programa de contabilidad gratis para autónomos](https://facturascripts.com/software-contabilidad)
- [🖨️ Programa para imprimir tickets](https://facturascripts.com/remote-printer)

---

<p align="center">
  Hecho con ❤️ por la comunidad de FacturaScripts
</p>