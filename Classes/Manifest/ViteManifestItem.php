<?php

declare(strict_types=1);

namespace Larsgowebdev\WpMuplugin\ViteAssetCollectorWp\Manifest;

readonly class ViteManifestItem
{
    public function __construct(
        public string  $identifier,
        public ?string $src,
        public string  $file,
        public bool    $isEntry,
        public bool    $isDynamicEntry,
        public array   $assets,
        public array   $css,
        public array   $imports,
        public array   $dynamicImports,
    ) {}

    public static function fromArray(array $item, string $identifier): static
    {
        return new static(
            $identifier,
            $item['src'] ?? null,
            $item['file'],
            (bool)($item['isEntry'] ?? false),
            (bool)($item['isDynamicEntry'] ?? false),
            (array)($item['assets'] ?? []),
            (array)($item['css'] ?? []),
            (array)($item['imports'] ?? []),
            (array)($item['dynamicImports'] ?? []),
        );
    }
}
