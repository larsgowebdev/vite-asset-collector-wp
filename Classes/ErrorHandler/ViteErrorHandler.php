<?php

namespace Larsgowebdev\WpMuplugin\ViteAssetCollectorWp\ErrorHandler;

use JetBrains\PhpStorm\NoReturn;
use Larsgowebdev\WpMuplugin\ViteAssetCollectorWp\Exception\ViteException;
use Roots\WPConfig\Config;

class ViteErrorHandler
{
    private bool $isDevelopment;

    public function __construct()
    {
        $this->isDevelopment = Config::get('WP_ENVIRONMENT_TYPE') === 'development';
    }

    public function handleError(ViteException $e): void
    {
        if ($this->isDevelopment) {
            $this->displayDevError($e);
        } else {
            $this->logProductionError($e);
        }
    }

    #[NoReturn]
    private function displayDevError(ViteException $e): void
    {
        wp_die(
            sprintf(
                '<h1>Vite Asset Collector Error</h1>
                <p><strong>Error:</strong> %s</p>
                <p><strong>File:</strong> %s</p>
                <p><strong>Line:</strong> %d</p>
                <div style="margin-top: 20px; padding: 10px; background: #f5f5f5;">
                    <pre>%s</pre>
                </div>',
                esc_html($e->getMessage()),
                esc_html($e->getFile()),
                $e->getLine(),
                esc_html($e->getTraceAsString())
            ),
            'Vite Asset Collector Error',
            ['response' => 500]
        );
    }

    private function logProductionError(ViteException $e): void
    {
        error_log(sprintf(
            '[Vite Asset Collector] Error: %s in %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ));
    }
}