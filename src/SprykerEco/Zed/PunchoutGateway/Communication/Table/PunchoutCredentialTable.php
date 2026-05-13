<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Communication\Table;

use Orm\Zed\Customer\Persistence\Map\SpyCustomerTableMap;
use Orm\Zed\PunchoutGateway\Persistence\Map\SpyPunchoutCredentialTableMap;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutCredentialQuery;
use Spryker\Service\UtilText\Model\Url\Url;
use Spryker\Zed\Gui\Communication\Table\AbstractTable;
use Spryker\Zed\Gui\Communication\Table\TableConfiguration;
use SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface;
use SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig;

class PunchoutCredentialTable extends AbstractTable
{
    protected const string COL_ID = SpyPunchoutCredentialTableMap::COL_ID_PUNCHOUT_CREDENTIAL;

    protected const string COL_USERNAME = SpyPunchoutCredentialTableMap::COL_USERNAME;

    protected const string COL_IS_ACTIVE = SpyPunchoutCredentialTableMap::COL_IS_ACTIVE;

    protected const string COL_CUSTOMER_NAME = 'customer_name';

    protected const string COL_ACTIONS = 'credential_actions';

    public function __construct(
        protected PunchoutGatewayRepositoryInterface $repository,
        protected int $idPunchoutConnection,
        protected string $tableUrl,
    ) {
    }

    protected function configure(TableConfiguration $config): TableConfiguration
    {
        $this->setTableIdentifier(sprintf('credential-table-%d', $this->idPunchoutConnection));

        $this->baseUrl = $this->tableUrl;

        $config->setHeader([
            static::COL_ID => 'ID',
            static::COL_USERNAME => 'Username',
            static::COL_CUSTOMER_NAME => 'Customer Name',
            static::COL_IS_ACTIVE => 'Status',
            static::COL_ACTIONS => 'Actions',
        ]);

        $config->setSearchable([static::COL_USERNAME]);
        $config->setSortable([static::COL_ID, static::COL_USERNAME, static::COL_IS_ACTIVE]);
        $config->setDefaultSortField(static::COL_ID, TableConfiguration::SORT_DESC);
        $config->setRawColumns([static::COL_IS_ACTIVE, static::COL_ACTIONS]);

        return $config;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function prepareData(TableConfiguration $config): array
    {
        $query = SpyPunchoutCredentialQuery::create()
            ->filterByFkPunchoutConnection($this->idPunchoutConnection)
            ->leftJoinWithSpyCustomer()
            ->withColumn(
                sprintf("CONCAT_WS(' ', %s, %s, %s)", SpyCustomerTableMap::COL_FIRST_NAME, SpyCustomerTableMap::COL_LAST_NAME, SpyCustomerTableMap::COL_EMAIL),
                static::COL_CUSTOMER_NAME,
            );

        $queryResults = $this->runQuery($query, $config);

        $results = [];

        foreach ($queryResults as $row) {
            $results[] = [
                static::COL_ID => $row[SpyPunchoutCredentialTableMap::COL_ID_PUNCHOUT_CREDENTIAL],
                static::COL_USERNAME => $row[SpyPunchoutCredentialTableMap::COL_USERNAME],
                static::COL_CUSTOMER_NAME => $row[static::COL_CUSTOMER_NAME] ?? '',
                static::COL_IS_ACTIVE => $row[SpyPunchoutCredentialTableMap::COL_IS_ACTIVE]
                    ? '<span class="label label-success">Active</span>'
                    : '<span class="label label-danger">Inactive</span>',
                static::COL_ACTIONS => $this->buildCredentialActionButtons($row),
            ];
        }

        return $results;
    }

    /**
     * @param array<string, mixed> $row
     */
    protected function buildCredentialActionButtons(array $row): string
    {
        $idCredential = $row[SpyPunchoutCredentialTableMap::COL_ID_PUNCHOUT_CREDENTIAL];

        return implode('', [
            $this->generateButton(
                Url::generate(PunchoutGatewayConfig::URL_CREDENTIAL_EDIT, [
                    PunchoutGatewayConfig::PARAM_ID_CREDENTIAL => $idCredential,
                    PunchoutGatewayConfig::PARAM_ID_CONNECTION => $this->idPunchoutConnection,
                ])->build(),
                'Edit',
                [static::BUTTON_CLASS => 'btn-edit'],
            ),
            $this->generateButton(
                Url::generate(PunchoutGatewayConfig::URL_CREDENTIAL_DELETE, [
                    PunchoutGatewayConfig::PARAM_ID_CREDENTIAL => $idCredential,
                    PunchoutGatewayConfig::PARAM_ID_CONNECTION => $this->idPunchoutConnection,
                ])->build(),
                'Delete',
                [
                    'class' => 'btn-danger',
                    'onclick' => sprintf(
                        'return confirm("%s")',
                        $this->getTranslator()->trans('Are you sure you want to proceed?'),
                    ),
                ],
            ),
            $this->createActivateButton($row),
        ]);
    }

    /**
     * @param array<string, mixed> $row
     */
    protected function createActivateButton(array $row): string
    {
        $idCredential = $row[SpyPunchoutCredentialTableMap::COL_ID_PUNCHOUT_CREDENTIAL];
        $idPunchoutConnection = $row[SpyPunchoutCredentialTableMap::COL_FK_PUNCHOUT_CONNECTION];

        $isActive = (bool)$row[SpyPunchoutCredentialTableMap::COL_IS_ACTIVE];
        $label = $isActive ? 'Deactivate' : 'Activate';
        $buttonClass = $isActive ? 'btn-danger safe-submit' : 'btn-success safe-submit';

        return $this->generateButton(
            Url::generate(PunchoutGatewayConfig::URL_CREDENTIAL_TOGGLE_IS_ACTIVE, [
                    PunchoutGatewayConfig::PARAM_ID_CREDENTIAL => $idCredential,
                    PunchoutGatewayConfig::PARAM_REDIRECT_URL =>
                        sprintf('%s?%s=%d', PunchoutGatewayConfig::URL_EDIT, PunchoutGatewayConfig::PARAM_ID_CONNECTION, $idPunchoutConnection),
                ])->build(),
            $label,
            [static::BUTTON_CLASS => $buttonClass],
        );
    }
}
