<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Sanitizer;

use Generated\Shared\Transfer\ConfigurationValueTransfer;
use Generated\Shared\Transfer\HtmlSanitizerAllowedAttributeTransfer;
use Generated\Shared\Transfer\HtmlSanitizerAllowedElementTransfer;
use Generated\Shared\Transfer\HtmlSanitizerConfigTransfer;
use Spryker\Service\UtilSanitizeXss\UtilSanitizeXssServiceInterface;
use Spryker\Shared\Configuration\ConfigurationConstants;
use Spryker\Zed\Configuration\Business\Schema\ConfigurationSchemaProviderInterface;

class ConfigurationValueSanitizer implements ConfigurationValueSanitizerInterface
{
    public function __construct(
        protected ConfigurationSchemaProviderInterface $schemaProvider,
        protected UtilSanitizeXssServiceInterface $utilSanitizeXssService,
    ) {
    }

    public function isSanitizeXssEnabled(string $settingKey): bool
    {
        $settingsMap = $this->schemaProvider->getSettingsMap();

        return isset($settingsMap[$settingKey][ConfigurationConstants::SCHEMA_KEY_SANITIZE_XSS]);
    }

    public function sanitize(ConfigurationValueTransfer $configurationValueTransfer): void
    {
        $value = $configurationValueTransfer->getValue();

        if ($value === null || $value === '') {
            return;
        }

        $settingKey = $configurationValueTransfer->getSettingKeyOrFail();
        $sanitizeXssConfig = $this->schemaProvider->getSettingsMap()[$settingKey][ConfigurationConstants::SCHEMA_KEY_SANITIZE_XSS];

        $htmlSanitizerConfigTransfer = $this->buildHtmlSanitizerConfigTransfer($sanitizeXssConfig);
        $sanitizedValue = $this->utilSanitizeXssService->sanitize($value, $htmlSanitizerConfigTransfer);

        $configurationValueTransfer->setValue($sanitizedValue);
    }

    /**
     * @param array<string, mixed> $sanitizeXssConfig
     */
    protected function buildHtmlSanitizerConfigTransfer(array $sanitizeXssConfig): HtmlSanitizerConfigTransfer
    {
        $htmlSanitizerConfigTransfer = new HtmlSanitizerConfigTransfer();

        if ($sanitizeXssConfig[ConfigurationConstants::SCHEMA_KEY_SANITIZE_XSS_ALLOW_SAFE_ELEMENTS] ?? false) {
            $htmlSanitizerConfigTransfer->setIsAllowSafeElements(true);
        }

        if ($sanitizeXssConfig[ConfigurationConstants::SCHEMA_KEY_SANITIZE_XSS_ALLOW_STATIC_ELEMENTS] ?? false) {
            $htmlSanitizerConfigTransfer->setIsAllowStaticElements(true);
        }

        $this->addAllowElements($htmlSanitizerConfigTransfer, $sanitizeXssConfig);
        $this->addAllowAttributes($htmlSanitizerConfigTransfer, $sanitizeXssConfig);

        if ($sanitizeXssConfig[ConfigurationConstants::SCHEMA_KEY_SANITIZE_XSS_FORCE_HTTPS_URLS] ?? false) {
            $htmlSanitizerConfigTransfer->setIsForceHttpsUrls(true);
        }

        if ($sanitizeXssConfig[ConfigurationConstants::SCHEMA_KEY_SANITIZE_XSS_ALLOW_RELATIVE_LINKS] ?? false) {
            $htmlSanitizerConfigTransfer->setIsAllowRelativeLinks(true);
        }

        if (!empty($sanitizeXssConfig[ConfigurationConstants::SCHEMA_KEY_SANITIZE_XSS_ALLOWED_LINK_SCHEMES])) {
            $htmlSanitizerConfigTransfer->setAllowedLinkSchemes($sanitizeXssConfig[ConfigurationConstants::SCHEMA_KEY_SANITIZE_XSS_ALLOWED_LINK_SCHEMES]);
        }

        if (!empty($sanitizeXssConfig[ConfigurationConstants::SCHEMA_KEY_SANITIZE_XSS_ALLOWED_LINK_HOSTS])) {
            $htmlSanitizerConfigTransfer->setAllowedLinkHosts($sanitizeXssConfig[ConfigurationConstants::SCHEMA_KEY_SANITIZE_XSS_ALLOWED_LINK_HOSTS]);
        }

        return $htmlSanitizerConfigTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\HtmlSanitizerConfigTransfer $htmlSanitizerConfigTransfer
     * @param array<string, mixed> $sanitizeXssConfig
     */
    protected function addAllowElements(HtmlSanitizerConfigTransfer $htmlSanitizerConfigTransfer, array $sanitizeXssConfig): void
    {
        foreach ($sanitizeXssConfig[ConfigurationConstants::SCHEMA_KEY_SANITIZE_XSS_ALLOW_ELEMENTS] ?? [] as $element => $attributes) {
            $allowedAttributes = $attributes === '*' ? ['*'] : (array)$attributes;

            $htmlSanitizerConfigTransfer->addAllowedElement(
                (new HtmlSanitizerAllowedElementTransfer())
                    ->setElement($element)
                    ->setAllowedAttributes($allowedAttributes),
            );
        }
    }

    /**
     * @param \Generated\Shared\Transfer\HtmlSanitizerConfigTransfer $htmlSanitizerConfigTransfer
     * @param array<string, mixed> $sanitizeXssConfig
     */
    protected function addAllowAttributes(HtmlSanitizerConfigTransfer $htmlSanitizerConfigTransfer, array $sanitizeXssConfig): void
    {
        foreach ($sanitizeXssConfig[ConfigurationConstants::SCHEMA_KEY_SANITIZE_XSS_ALLOW_ATTRIBUTES] ?? [] as $attribute => $elements) {
            $allowedElements = $elements === '*' ? ['*'] : (array)$elements;

            $htmlSanitizerConfigTransfer->addAllowedAttribute(
                (new HtmlSanitizerAllowedAttributeTransfer())
                    ->setAttribute($attribute)
                    ->setAllowedElements($allowedElements),
            );
        }
    }
}
