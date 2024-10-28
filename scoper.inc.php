<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

$polyfillsBootstraps = array_map(
    static fn (SplFileInfo $fileInfo): string => $fileInfo->getPathname(),
    iterator_to_array(
        Finder::create()
            ->files()
            ->in(__DIR__.'/vendor/symfony/polyfill-*')
            ->name('bootstrap*.php'),
        false,
    ),
);

$polyfillsStubs = array_map(
    static fn (SplFileInfo $fileInfo): string => $fileInfo->getPathname(),
    iterator_to_array(
        Finder::create()
            ->files()
            ->in(__DIR__.'/vendor/symfony/polyfill-*/Resources/stubs')
            ->name('*.php'),
        false,
    ),
);

$symfonyDeprecationContracts = array_map(
    static fn (SplFileInfo $fileInfo): string => $fileInfo->getPathname(),
    iterator_to_array(
        Finder::create()
            ->files()
            ->in(__DIR__.'/vendor/symfony/deprecation-contracts')
            ->name('*.php'),
        false,
    ),
);

return [
    'prefix' => '_ContaoManager',
    'whitelist' => [
        Finder::class,
    ],
    'exclude-namespaces' => [
        'Symfony\Polyfill',
        'React\Promise',
        '/^Composer/',
    ],
    'exclude-constants' => [
        '/^SYMFONY\_[\p{L}_]+$/',
        '/^COMPOSER(\_[\p{L}_]+)?$/',
    ],
    'exclude-functions' => [
        'trigger_deprecation',
    ],
    'exclude-files' => [
        ...$polyfillsBootstraps,
        ...$polyfillsStubs,
        ...$symfonyDeprecationContracts,
    ],

    'patchers' => [
        static function (string $filePath, string $prefix, string $contents): string {
            if ('src/Reflector.php' !== $filePath) {
                return $contents;
            }

            $originalContents = file_get_contents(__DIR__.'/src/Reflector.php');

            $classPosition = mb_strpos($originalContents, 'final class Reflector');
            $prefixedClassPosition = mb_strpos($contents, 'final class Reflector');

            return sprintf(
                '%s%s',
                mb_substr($contents, 0, $prefixedClassPosition),
                mb_substr($originalContents, $classPosition),
            );
        },
        static function (string $filePath, string $prefix, string $contents): string {
            if ('bin/php-scoper' !== $filePath) {
                return $contents;
            }

            return str_replace(
                '\\'.$prefix.'\Isolated\Symfony\Component\Finder\Finder::class',
                '\Isolated\Symfony\Component\Finder\Finder::class',
                $contents,
            );
        },
        static function (string $filePath, string $prefix, string $contents): string {
            $files = ['vendor/symfony/dependency-injection/Loader/Configurator/Traits/ParentTrait.php', 'vendor/symfony/dependency-injection/Compiler/ResolveInstanceofConditionalsPass.php'];
            if (!in_array($filePath, $files, true)) {
                return $contents;
            }

            return str_replace(
                [
                    '$definition = \substr_replace($definition, \'53\', 2, 2);',
                    '$definition = \substr_replace($definition, \'Child\', 44, 0);',
                ],
                [
                    '$definition = \substr_replace($definition, \''.(53 + strlen($prefix.'\\')).'\', 2, 2);',
                    '$definition = \substr_replace($definition, \'Child\', '.(44 + strlen($prefix.'\\')).', 0);',
                ],
                $contents,
            );
        },
        static function (string $filePath, string $prefix, string $contents): string {
            if (!str_starts_with($filePath, 'vendor/composer/composer/src/Composer/Package')) {
                return $contents;
            }

            return str_replace("'$prefix\\\\", "'", $contents);
        },

        // Fix error templates (e.g. /vendor/symfony/error-handler/Resources/views)
        static function (string $filePath, string $prefix, string $contents): string {
            if (!str_starts_with($filePath, 'vendor/symfony/error-handler/Resources/')) {
                return $contents;
            }

            return str_replace(
                [
                    "namespace $prefix;",
                    'echo Symfony\Component\HttpKernel\Kernel::VERSION',
                ],
                [
                    '',
                    "echo \\$prefix\\Symfony\\Component\\HttpKernel\\Kernel::VERSION",
                ],
                $contents,
            );
        },

        // Disable HtmlErrorRenderer::fileExcerpt breaking due to high memory load
        static function (string $filePath, string $prefix, string $contents): string {
            if ('vendor/symfony/error-handler/ErrorRenderer/HtmlErrorRenderer.php' !== $filePath) {
                return $contents;
            }

            return str_replace('private function fileExcerpt(string $file, int $line, int $srcContext = 3) : string
    {', "private function fileExcerpt(string \$file, int \$line, int \$srcContext = 3) : string
    { return '';", $contents);
        },

        // Fix prod container cache path
        static function (string $filePath, string $prefix, string $contents): string {
            if ('vendor/symfony/http-kernel/Kernel.php' !== $filePath) {
                return $contents;
            }

            return str_replace("\$buildDir . '/' . \$class . '.php'", "\$buildDir.'/'.str_replace('".$prefix."_', '', \$class).'.php'", $contents);
        },

        // Fix routes controllers with method argument
        static function (string $filePath, string $prefix, string $contents): string {
            if (!str_starts_with($filePath, 'api/Resources/cache/')) {
                return $contents;
            }

            return str_replace("'Contao\\\\ManagerApi\\\\", "'$prefix\\\\Contao\\\\ManagerApi\\\\", $contents);
        },

        // Fix route redirects if API is called with a slash
        static function (string $filePath, string $prefix, string $contents): string {
            if ('vendor/symfony/framework-bundle/Routing/RedirectableCompiledUrlMatcher.php' !== $filePath) {
                return $contents;
            }

            return str_replace("'Symfony\\\\Bundle\\\\FrameworkBundle\\\\Controller\\\\RedirectController::", "'$prefix\\\\Symfony\\\\Bundle\\\\FrameworkBundle\\\\Controller\\\\RedirectController::", $contents);
        },
    ],
];
