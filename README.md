# Vite Asset Collector for WordPress

A WordPress Must-Use Plugin that seamlessly integrates Vite.js with WordPress themes, with live reload support for development and optimized asset loading for production.

This is heavily inspired by [vite-asset-collector](https://github.com/s2b/vite-asset-collector) for TYPO3, which made this project possible.

## Features

- CSS/SCSS/JS live reloading in development
- Optimized asset loading in production
- Automatic environment detection
- Comprehensive error handling and debugging

## Requirements

- PHP 8.1 or higher & Composer
- WordPress 6.0 or higher
- Node.js 18+ 
- Vite 5.3 or higher
- [Roots/wp-config](https://github.com/roots/wp-config)

## Recommendations for local Development

- Docker
- [DDEV](https://ddev.com/) 1.23 and higher
- [ddev-viteserve](https://github.com/torenware/ddev-viteserve) for integration of Vite's dev server into DDEV
- [vite-plugin-auto-origin](https://github.com/s2b/vite-plugin-auto-origin) for auto configuration of vite server origin url

## Installation

### As WordPress(Bedrock) dependency

In a composer based setup with Bedrock, this plugin will be automatically symlinked into your web/app/mu-plugins directory, and become available in your WP.

```bash
composer require larsgowebdev/vite-asset-collector-wp
```

## Configuration

### Environment Variables

Create a `.env` file in your WordPress root directory with the following variables:

```env
# WordPress Environment Type ('development', 'production', 'staging', etc.)
WP_ENVIRONMENT_TYPE=development

# WordPress base URL
WP_HOME=http://localhost  # Your WordPress installation URL

# Vite Configuration
VITE_USE_DEV_SERVER=auto  # 'auto', 'true', or 'false'
VITE_DEV_SERVER_URI=auto  # 'auto' or specific URI
```

Configuration requires an .env file based configuration, based on [roots/wp-config](https://github.com/roots/wp-config) and [oscarotero/env](https://github.com/oscarotero/env). It should be required by default in a Bedrock project.

### Theme Integration

In your theme's `functions.php`:

```php
use Larsgowebdev\ViteAssetCollectorWp\ViteAssetCollector;
use Larsgowebdev\ViteAssetCollectorWp\Exception\ViteException;
use Larsgowebdev\ViteAssetCollectorWp\ErrorHandler\ViteErrorHandler;

try {
    $vite = new ViteAssetCollector(
        '/.vite/manifest.json',  // Path to manifest relative to theme
        'frontend/vite.entry.js' // Entry point path
    );
    $vite->injectViteAssets();
} catch (ViteException $e) {
    (new ViteErrorHandler())->handleError($e);
}
```

### Vite Configuration

Example `vite.config.js`:

```javascript
import {defineConfig} from "vite";
import { dirname, resolve } from "node:path"
import { fileURLToPath } from "node:url"
import autoOrigin from "vite-plugin-auto-origin";

/*
 * universal constant configuration
 */
const ROOT_PATH = "./"
const currentDir = dirname(fileURLToPath(import.meta.url))
const rootPath = resolve(currentDir, ROOT_PATH)

const ALIASES = {
    '@Images': resolve(__dirname, 'path/to/my/frontend/src/images'),
    '@Fonts': resolve(__dirname, 'path/to/my/frontend/src/fonts'),
}

const ENTRY_POINTS = [
    "path/to/my/frontend/src/vite.entry.js",
]

const BUILD_PATH = "path/to/my/theme/assets/dist/"

/*
 * the actual config definition for vite.
 * Note that 'mode' is a parameter coming from cli/node (see package.json)
 */
export default defineConfig(() => {
    return {
        base: "",
        resolve: {
            alias: ALIASES
        },
        build: {
            manifest: true,
            rollupOptions: {
                input: ENTRY_POINTS.map(entry => resolve(rootPath, entry)),
            },
            outDir: resolve(rootPath, BUILD_PATH),
        },
        css: {
            devSourcemap: true,
        },
        plugins: [
            autoOrigin(),
            watchFiles(),
        ],
        publicDir: false,
    }
});

```

## Usage

### Development Mode

1. Start your Vite dev server:
```bash
npm run dev
```

2. The plugin will automatically detect development mode and inject HMR scripts.

### Production Mode

1. Build your assets:
```bash
npm run build
```

2. The plugin will automatically load optimized assets from the manifest file.

### Enabling file watchers in Vite

To enable watching files for changes and live reloading in your dev environment, you can add a little Vite Plugin:

```javascript
/*
 * Define a custom file watcher in vite plugin syntax
 * Will trigger a "hot update" (without full reload) when files are changed
 */
const watchFiles = () => ({
    name: 'watch-files',
    handleHotUpdate({file, server}) {
        const watchedExtensions = [
            // adjust for your desired extensions
            '.html',
            '.twig',
            '.php',
            '.scss',
            '.js',
            '.jpg',
            '.png',
            '.svg',
        ];
        if (watchedExtensions.some(ext => file.endsWith(ext))) {
            server.ws.send({
                type: 'full-reload',
                path: '*'
            });
        }
    }
})

export default defineConfig(() => {
    return {
        // other Options...
        plugins: [
            autoOrigin(),
            watchFiles(),
        ],
    }
});
```

## Enable DDEV Vite-Serve

The [ddev-viteserve](https://github.com/torenware/ddev-viteserve) plugin integrates your Vite dev-server into your environment.
Simply install it like this and restart your box:
```bash
ddev get torenware/ddev-viteserve
ddev restart
```

Then you can run your dev server like this:
```bash
ddev vite-serve
```

And to stop it:

```bash
ddev vite-serve stop
```

Most importantly, the viteserve plugin sets the port it uses as environment variable:
```bash
VITE_PRIMARY_PORT=5173
VITE_SECONDARY_PORT=5273
```

## Credits

This WordPress plugin is an adaptation of the "Vite Asset Collector" extension for TYPO3 CMS, originally created by Simon Praetorius. The original project can be found at:
- Original Project: https://github.com/s2b/vite-asset-collector
- Original Author: Simon Praetorius (https://github.com/s2b)

We thank the original author and contributors for their work, which served as the foundation for this WordPress adaptation.

## License

This project is licensed under the GNU General Public License v2.0 - see the [LICENSE](LICENSE) file for details.

This software is partly derived from "Vite Asset Collector" for TYPO3, which is also licensed under GPL-2.0. As per the GPL-2.0 terms, this derivative work maintains the same license.