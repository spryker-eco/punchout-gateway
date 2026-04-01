<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerEco\Shared\PunchoutGateway;

/**
 * Declares global environment configuration keys. Do not use it for other class constants.
 */
interface PunchoutGatewayConstants
{
    public const string PROTOCOL_TYPE_CXML = 'cxml';

    public const string PROTOCOL_TYPE_OCI = 'oci';

    public const string OPERATION_EDIT = 'edit';

    public const string OPERATION_CREATE = 'create';

    public const string OCI_DEFAULT_USERNAME_FIELD = 'USERNAME';

    public const string OCI_DEFAULT_PASSWORD_FIELD = 'PASSWORD';

    public const string OCI_DEFAULT_HOOK_URL_FIELD = 'HOOK_URL';

    public const string CXML_SESSION_START_URL = '/punchout-gateway/cxml/start?session=%s';

    public const string ERROR_CUSTOMER_NOT_RESOLVED = 'Customer could not be resolved';

    public const string ERROR_AUTHENTICATION_FAILED = 'Authentication failed';

    public const string ERROR_CONNECTION_NOT_FOUND = 'No active connection was found.';
}
