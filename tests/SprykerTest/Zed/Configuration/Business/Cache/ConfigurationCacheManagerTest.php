<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Business\Cache;

use Codeception\Test\Unit;
use ReflectionProperty;
use Spryker\Zed\Configuration\Business\Cache\ConfigurationCacheManager;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Configuration
 * @group Business
 * @group Cache
 * @group ConfigurationCacheManagerTest
 * Add your own group annotations below this line
 */
class ConfigurationCacheManagerTest extends Unit
{
    /**
     * @var string
     */
    protected const SETTING_KEY = 'catalog:general:display:items_per_page';

    /**
     * @var string
     */
    protected const SCOPE_GLOBAL = 'global';

    /**
     * @var string
     */
    protected const SCOPE_STORE = 'store';

    /**
     * @var string
     */
    protected const SCOPE_IDENTIFIER_DE = 'DE';

    protected function _before(): void
    {
        (new ConfigurationCacheManager())->clearAll();
    }

    public function testWarmUpScopeMarksScopeWarmAndExposesItsValues(): void
    {
        // Arrange
        $configurationCacheManager = new ConfigurationCacheManager();

        // Act
        $configurationCacheManager->warmUpScope(static::SCOPE_GLOBAL, null, [static::SETTING_KEY => '24']);

        // Assert
        $this->assertTrue($configurationCacheManager->isScopeWarm(static::SCOPE_GLOBAL));
        $this->assertSame('24', $configurationCacheManager->get(static::SETTING_KEY, static::SCOPE_GLOBAL));
    }

    public function testIsScopeWarmReturnsFalseForScopeThatWasNeverWarmedUp(): void
    {
        // Arrange
        $configurationCacheManager = new ConfigurationCacheManager();
        $configurationCacheManager->warmUpScope(static::SCOPE_GLOBAL, null, [static::SETTING_KEY => '24']);

        // Act
        $isScopeWarm = $configurationCacheManager->isScopeWarm(static::SCOPE_STORE, static::SCOPE_IDENTIFIER_DE);

        // Assert
        $this->assertFalse($isScopeWarm);
    }

    public function testInvalidateDropsScopeWarmMarkerSoThatValuesCreatedLaterBecomeVisible(): void
    {
        // Arrange
        $configurationCacheManager = new ConfigurationCacheManager();
        $configurationCacheManager->warmUpScope(static::SCOPE_GLOBAL, null, []);

        // Act
        $configurationCacheManager->invalidate(static::SETTING_KEY, static::SCOPE_GLOBAL);

        // Assert
        $this->assertFalse($configurationCacheManager->isScopeWarm(static::SCOPE_GLOBAL));
    }

    public function testInvalidateRemovesTheCachedValueOfTheGivenKeyOnly(): void
    {
        // Arrange
        $configurationCacheManager = new ConfigurationCacheManager();
        $configurationCacheManager->warmUpScope(static::SCOPE_GLOBAL, null, [
            static::SETTING_KEY => '24',
            'catalog:general:display:sort_order' => 'price_asc',
        ]);

        // Act
        $configurationCacheManager->invalidate(static::SETTING_KEY, static::SCOPE_GLOBAL);

        // Assert
        $this->assertNull($configurationCacheManager->get(static::SETTING_KEY, static::SCOPE_GLOBAL));
        $this->assertSame('price_asc', $configurationCacheManager->get('catalog:general:display:sort_order', static::SCOPE_GLOBAL));
    }

    public function testScopeIdentifiersAreCachedIndependentlyOfEachOther(): void
    {
        // Arrange
        $configurationCacheManager = new ConfigurationCacheManager();

        // Act
        $configurationCacheManager->warmUpScope(static::SCOPE_STORE, static::SCOPE_IDENTIFIER_DE, [static::SETTING_KEY => '50']);
        $configurationCacheManager->warmUpScope(static::SCOPE_STORE, 'AT', [static::SETTING_KEY => '75']);

        // Assert
        $this->assertSame('50', $configurationCacheManager->get(static::SETTING_KEY, static::SCOPE_STORE, static::SCOPE_IDENTIFIER_DE));
        $this->assertSame('75', $configurationCacheManager->get(static::SETTING_KEY, static::SCOPE_STORE, 'AT'));
    }

    public function testCacheIsSharedBetweenInstancesBecauseTheFactoryHandsOutANewOnePerFacadeCall(): void
    {
        // Arrange
        (new ConfigurationCacheManager())->warmUpScope(static::SCOPE_GLOBAL, null, [static::SETTING_KEY => '24']);

        // Act
        $configurationCacheManager = new ConfigurationCacheManager();

        // Assert
        $this->assertTrue($configurationCacheManager->isScopeWarm(static::SCOPE_GLOBAL));
        $this->assertSame('24', $configurationCacheManager->get(static::SETTING_KEY, static::SCOPE_GLOBAL));
    }

    public function testClearAllRemovesCachedValuesAndScopeWarmMarkers(): void
    {
        // Arrange
        $configurationCacheManager = new ConfigurationCacheManager();
        $configurationCacheManager->warmUpScope(static::SCOPE_GLOBAL, null, [static::SETTING_KEY => '24']);

        // Act
        $configurationCacheManager->clearAll();

        // Assert
        $this->assertFalse($configurationCacheManager->isScopeWarm(static::SCOPE_GLOBAL));
        $this->assertNull($configurationCacheManager->get(static::SETTING_KEY, static::SCOPE_GLOBAL));
        $this->assertSame([], (new ReflectionProperty(ConfigurationCacheManager::class, 'runtimeCache'))->getValue());
    }
}
