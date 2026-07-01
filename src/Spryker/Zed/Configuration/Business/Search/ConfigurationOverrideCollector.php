<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Search;

use ReflectionException;
use ReflectionMethod;
use Spryker\Shared\Configuration\ConfigurationSchemaConstants;
use Throwable;

class ConfigurationOverrideCollector implements ConfigurationOverrideCollectorInterface
{
    protected const string GET_MODULE_CONFIG_PATTERN = '$this->getModuleConfig(';

    /**
     * Resolver class names as strings to avoid cross-namespace import violations (Zed cannot import Yves/Glue).
     */
    protected const array LAYER_RESOLVER_MAP = [
        'Yves' => 'Spryker\\Yves\\Kernel\\ClassResolver\\Config\\BundleConfigResolver',
        'Zed' => 'Spryker\\Zed\\Kernel\\ClassResolver\\Config\\BundleConfigResolver',
        'Glue' => 'Spryker\\Glue\\Kernel\\ClassResolver\\Config\\BundleConfigResolver',
        'Client' => 'Spryker\\Client\\Kernel\\ClassResolver\\Config\\BundleConfigResolver',
    ];

    /**
     * {@inheritDoc}
     *
     * @param array<string, array<int, array{coreClass: string, coreMethod: string, projectClass: string, projectMethod: string}>> $overrides
     * @param array<string, array<string>> $methodsWithKeys
     */
    public function collectOverrides(array &$overrides, string $coreClassName, string $layer, array $methodsWithKeys): void
    {
        $projectClassName = $this->resolveProjectClass($coreClassName, $layer);

        if ($projectClassName === null || $projectClassName === $coreClassName) {
            return;
        }

        foreach ($methodsWithKeys as $methodName => $configKeys) {
            if (!$this->isMethodOverriddenWithoutGetModuleConfig($projectClassName, $methodName)) {
                continue;
            }

            foreach ($configKeys as $configKey) {
                $overrides[$configKey][] = [
                    ConfigurationSchemaConstants::OVERRIDE_KEY_CORE_CLASS => $coreClassName,
                    ConfigurationSchemaConstants::OVERRIDE_KEY_CORE_METHOD => $methodName,
                    ConfigurationSchemaConstants::OVERRIDE_KEY_PROJECT_CLASS => $projectClassName,
                    ConfigurationSchemaConstants::OVERRIDE_KEY_PROJECT_METHOD => $methodName,
                ];
            }
        }
    }

    protected function resolveProjectClass(string $coreClassName, string $layer): ?string
    {
        $resolverClass = static::LAYER_RESOLVER_MAP[$layer] ?? null;

        if ($resolverClass === null) {
            return null;
        }

        try {
            $resolver = new $resolverClass();

            if (!method_exists($resolver, 'resolve')) {
                return null;
            }

            $resolved = $resolver->resolve($coreClassName);

            return $resolved::class;
        } catch (Throwable) {
            // Resolver throws if no override exists — this is expected
            return null;
        }
    }

    protected function isMethodOverriddenWithoutGetModuleConfig(string $projectClassName, string $methodName): bool
    {
        try {
            $reflectionMethod = new ReflectionMethod($projectClassName, $methodName);

            // Check the method is actually declared in the project class, not inherited
            if ($reflectionMethod->getDeclaringClass()->getName() !== $projectClassName) {
                return false;
            }

            $source = $this->getMethodSource($reflectionMethod);

            return !str_contains($source, static::GET_MODULE_CONFIG_PATTERN);
        } catch (ReflectionException) {
            return false;
        }
    }

    protected function getMethodSource(ReflectionMethod $method): string
    {
        $fileName = $method->getFileName();

        if ($fileName === false) {
            return '';
        }

        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        if ($startLine === false || $endLine === false) {
            return '';
        }

        $lines = file($fileName);

        if ($lines === false) {
            return '';
        }

        return implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));
    }
}
