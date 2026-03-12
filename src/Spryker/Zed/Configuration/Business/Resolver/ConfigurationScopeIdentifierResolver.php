<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Resolver;

class ConfigurationScopeIdentifierResolver implements ConfigurationScopeIdentifierResolverInterface
{
    /**
     * @param array<\Spryker\Zed\ConfigurationExtension\Dependency\Plugin\ConfigurationScopeIdentifierProviderPluginInterface> $scopeIdentifierProviderPlugins
     */
    public function __construct(protected array $scopeIdentifierProviderPlugins)
    {
    }

    /**
     * @param string $scope
     *
     * @return array<string>
     */
    public function getIdentifiersForScope(string $scope): array
    {
        foreach ($this->scopeIdentifierProviderPlugins as $plugin) {
            if ($plugin->getScopeKey() !== $scope) {
                continue;
            }

            return $plugin->getIdentifiers();
        }

        return [];
    }
}
