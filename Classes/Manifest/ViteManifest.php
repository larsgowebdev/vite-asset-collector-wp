<?php

namespace Larsgowebdev\ViteAssetCollectorWp\Manifest;

use Larsgowebdev\ViteAssetCollectorWp\Exception\ViteException;

class ViteManifest
{
    /** @var array<string, ViteManifestItem> */
    private array $items;

    /**
     * @throws ViteException
     */
    public function __construct(string $jsonString, string $fileName = 'manifest.json')
    {
        try {
            $manifest = $this->validateAndSanitize($jsonString, $fileName);
            foreach ($manifest as $identifier => $item) {
                $this->items[$identifier] = ViteManifestItem::fromArray($item, $identifier);
            }
        } catch (ViteException $e) {
            throw new ViteException(
                "Failed to parse manifest file: " . $e->getMessage(),
                "Ensure your manifest.json is properly formatted",
                0,
                $e
            );
        }
    }

    /**
     * @throws ViteException
     */
    private function validateAndSanitize(string $jsonString, string $fileName): array
    {
        $manifest = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ViteException(
                "Invalid JSON in manifest file: " . json_last_error_msg(),
                "Check if your manifest.json is valid JSON"
            );
        }

        if (!is_array($manifest)) {
            throw new ViteException(
                "Manifest must be an array",
                "Check if your manifest.json contains a valid Vite manifest structure"
            );
        }

        return $manifest;
    }

    /**
     * @throws ViteException
     */
    public function get(string $entrypoint): ?ViteManifestItem
    {
        if (!isset($this->items[$entrypoint])) {
            throw new ViteException(
                "Entrypoint '{$entrypoint}' not found in manifest",
                "Verify that the entrypoint exists in your Vite configuration"
            );
        }
        return $this->items[$entrypoint];
    }

    /**
     * @return array<string, ViteManifestItem>
     */
    public function getValidEntrypoints(): array
    {
        return array_filter($this->items, fn (ViteManifestItem $entry) => $entry->isEntry);
    }

    /**
     * @return array<string, ViteManifestItem>
     * @throws ViteException
     */
    public function getImportsForEntrypoint(string $entrypoint): array
    {
        if (!isset($this->items[$entrypoint])) {
            throw new ViteException(
                "Cannot get imports: Entrypoint '{$entrypoint}' not found",
                "Check if the entrypoint is correctly specified in your configuration"
            );
        }

        $imports = [];
        foreach ($this->items[$entrypoint]->imports as $identifier) {
            $imports[$identifier] = $this->get($identifier);
        }
        return $imports;
    }

    /**
     * @throws ViteException
     */
    public static function fromFile(string $path): static
    {
        $manifestJson = file_get_contents($path);
        if ($manifestJson === false) {
            throw new ViteException(
                "Unable to read manifest file: {$path}",
                "Check file permissions and ensure the file exists"
            );
        }
        return new static($manifestJson, $path);
    }
}