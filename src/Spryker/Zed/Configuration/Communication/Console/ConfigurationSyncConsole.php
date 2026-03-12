<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Console;

use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method \Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface getFacade()
 * @method \Spryker\Zed\Configuration\Persistence\ConfigurationRepositoryInterface getRepository()
 */
class ConfigurationSyncConsole extends Console
{
    protected const string COMMAND_NAME = 'configuration:sync';

    protected const string COMMAND_DESCRIPTION = 'Synchronizes configuration schemas from YAML files to database';

    protected function configure(): void
    {
        $this->setName(static::COMMAND_NAME)
            ->setDescription(static::COMMAND_DESCRIPTION);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->info('Starting configuration schema synchronization...');

        $configurationSyncResponseTransfer = $this->getFacade()->syncConfigurationSchemas();

        if (!$configurationSyncResponseTransfer->getIsSuccess()) {
            foreach ($configurationSyncResponseTransfer->getErrorMessages() as $errorMessage) {
                $this->error($errorMessage);
            }

            return static::CODE_ERROR;
        }

        $this->success(
            sprintf(
                'Configuration schema synchronization completed successfully. Processed %d settings.',
                $configurationSyncResponseTransfer->getProcessedCount(),
            ),
        );

        return static::CODE_SUCCESS;
    }
}
