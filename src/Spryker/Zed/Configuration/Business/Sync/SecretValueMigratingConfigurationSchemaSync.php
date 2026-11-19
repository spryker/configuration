<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\Sync;

use Generated\Shared\Transfer\ConfigurationSyncResponseTransfer;
use PDOException;
use Propel\Runtime\Connection\Exception\ConnectionException;
use Spryker\Zed\Configuration\Business\Migrator\ConfigurationSecretValueMigratorInterface;
use Spryker\Zed\Configuration\ConfigurationConfig;
use Throwable;

class SecretValueMigratingConfigurationSchemaSync implements ConfigurationSchemaSyncInterface
{
    /**
     * `42S02`: base table not found (MariaDB, MySQL); `42P01`: undefined table (PostgreSQL).
     *
     * @var array<string>
     */
    protected const array SQLSTATES_TABLE_NOT_FOUND = ['42S02', '42P01'];

    protected const string WARNING_MESSAGE_DATABASE_UNAVAILABLE = 'The database is not reachable or not installed, so the stored values were not checked against the "secret" flags. Run "configuration:sync" again where the database is available.';

    public function __construct(
        protected ConfigurationSchemaSyncInterface $configurationSchemaSync,
        protected ConfigurationSecretValueMigratorInterface $secretValueMigrator,
        protected ConfigurationConfig $config,
    ) {
    }

    public function sync(): ConfigurationSyncResponseTransfer
    {
        $configurationSyncResponseTransfer = $this->configurationSchemaSync->sync();

        if (!$configurationSyncResponseTransfer->getIsSuccess()) {
            return $configurationSyncResponseTransfer;
        }

        try {
            $this->secretValueMigrator->migrate($this->readSettingsMap(), $configurationSyncResponseTransfer);
        } catch (Throwable $throwable) {
            // The image build runs this command without a database; the deploy runs it again with one.
            if (!$this->isDatabaseUnavailableException($throwable)) {
                throw $throwable;
            }

            $configurationSyncResponseTransfer->addWarningMessage(static::WARNING_MESSAGE_DATABASE_UNAVAILABLE);
        }

        return $configurationSyncResponseTransfer;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function readSettingsMap(): array
    {
        $filePath = $this->config->getSettingsMapFilePath();

        if (!is_file($filePath)) {
            return [];
        }

        // The command line keeps OPcache on: without the invalidation, a map read earlier in this process would be served.
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($filePath, true);
        }

        $settingsMap = require $filePath;

        return is_array($settingsMap) ? $settingsMap : [];
    }

    protected function isDatabaseUnavailableException(Throwable $throwable): bool
    {
        $currentThrowable = $throwable;

        while ($currentThrowable !== null) {
            if ($currentThrowable instanceof ConnectionException || $this->isTableNotFoundException($currentThrowable)) {
                return true;
            }

            $currentThrowable = $currentThrowable->getPrevious();
        }

        return false;
    }

    protected function isTableNotFoundException(Throwable $throwable): bool
    {
        return $throwable instanceof PDOException && in_array((string)$throwable->getCode(), static::SQLSTATES_TABLE_NOT_FOUND, true);
    }
}
