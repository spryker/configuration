<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\Configuration\Encryptor;

use Spryker\Service\UtilEncryption\UtilEncryptionServiceInterface;
use Spryker\Shared\Configuration\ConfigurationConfig;

/**
 * This is a wrapper class for UtilEncryptionService to make sure that reading and writing uses same IV and EncryptionKey from configuration.
 */
class ConfigurationValueEncryptor implements ConfigurationValueEncryptorInterface
{
    public function __construct(
        protected UtilEncryptionServiceInterface $utilEncryptionService,
        protected ConfigurationConfig $config,
    ) {
    }

    public function encrypt(string $plainText): string
    {
        return $this->utilEncryptionService->encryptOpenSsl(
            $plainText,
            $this->config->getEncryptionInitVector(),
            $this->config->getEncryptionKey(),
        );
    }

    public function decrypt(string $cipherText): string
    {
        return $this->utilEncryptionService->decryptOpenSsl(
            $cipherText,
            $this->config->getEncryptionInitVector(),
            $this->config->getEncryptionKey(),
        );
    }
}
