<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Configuration\Communication\Console;

use Spryker\Zed\Configuration\Business\ConfigurationFacade;
use Spryker\Zed\Configuration\Communication\Console\ExportConfigurationConsole;
use Spryker\Zed\Configuration\ConfigurationConfig;
use Spryker\Zed\Kernel\Business\AbstractFacade;
use Symfony\Component\Console\Output\BufferedOutput;

class ExportConfigurationConsoleStub extends ExportConfigurationConsole
{
    protected BufferedOutput $bufferedOutput;

    public function __construct(
        protected ConfigurationConfig $configurationConfig,
        protected ConfigurationFacade $configurationFacade
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
     * @param list<array<string, string>> $values
     */
    public function writeFileForTest(string $file, array $values): int
    {
        return $this->writeFile($file, $values);
    }

    public function getConfig(): ConfigurationConfig
    {
        return $this->configurationConfig;
    }

    protected function getFacade(): AbstractFacade
    {
        return $this->configurationFacade;
    }
}
