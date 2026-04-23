<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Business\DataImport\Step;

use Spryker\Shared\Configuration\ConfigurationConstants;
use Spryker\Zed\Configuration\Business\DataImport\DataSet\ConfigurationValueDataSetInterface;
use Spryker\Zed\DataImport\Business\Exception\DataImportException;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\DataImportStepInterface;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;

class ConfigurationValueScopeIdentifierValidatorStep implements DataImportStepInterface
{
    /**
     * @param \Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface<string> $dataSet
     *
     * @throws \Spryker\Zed\DataImport\Business\Exception\DataImportException
     */
    public function execute(DataSetInterface $dataSet): void
    {
        $scope = $dataSet[ConfigurationValueDataSetInterface::COLUMN_SCOPE];

        if ($scope === ConfigurationConstants::SCOPE_GLOBAL) {
            return;
        }

        $scopeIdentifier = $dataSet[ConfigurationValueDataSetInterface::COLUMN_SCOPE_IDENTIFIER] ?? '';

        if ($scopeIdentifier === '') {
            throw new DataImportException(
                sprintf('Scope identifier is required for non-global scope "%s".', $scope),
            );
        }
    }
}
