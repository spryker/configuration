<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Search;

use ReflectionClass;
use ReflectionException;
use Spryker\Zed\Configuration\ConfigurationConfig;

class ConfigurationUsageScanner implements ConfigurationUsageScannerInterface
{
    protected const array SCAN_LAYERS = ['Yves', 'Zed', 'Glue', 'Client'];

    public function __construct(
        protected ConfigurationConfig $config,
        protected ConfigurationOverrideCollectorInterface $overrideCollector,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function scan(): array
    {
        /** @var array<string, array<int, array{coreClass: string, coreMethod: string, projectClass: string, projectMethod: string}>> $overrides */
        $overrides = [];
        $configFiles = $this->findConfigFiles();

        foreach ($configFiles as $filePath) {
            $className = $this->extractClassName($filePath);

            if ($className === null || !class_exists($className)) {
                continue;
            }

            $layer = $this->detectLayer($className);

            if ($layer === null) {
                continue;
            }

            $methodsWithKeys = $this->extractMethodsWithConfigKeys($filePath, $className);

            if ($methodsWithKeys === []) {
                continue;
            }

            $this->overrideCollector->collectOverrides($overrides, $className, $layer, $methodsWithKeys);
        }

        return $overrides;
    }

    /**
     * @return array<string>
     */
    protected function findConfigFiles(): array
    {
        $files = [];
        $rootDir = APPLICATION_ROOT_DIR . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;

        foreach ($this->config->getCoreNamespaces() as $namespace) {
            foreach (static::SCAN_LAYERS as $layer) {
                $pattern = sprintf(
                    '%s%s/*/src/%s/%s/*/*Config.php',
                    $rootDir,
                    $namespace,
                    $namespace,
                    $layer,
                );

                $found = glob($pattern);

                if ($found !== false) {
                    $files = array_merge($files, $found);
                }
            }
        }

        return $files;
    }

    protected function extractClassName(string $filePath): ?string
    {
        $content = file_get_contents($filePath);

        if ($content === false) {
            return null;
        }

        $tokens = token_get_all($content);
        $namespace = '';
        $className = null;

        for ($i = 0, $count = count($tokens); $i < $count; $i++) {
            if (!is_array($tokens[$i])) {
                continue;
            }

            if ($tokens[$i][0] === T_NAMESPACE) {
                $namespace = $this->extractNamespace($tokens, $i);
            }

            if ($tokens[$i][0] === T_CLASS) {
                $className = $this->extractTokenValue($tokens, $i);

                break;
            }
        }

        if ($className === null) {
            return null;
        }

        return $namespace !== '' ? sprintf('%s\\%s', $namespace, $className) : $className;
    }

    /**
     * @param array<mixed> $tokens
     */
    protected function extractNamespace(array $tokens, int $startIndex): string
    {
        $namespace = '';

        for ($i = $startIndex + 1, $count = count($tokens); $i < $count; $i++) {
            if (!is_array($tokens[$i])) {
                if ($tokens[$i] === ';') {
                    break;
                }

                continue;
            }

            if ($tokens[$i][0] === T_NAME_QUALIFIED || $tokens[$i][0] === T_STRING) {
                $namespace .= $tokens[$i][1];
            }
        }

        return $namespace;
    }

    /**
     * @param array<mixed> $tokens
     */
    protected function extractTokenValue(array $tokens, int $startIndex): ?string
    {
        for ($i = $startIndex + 1, $count = count($tokens); $i < $count; $i++) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
                return $tokens[$i][1];
            }
        }

        return null;
    }

    protected function detectLayer(string $className): ?string
    {
        foreach (static::SCAN_LAYERS as $layer) {
            if (str_contains($className, sprintf('\\%s\\', $layer))) {
                return $layer;
            }
        }

        return null;
    }

    /**
     * Parses the file source and returns method names that contain getModuleConfig() calls,
     * mapped to the configuration keys extracted from those calls.
     *
     * @return array<string, array<string>>
     */
    protected function extractMethodsWithConfigKeys(string $filePath, string $className): array
    {
        $source = file_get_contents($filePath);

        if ($source === false) {
            return [];
        }

        $tokens = token_get_all($source);
        $methods = [];
        $currentMethod = null;
        $braceDepth = 0;
        $methodBraceStart = 0;

        for ($i = 0, $count = count($tokens); $i < $count; $i++) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_FUNCTION) {
                $methodName = $this->extractTokenValue($tokens, $i);

                if ($methodName !== null) {
                    $currentMethod = $methodName;
                    $methodBraceStart = $braceDepth;
                }
            }

            if (!is_array($tokens[$i])) {
                if ($tokens[$i] === '{') {
                    $braceDepth++;
                }

                if ($tokens[$i] === '}') {
                    $braceDepth--;

                    if ($currentMethod !== null && $braceDepth <= $methodBraceStart) {
                        $currentMethod = null;
                    }
                }
            }

            if ($currentMethod === null) {
                continue;
            }

            if (
                is_array($tokens[$i])
                && $tokens[$i][0] === T_VARIABLE
                && $tokens[$i][1] === '$this'
                && $this->isGetModuleConfigCall($tokens, $i)
            ) {
                $configKey = $this->extractConfigKey($tokens, $i, $className);

                if ($configKey !== null) {
                    $methods[$currentMethod][] = $configKey;
                }
            }
        }

        return $methods;
    }

    /**
     * @param array<mixed> $tokens
     */
    protected function isGetModuleConfigCall(array $tokens, int $thisIndex): bool
    {
        $count = count($tokens);

        for ($i = $thisIndex + 1; $i < $count && $i <= $thisIndex + 4; $i++) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_OBJECT_OPERATOR) {
                continue;
            }

            if (is_array($tokens[$i]) && $tokens[$i][0] === T_STRING && $tokens[$i][1] === 'getModuleConfig') {
                return true;
            }

            if (is_array($tokens[$i]) && $tokens[$i][0] === T_WHITESPACE) {
                continue;
            }

            break;
        }

        return false;
    }

    /**
     * Extracts the first argument of getModuleConfig() — either an inline string or static::/self:: constant.
     *
     * @param array<mixed> $tokens
     */
    protected function extractConfigKey(array $tokens, int $thisIndex, string $className): ?string
    {
        $count = count($tokens);

        $parenIndex = null;

        for ($i = $thisIndex + 1; $i < $count && $i <= $thisIndex + 10; $i++) {
            if (!is_array($tokens[$i]) && $tokens[$i] === '(') {
                $parenIndex = $i;

                break;
            }
        }

        if ($parenIndex === null) {
            return null;
        }

        for ($i = $parenIndex + 1; $i < $count; $i++) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_WHITESPACE) {
                continue;
            }

            if (is_array($tokens[$i]) && $tokens[$i][0] === T_CONSTANT_ENCAPSED_STRING) {
                return trim($tokens[$i][1], "'\"");
            }

            if (is_array($tokens[$i]) && ($tokens[$i][1] === 'static' || $tokens[$i][1] === 'self')) {
                return $this->resolveClassConstant($tokens, $i, $className);
            }

            break;
        }

        return null;
    }

    /**
     * @param array<mixed> $tokens
     */
    protected function resolveClassConstant(array $tokens, int $staticIndex, string $className): ?string
    {
        $count = count($tokens);
        $constantName = null;

        for ($i = $staticIndex + 1; $i < $count && $i <= $staticIndex + 4; $i++) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
                $constantName = $tokens[$i][1];

                break;
            }
        }

        if ($constantName === null) {
            return null;
        }

        try {
            $reflectionClass = new ReflectionClass($className); /** @phpstan-ignore-line */
            $value = $reflectionClass->getConstant($constantName);

            return is_string($value) ? $value : null;
        } catch (ReflectionException) {
            return null;
        }
    }
}
