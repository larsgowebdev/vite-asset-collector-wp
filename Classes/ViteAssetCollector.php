<?php

namespace Larsgowebdev\ViteAssetCollectorWp;

use Larsgowebdev\ViteAssetCollectorWp\Manifest\ViteManifest;
use Roots\WPConfig\Config;

class ViteAssetCollector
{
    public const DEFAULT_PORT = 5173;
    protected bool $useDevServer = false;
    protected ?string $devServerUri;
    protected string $manifestPath;
    protected string $manifestUri;
    protected string $manifestPathRelativeToTheme;
    protected string $entryPath;

    public function __construct(string $manifest, string $entry)
    {
        $this->manifestPathRelativeToTheme = $manifest;
        $this->manifestPath = get_stylesheet_directory() . $manifest;
        $this->manifestUri = get_stylesheet_directory_uri() . $manifest;
        $this->entryPath = $entry;

        $this->useDevServer = $this->determineUseDevServer();
        $this->devServerUri = $this->determineDevServerUri();
    }

    public function injectViteAssets(): void
    {
        if ($this->useDevServer) {
            $this->addAssetsFromDevServer();
        } else {
            $this->addAssetsFromManifest();
        }
    }


    public function determineUseDevServer(): bool
    {
        if (empty(getenv('VITE_USE_DEV_SERVER')) || getenv('VITE_USE_DEV_SERVER') === 'auto') {
            // determine dev server use depending on environment
            if (Config::get('WP_ENVIRONMENT_TYPE') === 'development') {
                return true;
            } else {
                return false;
            }
        }
        if (getenv('VITE_USE_DEV_SERVER') === 'true') {
            return true;
        }
        return false;
    }

    /**
     * @return string the URI of the dev server
     */
    public function determineDevServerUri(): string
    {
        if (empty(getenv('VITE_USE_DEV_SERVER')) || getenv('VITE_DEV_SERVER_URI') === 'auto') {
            $vitePort = getenv('VITE_PRIMARY_PORT') ?: self::DEFAULT_PORT;
            $wpHome = getenv('WP_HOME');
            return $wpHome . ':' . $vitePort;
        }
        die("dev server uri could not be resolved");
    }

    public function addAssetsFromDevServer(): void {
        $devServerUri = $this->devServerUri;
        $entryPath = $this->entryPath;
        // enqueue assets from dev server
        add_action(
            'wp_head',
            function () use ($devServerUri, $entryPath) {
                $enqueueHmrClientUri = $devServerUri . '/@vite/client';
                $enqueueEntryPointUri = $devServerUri . '/' . $entryPath;

                // part 1: include entry point
                echo '<script type="module" src="' . $enqueueHmrClientUri . '"></script>';

                // part 2: include actual entry point
                echo '<script type="module" src="' . $enqueueEntryPointUri . '"></script>';
            }
        );
    }

    public function addAssetsFromManifest(
        bool $addCss = true,
    ): void {

        $manifest = $this->parseManifestFile($this->manifestPath);
        $outputDir = $this->determineBuildUriFromManifestLocation();

        if (!$outputDir) {
            die('output directory could not be resolved');
        }

        if (!$manifest->get($this->entryPath)?->isEntry) {
           die('Invalid vite entry point in manifest file');
        }

        // enqueue the manifest entrypoint as JS module
        $manifestEntrypoint = $manifest->get($this->entryPath);
        $entrypointIdentifier = md5($manifestEntrypoint->identifier);
        add_action( 'wp_enqueue_scripts', function() use ($entrypointIdentifier, $outputDir, $manifestEntrypoint) {
            wp_enqueue_script_module($entrypointIdentifier, $outputDir . $manifestEntrypoint->file);
        } );

        // add CSS files
        if ($addCss) {
            // import every css file required as import (from JS)
            foreach ($manifest->getImportsForEntrypoint($this->entryPath) as $import) {
                foreach ($import->css as $file) {
                    $identifier = md5($import->identifier);
                    add_action('wp_enqueue_scripts', function() use ($identifier, $outputDir, $file) {
                        wp_enqueue_style("vite:{$identifier}:{$file}", $outputDir . $file);
                    });
                }
            }

            // import every css file required as CSS (via SCSS etc)
            foreach ($manifest->get($this->entryPath)->css as $file) {
                add_action('wp_enqueue_scripts', function() use ($outputDir, $file) {
                    wp_enqueue_style("vite:{$file}", $outputDir . $file);
                });
            }
        }
    }

    protected function determineBuildUriFromManifestLocation(): ?string
    {
        // if we look at the Uri of our manifest.json, we need to do this:
        // Find the position of the last occurrence of '/.vite/'
        $pos = strrpos($this->manifestUri, '/.vite/');

        if ($pos !== false) {
            // Remove everything from '/.vite/' onwards
            return substr($this->manifestUri, 0, $pos) . '/';
        } else {
            // If '/.vite/' not found, use the original URI (or handle error as needed)
            return $this->manifestUri;
        }
    }

    protected function parseManifestFile(string $manifestFile): ViteManifest
    {
        return ViteManifest::fromFile($manifestFile);
    }

}