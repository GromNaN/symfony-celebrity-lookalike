<?php

declare(strict_types=1);

use Symfony\Component\AssetMapper\ImportMap\ImportMapConfigBuilder;

return static function (ImportMapConfigBuilder $config): void {
    // Automatically detect controllers in the controllers/ directory
    $config->autoImportDirectory(__DIR__ . '/assets/controllers');

    // The path is relative to this file
    $config->autoProvideAssets(['assets/app.js']);

    // If the package is available in a CDN, it can be imported
    $config->addPackage('stimulus', [
        'url' => 'https://cdn.jsdelivr.net/npm/@hotwired/stimulus@3.2.2/+esm',
        'version' => '3.2.2',
    ]);
    
    $config->addPackage('@hotwired/stimulus', [
        'url' => 'https://cdn.jsdelivr.net/npm/@hotwired/stimulus@3.2.2/+esm',
        'version' => '3.2.2',
    ]);
    
    $config->addPackage('@symfony/stimulus-bundle', [
        'url' => 'https://cdn.jsdelivr.net/npm/@symfony/stimulus-bundle@2.24.0/+esm',
        'version' => '2.24.0',
    ]);
};
/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
];
