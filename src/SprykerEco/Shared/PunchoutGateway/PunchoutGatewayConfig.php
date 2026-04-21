<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Shared\PunchoutGateway;

interface PunchoutGatewayConfig
{
    public const string PROTOCOL_TYPE_CXML = 'cxml';

    public const string PROTOCOL_TYPE_OCI = 'oci';

    public const string DEFAULT_QUOTE_NAME = 'Punchout';

    public const string OPERATION_EDIT = 'edit';

    public const string OPERATION_CREATE = 'create';

    public const string OCI_HOOK_URL_FIELD = 'HOOK_URL';

    public const string ERROR_QUOTE_WAS_NOT_CREATED = 'Failed to create a cart for the session.';

    public const string ERROR_CUSTOMER_NOT_RESOLVED = 'Customer could not be resolved';

    public const string ERROR_AUTHENTICATION_FAILED = 'Authentication failed';

    public const string ERROR_CONNECTION_NOT_FOUND = 'No active connection was found.';

    public const string ERROR_SESSION_CREATION_FAILED = 'Session creation failed.';

    public const string DEFAULT_CXML_CREDENTIAL_DOMAIN = 'DUNS';

    public const string DEFAULT_CXML_LANGUAGE = 'en-US';

    public const string DEFAULT_CXML_SENDER_USER_AGENT = 'Spryker cXML';

    public const string DEFAULT_CXML_SHIPPING_DESCRIPTION = 'Shipping';

    public const string DEFAULT_CXML_TAX_DESCRIPTION = 'Tax';

    public const string DEFAULT_UNIT_OF_MEASURE = 'EA';

    public const string CXML_FORM_FIELD_NAME = 'cxml-urlencoded';

    public const string FORM_DATA_FIELD_TARGET = '~TARGET';

    public const string SESSION_KEY_PUNCHOUT_CSP_FRAGMENT = 'punchout_csp_fragment';

    public const string CXML_SESSION_START_URL = '/punchout-cxml-start?session=%s';

    public const string OCI_DEFAULT_USERNAME_FIELD = 'USERNAME';

    public const string OCI_DEFAULT_PASSWORD_FIELD = 'PASSWORD';
}
