<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Business;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ConfigurationValueTransfer;
use Generated\Shared\Transfer\HtmlSanitizerConfigTransfer;
use Spryker\Service\UtilSanitizeXss\UtilSanitizeXssServiceInterface;
use Spryker\Zed\Configuration\Business\Sanitizer\ConfigurationValueSanitizer;
use Spryker\Zed\Configuration\Business\Schema\ConfigurationSchemaProviderInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Configuration
 * @group Business
 * @group ConfigurationValueSanitizerTest
 * Add your own group annotations below this line
 */
class ConfigurationValueSanitizerTest extends Unit
{
    public function testIsSanitizeXssEnabledReturnsTrueWhenSettingHasSanitizeXssOption(): void
    {
        // Arrange
        $schemaProvider = $this->createSchemaProviderMock([
            'test:setting' => ['sanitize_xss' => ['allow_elements' => ['strong' => []]]],
        ]);
        $sanitizer = new ConfigurationValueSanitizer($schemaProvider, $this->createMock(UtilSanitizeXssServiceInterface::class));

        // Act & Assert
        $this->assertTrue($sanitizer->isSanitizeXssEnabled('test:setting'));
    }

    public function testIsSanitizeXssEnabledReturnsFalseWhenSettingDoesNotHaveSanitizeXssOption(): void
    {
        // Arrange
        $schemaProvider = $this->createSchemaProviderMock([
            'test:setting' => ['type' => 'string', 'constraints' => []],
        ]);
        $sanitizer = new ConfigurationValueSanitizer($schemaProvider, $this->createMock(UtilSanitizeXssServiceInterface::class));

        // Act & Assert
        $this->assertFalse($sanitizer->isSanitizeXssEnabled('test:setting'));
    }

    public function testIsSanitizeXssEnabledReturnsFalseForUnknownSettingKey(): void
    {
        // Arrange
        $schemaProvider = $this->createSchemaProviderMock([]);
        $sanitizer = new ConfigurationValueSanitizer($schemaProvider, $this->createMock(UtilSanitizeXssServiceInterface::class));

        // Act & Assert
        $this->assertFalse($sanitizer->isSanitizeXssEnabled('unknown:key'));
    }

    public function testSanitizeCallsXssServiceWithConfiguredElementsAndAttributes(): void
    {
        // Arrange
        $schemaProvider = $this->createSchemaProviderMock([
            'test:setting' => [
                'sanitize_xss' => [
                    'allow_elements' => [
                        'a' => ['href', 'class'],
                        'strong' => [],
                    ],
                    'allow_attributes' => [
                        'data-custom' => '*',
                    ],
                ],
            ],
        ]);

        $xssServiceMock = $this->createMock(UtilSanitizeXssServiceInterface::class);
        $xssServiceMock->expects($this->once())
            ->method('sanitize')
            ->with('<a>text</a>', $this->isInstanceOf(HtmlSanitizerConfigTransfer::class))
            ->willReturn('sanitized-value');

        $sanitizer = new ConfigurationValueSanitizer($schemaProvider, $xssServiceMock);

        $transfer = (new ConfigurationValueTransfer())
            ->setSettingKey('test:setting')
            ->setValue('<a>text</a>');

        // Act
        $sanitizer->sanitize($transfer);

        // Assert
        $this->assertSame('sanitized-value', $transfer->getValue());
    }

    public function testSanitizePassesEmptyConfigTransferWhenChildOptionsAreAbsent(): void
    {
        // Arrange
        $schemaProvider = $this->createSchemaProviderMock([
            'test:setting' => ['sanitize_xss' => []],
        ]);

        $xssServiceMock = $this->createMock(UtilSanitizeXssServiceInterface::class);
        $xssServiceMock->expects($this->once())
            ->method('sanitize')
            ->with('some value', $this->isInstanceOf(HtmlSanitizerConfigTransfer::class))
            ->willReturn('stripped');

        $sanitizer = new ConfigurationValueSanitizer($schemaProvider, $xssServiceMock);

        $transfer = (new ConfigurationValueTransfer())
            ->setSettingKey('test:setting')
            ->setValue('some value');

        // Act
        $sanitizer->sanitize($transfer);

        // Assert
        $this->assertSame('stripped', $transfer->getValue());
    }

    public function testSanitizeDoesNotCallXssServiceWhenValueIsEmpty(): void
    {
        // Arrange
        $schemaProvider = $this->createSchemaProviderMock([
            'test:setting' => ['sanitize_xss' => []],
        ]);

        $xssServiceMock = $this->createMock(UtilSanitizeXssServiceInterface::class);
        $xssServiceMock->expects($this->never())->method('sanitize');

        $sanitizer = new ConfigurationValueSanitizer($schemaProvider, $xssServiceMock);

        $transfer = (new ConfigurationValueTransfer())
            ->setSettingKey('test:setting')
            ->setValue('');

        // Act
        $sanitizer->sanitize($transfer);

        // Assert
        $this->assertSame('', $transfer->getValue());
    }

    public function testSanitizeDoesNotCallXssServiceWhenValueIsNull(): void
    {
        // Arrange
        $schemaProvider = $this->createSchemaProviderMock([
            'test:setting' => ['sanitize_xss' => []],
        ]);

        $xssServiceMock = $this->createMock(UtilSanitizeXssServiceInterface::class);
        $xssServiceMock->expects($this->never())->method('sanitize');

        $sanitizer = new ConfigurationValueSanitizer($schemaProvider, $xssServiceMock);

        $transfer = (new ConfigurationValueTransfer())->setSettingKey('test:setting');

        // Act
        $sanitizer->sanitize($transfer);

        // Assert
        $this->assertNull($transfer->getValue());
    }

    /**
     * @param array<string, array<string, mixed>> $settingsMap
     */
    protected function createSchemaProviderMock(array $settingsMap): ConfigurationSchemaProviderInterface
    {
        $mock = $this->createMock(ConfigurationSchemaProviderInterface::class);
        $mock->method('getSettingsMap')->willReturn($settingsMap);

        return $mock;
    }
}
