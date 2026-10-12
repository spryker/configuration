<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Communication\Console;

use Generated\Shared\Transfer\ConfigurationValueTransfer;
use Spryker\Zed\Configuration\Business\ConfigurationFacade;
use Spryker\Zed\Configuration\Communication\ConfigurationCommunicationFactory;
use Spryker\Zed\Configuration\Communication\Console\ImportEnvironmentConfigurationConsole;
use Spryker\Zed\Kernel\Business\AbstractFacade;
use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;
use Symfony\Component\Console\Output\BufferedOutput;

class ImportEnvironmentConfigurationConsoleStub extends ImportEnvironmentConfigurationConsole
{
    protected BufferedOutput $bufferedOutput;

    public function __construct(
        protected ConfigurationCommunicationFactory $configurationCommunicationFactory,
        protected ?ConfigurationFacade $configurationFacade = null
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
     * @param array<string> $secretSettingKeys
     */
    public function importEnvironmentVariableForTest(string $variableName, array $secretSettingKeys): void
    {
        $this->importEnvironmentVariable($variableName, $secretSettingKeys);
    }

    public function saveConfigurationValueForTest(ConfigurationValueTransfer $configurationValueTransfer, bool $isSecret): void
    {
        $this->saveConfigurationValue($configurationValueTransfer, $isSecret);
    }

    protected function getFactory(): AbstractCommunicationFactory
    {
        return $this->configurationCommunicationFactory;
    }

    protected function getFacade(): AbstractFacade
    {
        return $this->configurationFacade;
    }
}
