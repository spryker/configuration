<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Communication\Console;

use Spryker\Zed\Configuration\Business\ConfigurationFacade;
use Spryker\Zed\Configuration\Communication\ConfigurationCommunicationFactory;
use Spryker\Zed\Configuration\Communication\Console\RestoreConfigurationConsole;
use Spryker\Zed\Kernel\Business\AbstractFacade;
use Symfony\Component\Console\Output\BufferedOutput;

class RestoreConfigurationConsoleStub extends RestoreConfigurationConsole
{
    protected BufferedOutput $bufferedOutput;

    public function __construct(
        protected ConfigurationFacade $configurationFacade,
        protected ConfigurationCommunicationFactory $configurationCommunicationFactory
    ) {
        parent::__construct();

        $this->bufferedOutput = new BufferedOutput();
        $this->output = $this->bufferedOutput;
    }

    public function getOutputForTest(): string
    {
        return $this->bufferedOutput->fetch();
    }

    /**
     * @param array<string> $settingKeys
     */
    public function restoreFileForTest(string $file, array $settingKeys): int
    {
        return $this->restoreFile($file, $settingKeys);
    }

    public function getFailedCountForTest(): int
    {
        return $this->failedCount;
    }

    protected function getFacade(): AbstractFacade
    {
        return $this->configurationFacade;
    }

    protected function getFactory(): ConfigurationCommunicationFactory
    {
        return $this->configurationCommunicationFactory;
    }
}
