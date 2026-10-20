<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Communication\Loader;

use Codeception\Test\Unit;
use Spryker\Shared\Configuration\ConfigurationSchemaConstants;
use Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface;
use Spryker\Zed\Configuration\Communication\Loader\ConfigurationSchemaMetadataExtractor;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Configuration
 * @group Communication
 * @group Loader
 * @group ConfigurationSchemaMetadataExtractorTest
 * Add your own group annotations below this line
 */
class ConfigurationSchemaMetadataExtractorTest extends Unit
{
    protected const string FEATURE_KEY = 'test_feature';

    protected const string TAB_KEY = 'test_tab';

    protected const string COMPACT_GROUP_KEY = 'compact_group';

    protected const string PLAIN_GROUP_KEY = 'plain_group';

    protected const string LAYOUT_COMPACT = 'compact';

    protected const string SECTION_KEY = 'test_section';

    protected const string SECTION_NAME = 'Test Section';

    protected const string SECTION_DESCRIPTION = 'Test section description.';

    protected const string UNKNOWN_SECTION_KEY = 'unknown_section';

    public function testExtractGroupMetadataPassesThroughToggleAllAndLayout(): void
    {
        // Arrange
        $configurationSchemaMetadataExtractor = $this->createExtractor([
            ConfigurationSchemaConstants::SCHEMA_KEY_KEY => static::COMPACT_GROUP_KEY,
            ConfigurationSchemaConstants::SCHEMA_KEY_TOGGLE_ALL => true,
            ConfigurationSchemaConstants::SCHEMA_KEY_LAYOUT => static::LAYOUT_COMPACT,
        ]);

        // Act
        $groupMetadata = $configurationSchemaMetadataExtractor->extractGroupMetadata(static::FEATURE_KEY, static::TAB_KEY);

        // Assert
        $this->assertTrue($groupMetadata[static::COMPACT_GROUP_KEY][ConfigurationSchemaConstants::SCHEMA_KEY_TOGGLE_ALL]);
        $this->assertSame(static::LAYOUT_COMPACT, $groupMetadata[static::COMPACT_GROUP_KEY][ConfigurationSchemaConstants::SCHEMA_KEY_LAYOUT]);
    }

    public function testExtractGroupMetadataDefaultsToNoToggleAllAndDefaultLayoutWhenTheSchemaOmitsThem(): void
    {
        // Arrange
        $configurationSchemaMetadataExtractor = $this->createExtractor([
            ConfigurationSchemaConstants::SCHEMA_KEY_KEY => static::PLAIN_GROUP_KEY,
        ]);

        // Act
        $groupMetadata = $configurationSchemaMetadataExtractor->extractGroupMetadata(static::FEATURE_KEY, static::TAB_KEY);

        // Assert
        $this->assertFalse($groupMetadata[static::PLAIN_GROUP_KEY][ConfigurationSchemaConstants::SCHEMA_KEY_TOGGLE_ALL]);
        $this->assertNull($groupMetadata[static::PLAIN_GROUP_KEY][ConfigurationSchemaConstants::SCHEMA_KEY_LAYOUT]);
    }

    public function testExtractGroupMetadataResolvesTheSectionTheGroupReferences(): void
    {
        // Arrange
        $configurationSchemaMetadataExtractor = $this->createExtractor(
            [
                ConfigurationSchemaConstants::SCHEMA_KEY_KEY => static::COMPACT_GROUP_KEY,
                ConfigurationSchemaConstants::SCHEMA_KEY_SECTION => static::SECTION_KEY,
            ],
            [$this->createTestSection()],
        );

        // Act
        $groupMetadata = $configurationSchemaMetadataExtractor->extractGroupMetadata(static::FEATURE_KEY, static::TAB_KEY);

        // Assert
        $this->assertSame(
            [
                ConfigurationSchemaConstants::SCHEMA_KEY_KEY => static::SECTION_KEY,
                ConfigurationSchemaConstants::SCHEMA_KEY_NAME => static::SECTION_NAME,
                ConfigurationSchemaConstants::SCHEMA_KEY_DESCRIPTION => static::SECTION_DESCRIPTION,
            ],
            $groupMetadata[static::COMPACT_GROUP_KEY][ConfigurationSchemaConstants::SCHEMA_KEY_SECTION],
        );
    }

    public function testExtractGroupMetadataLeavesTheSectionEmptyWhenTheGroupHasNone(): void
    {
        // Arrange
        $configurationSchemaMetadataExtractor = $this->createExtractor(
            [
                ConfigurationSchemaConstants::SCHEMA_KEY_KEY => static::PLAIN_GROUP_KEY,
            ],
            [$this->createTestSection()],
        );

        // Act
        $groupMetadata = $configurationSchemaMetadataExtractor->extractGroupMetadata(static::FEATURE_KEY, static::TAB_KEY);

        // Assert
        $this->assertNull($groupMetadata[static::PLAIN_GROUP_KEY][ConfigurationSchemaConstants::SCHEMA_KEY_SECTION]);
    }

    public function testExtractGroupMetadataLeavesTheSectionEmptyWhenTheTabDoesNotDeclareIt(): void
    {
        // Arrange
        $configurationSchemaMetadataExtractor = $this->createExtractor(
            [
                ConfigurationSchemaConstants::SCHEMA_KEY_KEY => static::COMPACT_GROUP_KEY,
                ConfigurationSchemaConstants::SCHEMA_KEY_SECTION => static::UNKNOWN_SECTION_KEY,
            ],
            [$this->createTestSection()],
        );

        // Act
        $groupMetadata = $configurationSchemaMetadataExtractor->extractGroupMetadata(static::FEATURE_KEY, static::TAB_KEY);

        // Assert
        $this->assertNull($groupMetadata[static::COMPACT_GROUP_KEY][ConfigurationSchemaConstants::SCHEMA_KEY_SECTION]);
    }

    /**
     * @return array<string, string>
     */
    protected function createTestSection(): array
    {
        return [
            ConfigurationSchemaConstants::SCHEMA_KEY_KEY => static::SECTION_KEY,
            ConfigurationSchemaConstants::SCHEMA_KEY_NAME => static::SECTION_NAME,
            ConfigurationSchemaConstants::SCHEMA_KEY_DESCRIPTION => static::SECTION_DESCRIPTION,
        ];
    }

    /**
     * @param array<string, mixed> $group
     * @param array<int, array<string, string>> $sections
     */
    protected function createExtractor(array $group, array $sections = []): ConfigurationSchemaMetadataExtractor
    {
        $configurationFacadeMock = $this->createMock(ConfigurationFacadeInterface::class);
        $configurationFacadeMock->method('getMergedConfigurationSchema')->willReturn([
            ConfigurationSchemaConstants::SCHEMA_KEY_FEATURES => [
                [
                    ConfigurationSchemaConstants::SCHEMA_KEY_KEY => static::FEATURE_KEY,
                    ConfigurationSchemaConstants::SCHEMA_KEY_TABS => [
                        [
                            ConfigurationSchemaConstants::SCHEMA_KEY_KEY => static::TAB_KEY,
                            ConfigurationSchemaConstants::SCHEMA_KEY_SECTIONS => $sections,
                            ConfigurationSchemaConstants::SCHEMA_KEY_GROUPS => [$group],
                        ],
                    ],
                ],
            ],
        ]);

        return new ConfigurationSchemaMetadataExtractor($configurationFacadeMock);
    }
}
