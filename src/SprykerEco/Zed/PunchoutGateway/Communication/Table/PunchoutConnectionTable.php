<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Communication\Table;

use Orm\Zed\PunchoutGateway\Persistence\Map\SpyPunchoutConnectionTableMap;
use Orm\Zed\PunchoutGateway\Persistence\SpyPunchoutConnectionQuery;
use Orm\Zed\Store\Persistence\Map\SpyStoreTableMap;
use Spryker\Service\UtilText\Model\Url\Url;
use Spryker\Zed\Gui\Communication\Table\AbstractTable;
use Spryker\Zed\Gui\Communication\Table\TableConfiguration;
use SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface;
use SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig;

class PunchoutConnectionTable extends AbstractTable
{
    protected const string COL_ID = SpyPunchoutConnectionTableMap::COL_ID_PUNCHOUT_CONNECTION;

    protected const string COL_STORE = SpyPunchoutConnectionTableMap::COL_FK_STORE;

    protected const string COL_NAME = SpyPunchoutConnectionTableMap::COL_NAME;

    protected const string COL_PROTOCOL_TYPE = SpyPunchoutConnectionTableMap::COL_PROTOCOL_TYPE;

    protected const string COL_IS_ACTIVE = SpyPunchoutConnectionTableMap::COL_IS_ACTIVE;

    protected const string COL_ACTIONS = 'actions';

    public function __construct(
        protected PunchoutGatewayRepositoryInterface $repository,
        protected PunchoutGatewayConfig $moduleConfig
    ) {
    }

    protected function configure(TableConfiguration $config): TableConfiguration
    {
        $config->setHeader([
            static::COL_ID => 'ID',
            static::COL_NAME => 'Name',
            static::COL_PROTOCOL_TYPE => 'Protocol',
            static::COL_IS_ACTIVE => 'Status',
            static::COL_STORE => 'Customer Default Store',
            static::COL_ACTIONS => 'Actions',
        ]);

        $config->setSearchable([
            static::COL_NAME,
            static::COL_PROTOCOL_TYPE,
        ]);

        $config->setSortable([
            static::COL_ID,
            static::COL_NAME,
            static::COL_STORE,
            static::COL_PROTOCOL_TYPE,
            static::COL_IS_ACTIVE,
        ]);

        $config->setDefaultSortField(static::COL_ID, TableConfiguration::SORT_DESC);
        $config->setRawColumns([static::COL_IS_ACTIVE, static::COL_ACTIONS]);

        return $config;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function prepareData(TableConfiguration $config): array
    {
        $query = SpyPunchoutConnectionQuery::create()
            ->joinWithSpyStore();

        $queryResults = $this->runQuery($query, $config);

        $results = [];

        foreach ($queryResults as $row) {
            $dataRow = [
                static::COL_ID => $row[SpyPunchoutConnectionTableMap::COL_ID_PUNCHOUT_CONNECTION],
                static::COL_NAME => $row[SpyPunchoutConnectionTableMap::COL_NAME],
                static::COL_STORE => $row['SpyStore'][SpyStoreTableMap::COL_NAME],
                static::COL_PROTOCOL_TYPE => $row[SpyPunchoutConnectionTableMap::COL_PROTOCOL_TYPE],
                static::COL_IS_ACTIVE => $this->renderStatus((bool)$row[SpyPunchoutConnectionTableMap::COL_IS_ACTIVE]),
                static::COL_ACTIONS => $this->buildActionButtons($row),
            ];

            $results[] = $dataRow;
        }

        return $results;
    }

    /**
     * @param array<string, mixed> $row
     */
    protected function buildActionButtons(array $row): string
    {
        $idConnection = $row[SpyPunchoutConnectionTableMap::COL_ID_PUNCHOUT_CONNECTION];

        $editButton = $this->generateEditButton(
            Url::generate(PunchoutGatewayConfig::URL_EDIT, [PunchoutGatewayConfig::PARAM_ID_CONNECTION => $idConnection])->build(),
            'Edit',
        );

        $viewButton = $this->generateViewButton(
            Url::generate(PunchoutGatewayConfig::URL_VIEW, [PunchoutGatewayConfig::PARAM_ID_CONNECTION => $idConnection])->build(),
            'View',
        );

        $deleteButton = $this->generateButton(
            Url::generate(PunchoutGatewayConfig::URL_DELETE, [
                PunchoutGatewayConfig::PARAM_ID_CONNECTION => $idConnection,
                PunchoutGatewayConfig::PARAM_REDIRECT_URL => PunchoutGatewayConfig::URL_LIST,
            ])->build(),
            'Delete',
            [
                'class' => 'btn-danger',
                'onclick' => sprintf(
                    'return confirm("%s")',
                    $this->getTranslator()->trans('Are you sure you want to proceed?'),
                ),
            ],
        );

        $statusToggleButton = $this->renderStatusToggle(
            $row[SpyPunchoutConnectionTableMap::COL_ID_PUNCHOUT_CONNECTION],
            (bool)$row[SpyPunchoutConnectionTableMap::COL_IS_ACTIVE],
        );

        return $editButton . $viewButton . $deleteButton . $statusToggleButton;
    }

    protected function renderStatusToggle(int $idConnection, bool $isActive): string
    {
        $label = $isActive ? 'Deactivate' : 'Activate';
        $buttonClass = $isActive ? 'btn-success safe-submit' : 'btn-danger safe-submit';

        return $this->generateButton(
            Url::generate(PunchoutGatewayConfig::URL_TOGGLE_IS_ACTIVE, [
                PunchoutGatewayConfig::PARAM_ID_CONNECTION => $idConnection,
                PunchoutGatewayConfig::PARAM_REDIRECT_URL => PunchoutGatewayConfig::URL_LIST,
            ])->build(),
            $label,
            [static::BUTTON_CLASS => $buttonClass],
        );
    }

    protected function renderStatus(bool $isActive): string
    {
        $label = $isActive ? 'Active' : 'Inactive';
        $class = $isActive ? 'label-success' : 'label-danger';

        return sprintf('<span class="label %s">%s</span>', $class, $label);
    }
}
