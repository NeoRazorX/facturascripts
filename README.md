<p align="center">
  <a href="https://facturascripts.com">
    <img src="https://upload.wikimedia.org/wikipedia/commons/d/de/Logo-FacturaScripts.png" width="300" title="FacturaScripts Logo" alt="FacturaScripts Logo">
  </a>
</p>

<p align="center">
  <strong>Open Source ERP & Accounting Software</strong><br>
  Built with modern PHP and Bootstrap 5
</p>

<p align="center">
  <a href="https://opensource.org/licenses/LGPL"><img src="https://img.shields.io/badge/license-LGPL-green.svg?color=2670c9&style=for-the-badge&label=License&logoColor=000000&labelColor=ececec" alt="License: LGPL"></a>
  <a href="https://github.com/NeoRazorX/facturascripts/releases/latest"><img src="https://img.shields.io/github/v/release/NeoRazorX/facturascripts?style=for-the-badge&logo=github&logoColor=white" alt="Latest Release"></a>
  <a href="https://github.com/NeoRazorX/facturascripts/releases"><img src="https://img.shields.io/github/downloads/NeoRazorX/facturascripts/total?style=for-the-badge&logo=github&logoColor=white" alt="Total Downloads"></a>
  <a href="https://github.com/NeoRazorX/facturascripts/pulls"><img alt="PRs Welcome" src="https://img.shields.io/badge/PRs_Welcome-brightgreen?style=for-the-badge"></a>
</p>

<p align="center">
  <a href="https://facturascripts.com/probar-online">🚀 Try Demo</a> •
  <a href="#-documentation">📚 Documentation</a> •
  <a href="https://discord.gg/qKm7j9AaJT">💬 Discord</a> •
  <a href="README_ES.md">🇪🇸 Español</a>
</p>

---

## 🎯 What is FacturaScripts?

FacturaScripts is a comprehensive **open-source ERP and accounting software** designed for small and medium businesses. Create invoices, manage inventory, handle accounting, and much more with an intuitive and modern interface.

### ✨ Key Features

- 🧾 **Invoice & Quote Management** - Professional invoicing system
- 📊 **Accounting & Finance** - Complete accounting module
- 📦 **Inventory Management** - Stock control and product management  
- 👥 **Customer & Supplier Management** - CRM functionality
- 📈 **Reports & Analytics** - Business insights and reporting
- 🔌 **Plugin System** - Extensible architecture
- 🌍 **Multi-language** - Available in multiple languages
- 📱 **Responsive Design** - Works on desktop and mobile

## ⚠️ Development Version Notice

- This repository contains the **active development version**
- Expect bugs and breaking changes
- For **production use**, download the stable version from [facturascripts.com/descargar](https://facturascripts.com/descargar)

## 🚀 Quick Start

### System Requirements
- PHP 8.0 or higher
- MySQL/MariaDB or PostgreSQL
- Composer
- Node.js & npm

### Installation

```bash
# Clone the repository
git clone https://github.com/NeoRazorX/facturascripts.git
cd facturascripts

# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install
```

### Running the Application

**Option 1: PHP Built-in Server (Development)**
```bash
# Start the development server
php -S localhost:8000 index.php

# Or using Composer script
composer dev-server
```
Then visit http://localhost:8000 in your browser.

**Option 2: Apache**
- Copy the project to your Apache document root (e.g., `/var/www/html/`)
- Ensure mod_rewrite is enabled
- Configure virtual host pointing to the project directory

**Option 3: Nginx**
- Configure your Nginx server block to point to the project directory
- Ensure PHP-FPM is properly configured
- Set the document root to the project folder

## 📚 Documentation

- **Official User Course** (with official certification): [facturascripts.com/cursos/curso-de-usuario](https://facturascripts.com/cursos/curso-de-usuario)
- **User Guide**: [facturascripts.com/ayuda](https://facturascripts.com/ayuda)
- **Developer Documentation**: [facturascripts.com/ayuda-dev](https://facturascripts.com/ayuda-dev)
- **Roadmap**: [facturascripts.com/roadmap](https://facturascripts.com/roadmap)

## 🧪 Testing

Run the test suite to ensure everything works correctly:

```bash
# Run PHPUnit tests
vendor/bin/phpunit

# Run static analysis
vendor/bin/phpstan analyse Core
```

## 🤝 Contributing

We welcome contributions! Please check our [contribution guidelines](https://facturascripts.com/colabora) before submitting pull requests.

## 💬 Support & Community

- **Discord Community**: [discord.gg/qKm7j9AaJT](https://discord.gg/qKm7j9AaJT)
- **Contact & Support**: [facturascripts.com/contacto](https://facturascripts.com/contacto)
- **Translations**: [facturascripts.com/traducciones](https://facturascripts.com/traducciones)

### 🔒 Security Vulnerabilities

If you discover a security vulnerability, please email Carlos García at [carlos@facturascripts.com](mailto:carlos@facturascripts.com)

## 🔗 Useful Resources

- [🧾 Free Invoicing Software](https://facturascripts.com/programa-para-hacer-facturas)
- [📋 Free Quote Software](https://facturascripts.com/programa-de-presupuestos)
- [📊 Free Accounting for Freelancers](https://facturascripts.com/software-contabilidad)
- [🖨️ Ticket Printing](https://facturascripts.com/remote-printer)
- [📹 Old YouTube Course (Spanish)](https://www.youtube.com/watch?v=rGopZA3ErzE&list=PLNxcJ5CWZ8V6nfeVu6vieKI_d8a_ObLfY)

---

<p align="center">
  Made with ❤️ by the FacturaScripts community
</p>