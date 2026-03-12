<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\Configuration\Reader;

use Generated\Shared\Transfer\ConfigurationSettingTransfer;
use Generated\Shared\Transfer\ConfigurationValueRequestTransfer;
use Spryker\Shared\Configuration\ConfigurationConfig;
use Spryker\Shared\Configuration\ConfigurationConstants;
use Spryker\Shared\Configuration\Encryptor\ConfigurationValueEncryptorInterface;
use Spryker\Shared\Configuration\Schema\ConfigurationSchemaReaderInterface;

abstract class AbstractConfigurationValueResolver implements ConfigurationValueResolverInterface
{
    /**
     * @var array<string, array<string, mixed>>|null
     */
    protected static ?array $settingCache = null;

    /**
     * @var array<string, mixed>
     */
    protected static array $resolvedValueCache = [];

    public function __construct(
        protected ConfigurationConfig $sharedConfig,
        protected ConfigurationSchemaReaderInterface $schemaReader,
        protected ConfigurationValueEncryptorInterface $encryptor,
    ) {
    }

    public function getConfigurationValue(ConfigurationValueRequestTransfer $configurationValueRequestTransfer): mixed
    {
        $key = $configurationValueRequestTransfer->getKeyOrFail();
        $scopeContextTransfers = (array)$configurationValueRequestTransfer->getScopes();
        $scopeContextMapping = $this->mapScopeContextTransfers($scopeContextTransfers);
        $scopeHashedKey = $key . '|' . implode('|', $scopeContextMapping);

        if (isset(static::$resolvedValueCache[$scopeHashedKey])) {
            return static::$resolvedValueCache[$scopeHashedKey];
        }

        $setting = $this->findConfigurationSetting($key);

        if (!$setting) {
            static::$resolvedValueCache[$scopeHashedKey] = null;

            return static::$resolvedValueCache[$scopeHashedKey];
        }

        $highestScope = $this->findHighestScope($scopeContextMapping);

        if (!$scopeContextTransfers) {
            $value = $this->fetchRawValue($key, $highestScope) ?? $setting->getDefaultValue();
            static::$resolvedValueCache[$scopeHashedKey] = $this->formatValue($value, $setting);

            return static::$resolvedValueCache[$scopeHashedKey];
        }

        $value = $this->findValueWithHierarchy($key, $scopeContextMapping, $highestScope) ?? $setting->getDefaultValue();
        static::$resolvedValueCache[$scopeHashedKey] = $this->formatValue($value, $setting);

        return static::$resolvedValueCache[$scopeHashedKey];
    }

    /**
     * Fetches the raw stored value for the given key, scope, and optional scope identifier.
     * Returns null when no value exists at this scope level.
     *
     * @param string $key
     * @param string $scope
     * @param string|null $scopeIdentifier
     *
     * @return string|null
     */
    abstract protected function fetchRawValue(string $key, string $scope, ?string $scopeIdentifier = null): ?string;

    protected function findConfigurationSetting(string $key): ?ConfigurationSettingTransfer
    {
        if (static::$settingCache === null) {
            static::$settingCache = $this->schemaReader->getSettingsMap();
        }

        if (!isset(static::$settingCache[$key])) {
            return null;
        }

        $entry = static::$settingCache[$key];

        return (new ConfigurationSettingTransfer())
            ->setKey($key)
            ->setType($entry[ConfigurationConstants::SCHEMA_KEY_TYPE])
            ->setDefaultValue($entry[ConfigurationConstants::SCHEMA_KEY_DEFAULT_VALUE] ?? null)
            ->setIsSecret($entry[ConfigurationConstants::SCHEMA_KEY_SECRET] ?? false)
            ->setIsStorefront($entry[ConfigurationConstants::SCHEMA_KEY_STOREFRONT] ?? false)
            ->setScopes($entry[ConfigurationConstants::SCHEMA_KEY_SCOPES] ?? [])
            ->setConstraints($entry[ConfigurationConstants::SCHEMA_KEY_CONSTRAINTS] ?? []);
    }

    /**
     * @param string $key
     * @param array<string, string|null> $scopeContextMapping
     * @param string $scope
     *
     * @return string|null
     */
    protected function findValueWithHierarchy(string $key, array $scopeContextMapping, string $scope): ?string
    {
        $scopeIdentifier = $scopeContextMapping[$scope] ?? null;
        $value = $this->fetchRawValue($key, $scope, $scopeIdentifier);

        if ($value !== null) {
            return $value;
        }

        $parentScope = $this->getParentScope($scope);

        if ($parentScope === null) {
            return null;
        }

        return $this->findValueWithHierarchy($key, $scopeContextMapping, $parentScope);
    }

    protected function getParentScope(string $scope): ?string
    {
        return $this->sharedConfig->getScopeHierarchy()[$scope] ?? null;
    }

    /**
     * @param array<string, string|null> $scopeContext
     *
     * @return string
     */
    protected function findHighestScope(array $scopeContext): string
    {
        $hierarchyKeys = $this->getHierarchyKeys($this->sharedConfig->getScopeHierarchy());

        foreach ($hierarchyKeys as $scope) {
            if ($scopeContext && isset($scopeContext[$scope])) {
                return $scope;
            }
        }

        return ConfigurationConstants::SCOPE_GLOBAL;
    }

    /**
     * @param array<string, string|null> $hierarchy
     *
     * @return array<string>
     */
    protected function getHierarchyKeys(array $hierarchy): array
    {
        $result = [];
        $current = null;

        while (($key = array_search($current, $hierarchy, true)) !== false) {
            array_unshift($result, $key);
            $current = $key;
        }

        return $result;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ConfigurationScopeTransfer> $scopeContextTransfers
     *
     * @return array<string, string|null>
     */
    protected function mapScopeContextTransfers(array $scopeContextTransfers): array
    {
        $mapping = [];

        foreach ($scopeContextTransfers as $scopeContextTransfer) {
            $mapping[$scopeContextTransfer->getKey()] = $scopeContextTransfer->getIdentifier();
        }

        return $mapping;
    }

    protected function formatValue(mixed $value, ConfigurationSettingTransfer $settingTransfer): mixed
    {
        if ($settingTransfer->getType() === ConfigurationConstants::VALUE_TYPE_BOOLEAN && $value === ConfigurationConstants::BOOLEAN_STRING_FALSE) {
            return false;
        }

        return $value;
    }

    protected function decryptIfSecret(string $key, ?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $setting = $this->findConfigurationSetting($key);

        if ($setting === null || !$setting->getIsSecret()) {
            return $value;
        }

        return $this->encryptor->decrypt($value);
    }
}
