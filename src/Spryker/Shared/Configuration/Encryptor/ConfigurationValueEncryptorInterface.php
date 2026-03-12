<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\Configuration\Encryptor;

interface ConfigurationValueEncryptorInterface
{
    /**
     * Encrypts the given plain text value using OpenSSL.
     *
     * @param string $plainText
     *
     * @return string
     */
    public function encrypt(string $plainText): string;

    /**
     * Decrypts the given cipher text value using OpenSSL.
     *
     * @param string $cipherText
     *
     * @return string
     */
    public function decrypt(string $cipherText): string;
}
