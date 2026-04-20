<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Persistence;

use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutConnectionQuery;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutCredentialQuery;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutSessionQuery;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use Spryker\Zed\Kernel\Persistence\AbstractPersistenceFactory;
use SprykerEco\Zed\PunchoutGateway\Persistence\Mapper\PunchoutConnectionMapper;
use SprykerEco\Zed\PunchoutGateway\Persistence\Mapper\PunchoutCredentialMapper;
use SprykerEco\Zed\PunchoutGateway\Persistence\Mapper\PunchoutSessionMapper;
use SprykerEco\Zed\PunchoutGateway\PunchoutGatewayDependencyProvider;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface getRepository()
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayEntityManagerInterface getEntityManager()
 * @method \SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig getConfig()
 */
class PunchoutGatewayPersistenceFactory extends AbstractPersistenceFactory
{
    public function createPunchoutConnectionMapper(): PunchoutConnectionMapper
    {
        return new PunchoutConnectionMapper(
            $this->getServiceUtilEncoding(),
        );
    }

    public function createPunchoutSessionMapper(): PunchoutSessionMapper
    {
        return new PunchoutSessionMapper(
            $this->getServiceUtilEncoding(),
        );
    }

    public function createSpyPunchoutConnectionQuery(): SpyPunchoutConnectionQuery
    {
        return SpyPunchoutConnectionQuery::create();
    }

    public function createSpyPunchoutSessionQuery(): SpyPunchoutSessionQuery
    {
        return SpyPunchoutSessionQuery::create();
    }

    public function createSpyPunchoutCredentialQuery(): SpyPunchoutCredentialQuery
    {
        return SpyPunchoutCredentialQuery::create();
    }

    public function createPunchoutCredentialMapper(): PunchoutCredentialMapper
    {
        return new PunchoutCredentialMapper();
    }

    public function getServiceUtilEncoding(): UtilEncodingServiceInterface
    {
        return $this->getProvidedDependency(PunchoutGatewayDependencyProvider::SERVICE_UTIL_ENCODING);
    }
}
