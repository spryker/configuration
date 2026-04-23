<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\Configuration;

use Codeception\Actor;
use Spryker\Client\Configuration\Reader\ConfigurationReaderInterface;

/**
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 * @method \Spryker\Client\Configuration\ConfigurationClientInterface getClient()
 *
 * @SuppressWarnings(\SprykerTest\Client\Configuration\PHPMD)
 */
class ConfigurationClientTester extends Actor
{
    use _generated\ConfigurationClientTesterActions;

    /**
     * Stubs out the Zed facade-reader path in the client factory so unit tests that exercise
     * only the storage-reader path do not trigger GlobalContainer resolution of the
     * `SERVICE_CONFIGURATION` application service (which is unavailable in unit context).
     */
    public function mockFacadeReaderPathAsUnavailable(ConfigurationReaderInterface $facadeReaderStub): void
    {
        $this->mockFactoryMethod('createConfigurationFacadeReader', $facadeReaderStub);
        $this->mockFactoryMethod('getIsConfigurationServiceProvided', false);
    }
}
