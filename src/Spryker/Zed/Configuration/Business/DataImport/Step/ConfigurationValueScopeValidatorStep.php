<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\DataImport\Step;

use Spryker\Zed\Configuration\Business\DataImport\DataSet\ConfigurationValueDataSetInterface;
use Spryker\Zed\Configuration\ConfigurationConfig;
use Spryker\Zed\DataImport\Business\Exception\DataImportException;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\DataImportStepInterface;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;

class ConfigurationValueScopeValidatorStep implements DataImportStepInterface
{
    public function __construct(protected ConfigurationConfig $config)
    {
    }

    /**
     * @param \Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface<string> $dataSet
     *
     * @throws \Spryker\Zed\DataImport\Business\Exception\DataImportException
     */
    public function execute(DataSetInterface $dataSet): void
    {
        $scope = $dataSet[ConfigurationValueDataSetInterface::COLUMN_SCOPE];
        $availableScopes = $this->config->getAvailableScopes();

        if (!in_array($scope, $availableScopes, true)) {
            throw new DataImportException(
                sprintf('Scope "%s" is not valid. Available scopes: %s', $scope, implode(', ', $availableScopes)),
            );
        }
    }
}
