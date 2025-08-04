# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Architecture Overview

This is a **Botble CMS-based e-commerce application** built on Laravel 12. The architecture follows a modular plugin system with distinct separation between core platform, plugins, themes, and the main Laravel application.

### Key Structural Components

- **`/platform/core/`** - Core Botble framework modules (ACL, base functionality, media, settings, etc.)
- **`/platform/plugins/`** - Feature plugins (ecommerce, payment gateways, newsletter, blog, etc.)
- **`/platform/themes/`** - Frontend themes (currently "Wowy" theme)
- **`/app/`** - Standard Laravel application directory
- **Main Laravel app** - Acts as the host application that loads the Botble platform

### Plugin System Architecture

Plugins are self-contained modules with their own:
- Service providers
- Database migrations
- Routes
- Views and assets
- Configuration files
- Individual `plugin.json` metadata files
- Individual `webpack.mix.js` build configurations

Each plugin follows the namespace pattern `Botble\PluginName\` and registers via service providers.

## Development Commands

### Frontend Build Commands
```bash
# Development build
npm run dev

# Watch for changes
npm run watch

# Production build
npm run prod

# Format code
npm run format
```

### Laravel/PHP Commands
```bash
# Run database migrations
php artisan migrate

# Serve development server
php artisan serve

# Clear application cache
php artisan optimize:clear

# Publish CMS assets (important after updates)
php artisan cms:publish:assets

# Install/setup
php artisan cms:install
```

### Testing
```bash
# Run all tests
php artisan test
# or
vendor/bin/phpunit

# Run specific test suite
vendor/bin/phpunit tests/Feature
vendor/bin/phpunit tests/Unit
```

### Code Quality
```bash
# Format PHP code
vendor/bin/pint

# Static analysis
vendor/bin/phpstan analyse
```

## Build System

Uses **Laravel Mix** with a sophisticated workspace system:
- Main `webpack.mix.js` dynamically includes webpack configs from all plugins/themes
- Individual plugins/themes have their own `webpack.mix.js` files
- Supports building specific components via environment variables:
  - `npm_config_theme=wowy npm run dev`
  - `npm_config_plugin=ecommerce npm run dev`
  - `npm_config_core=base npm run dev`

## Database Architecture

- **Composer merge plugin** automatically includes database migrations from all plugins
- Migrations are located in each plugin's `database/migrations/` directory
- Main Laravel migrations in `/database/migrations/`
- Uses SQLite for testing (`:memory:`) and configurable database for development

## Payment Integrations

Multiple payment gateways available as plugins:
- **Wompi** (Colombian payment gateway) - Custom implementation
- Stripe, PayPal, PayStack, Razorpay, Mollie, SSLCommerz

Each payment plugin is self-contained with its own controllers, services, and configuration.

## Key Configuration Files to Check

- **Plugin-specific configs**: Each plugin has its own config files in `platform/plugins/{plugin}/config/`
- **Core configs**: `platform/core/*/config/` directories
- **Laravel configs**: Standard `/config/` directory
- **Environment**: `.env` file (check `.env.example` for required variables)

## Development Workflow Notes

- **Plugin development**: Work within `platform/plugins/{plugin-name}/` structure
- **Theme customization**: Modify files in `platform/themes/wowy/`
- **Asset compilation**: Each plugin/theme builds independently, use appropriate npm commands
- **Database changes**: Create migrations in appropriate plugin directories
- **CMS updates**: Run `php artisan cms:publish:assets` after platform updates

## File Structure Patterns

- **Controllers**: `src/Http/Controllers/`
- **Models**: `src/Models/`
- **Service Providers**: `src/Providers/`
- **Routes**: `routes/web.php` (in each plugin)
- **Views**: `resources/views/`
- **Assets**: `resources/js/`, `resources/sass/`, `public/`
- **Translations**: `resources/lang/`

## Important Notes

- This uses **Botble CMS** as the foundation, not pure Laravel
- The `wikimedia/composer-merge-plugin` automatically merges dependencies from all plugins
- Frontend uses **Vue.js 3** and **Bootstrap 5**
- Each plugin is essentially a mini-Laravel application with full MVC structure
- The system supports multi-language with translation files in multiple plugins