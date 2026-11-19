<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Migrator;

use Generated\Shared\Transfer\ConfigurationSyncResponseTransfer;

interface ConfigurationSecretValueMigratorInterface
{
    /**
     * Encrypts the plain values of secret settings and decrypts the encrypted values of non-secret settings.
     *
     * @param array<string, array<string, mixed>> $settingsMap
     */
    public function migrate(array $settingsMap, ConfigurationSyncResponseTransfer $configurationSyncResponseTransfer): void;
}
