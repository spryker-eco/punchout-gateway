<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway;

use Spryker\Shared\Application\ApplicationConstants;
use Spryker\Zed\Kernel\AbstractBundleConfig;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig as PunchoutGatewayPunchoutGatewayConfig;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig as SharedPunchoutGatewayConfig;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConstants;

class PunchoutGatewayConfig extends AbstractBundleConfig
{
    public const string PARAM_ID_CONNECTION = 'id-punchout-connection';

    public const string PARAM_ID_CREDENTIAL = 'id-punchout-credential';

    public const string PARAM_REDIRECT_URL = 'redirect-url';

    public const string URL_LIST = '/punchout-gateway/list/index';

    public const string URL_EDIT = '/punchout-gateway/edit/index';

    public const string URL_VIEW = '/punchout-gateway/view/index';

    public const string URL_DELETE = '/punchout-gateway/delete/index';

    public const string URL_CONNECTION = '/punchout-gateway/view/index';

    public const string URL_TOGGLE_IS_ACTIVE = '/punchout-gateway/edit/toggle-is-active';

    public const string URL_CREDENTIAL_TABLE = '/punchout-gateway/credential/table';

    public const string URL_CREDENTIAL_EDIT = '/punchout-gateway/credential/edit-credential';

    public const string URL_CREDENTIAL_DELETE = '/punchout-gateway/credential/delete-credential';

    public const string URL_CREDENTIAL_TOGGLE_IS_ACTIVE = '/punchout-gateway/credential/toggle-is-active';

    public const string URL_CUSTOMER_SUGGEST = '/punchout-gateway/customer-suggest/index';

    public const string URL_SOURCE_FIELD_SUGGESTIONS = '/punchout-gateway/source-field-suggestions/index';

    protected const int DEFAULT_OCI_SESSION_VALIDITY_IN_SECONDS = 600;

    /**
     * @api
     */
    public function getBaseUrlYves(): string
    {
        return $this->get(ApplicationConstants::BASE_URL_YVES);
    }

    /**
     * @api
     */
    public function isLoggingEnabled(): bool
    {
        return (bool)$this->getModuleConfig(
            PunchoutGatewayPunchoutGatewayConfig::CONFIGURATION_KEY_ENABLE_LOGGING,
            $this->get(PunchoutGatewayConstants::ENABLE_LOGGING, false),
        );
    }

    /**
     * @api
     */
    public function getCxmlSessionStartUrlValidityInSeconds(): int
    {
        return (int)$this->getModuleConfig(
            PunchoutGatewayPunchoutGatewayConfig::CONFIGURATION_KEY_CXML_SESSION_START_URL_VALIDITY_IN_SECONDS,
            10 * 60,
        );
    }

    /**
     * @api
     */
    public function getOciSessionValidityInSeconds(): int
    {
        return static::DEFAULT_OCI_SESSION_VALIDITY_IN_SECONDS;
    }

    /**
     * @api
     */
    public function getOciDefaultStartUrl(): string
    {
        return '/';
    }

    /**
     * @api
     */
    public function getCxmlSessionTokenLength(): int
    {
        return (int)$this->getModuleConfig(
            PunchoutGatewayPunchoutGatewayConfig::CONFIGURATION_KEY_CXML_SESSION_TOKEN_LENGTH,
            32,
        );
    }

    /**
     * @api
     */
    public function getSourceFieldSuggestionLimit(): int
    {
        return 20;
    }

    /**
     * @api
     *
     * @return list<string>
     */
    public function getExtrinsicBlackList(): array
    {
        return SharedPunchoutGatewayConfig::EXTRINSIC_DENY_LIST;
    }
}
