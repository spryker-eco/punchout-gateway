<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Yves\PunchoutGateway\Model;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\PunchoutSessionStartResponseTransfer;
use Spryker\Client\Customer\CustomerClientInterface;
use Spryker\Client\Quote\QuoteClientInterface;
use Spryker\Client\Session\SessionClientInterface;
use SprykerEco\Shared\PunchoutGateway\Logger\PunchoutLoggerInterface;
use SprykerShop\Shared\CustomerPage\CustomerPageConfig;
use SprykerShop\Yves\CustomerPage\Security\Customer;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class LoginModel implements LoginModelInterface
{
    /**
     * @uses \SprykerShop\Yves\CustomerPage\Expander\SecurityBuilderExpander::ROLE_NAME_USER
     */
    protected const string ROLE_NAME_USER = 'ROLE_USER';

    /**
     * @uses \SprykerShop\Yves\StoreWidget\Plugin\ShopApplication\StoreApplicationPlugin::SESSION_STORE
     */
    protected const string SESSION_KEY_CURRENT_STORE = 'current_store';

    public function __construct(
        protected TokenStorageInterface $securityTokenStorage,
        protected SessionClientInterface $sessionClient,
        protected CustomerClientInterface $customerClient,
        protected QuoteClientInterface $quoteClient,
        protected PunchoutLoggerInterface $punchoutLogger,
    ) {
    }

    public function loginCustomerFromSession(PunchoutSessionStartResponseTransfer $sessionStartResponseTransfer): void
    {
        if ($sessionStartResponseTransfer->getQuote()) {
            $this->quoteClient->setQuote($sessionStartResponseTransfer->getQuote());
        }

        $customerTransfer = $sessionStartResponseTransfer->getCustomerOrFail();

        if ($sessionStartResponseTransfer->getStoreName()) {
            $customerTransfer->setStoreName($sessionStartResponseTransfer->getStoreName());
        }

        $this->authenticateCustomer($customerTransfer);

        if ($sessionStartResponseTransfer->getStoreName() !== null) {
            $this->sessionClient->set(static::SESSION_KEY_CURRENT_STORE, $sessionStartResponseTransfer->getStoreName());
        }
    }

    protected function authenticateCustomer(CustomerTransfer $customerTransfer): void
    {
        $securityUser = new Customer(
            $customerTransfer,
            $customerTransfer->getEmail(),
            '',
            [static::ROLE_NAME_USER],
        );

        $token = new UsernamePasswordToken(
            $securityUser,
            CustomerPageConfig::SECURITY_FIREWALL_NAME,
            [static::ROLE_NAME_USER],
        );

        $this->securityTokenStorage->setToken($token);

        $this->customerClient->setCustomer($customerTransfer);
    }
}
