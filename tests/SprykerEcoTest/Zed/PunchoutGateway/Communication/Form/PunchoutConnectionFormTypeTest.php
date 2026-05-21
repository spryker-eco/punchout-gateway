<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Zed\PunchoutGateway\Communication\Form;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\StoreTransfer;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;
use SprykerEco\Zed\PunchoutGateway\Communication\Form\PunchoutConnectionFormType;
use SprykerEco\Zed\PunchoutGateway\Communication\Form\PunchoutCxmlConfigurationFormType;
use SprykerEco\Zed\PunchoutGateway\Communication\Form\PunchoutCxmlConfigurationFormType as CxmlFormType;
use SprykerEco\Zed\PunchoutGateway\Communication\Form\PunchoutOciConfigurationFormType;
use SprykerEco\Zed\PunchoutGateway\Communication\Plugin\PunchoutGateway\DefaultCxmlProcessorPlugin;
use SprykerEco\Zed\PunchoutGateway\Communication\Plugin\PunchoutGateway\DefaultOciProcessorPlugin;
use SprykerEcoTest\Zed\PunchoutGateway\PunchoutGatewayCommunicationTester;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Validation;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group PunchoutGateway
 * @group Communication
 * @group Form
 * @group PunchoutConnectionFormTypeTest
 */
class PunchoutConnectionFormTypeTest extends Unit
{
    use LocatorHelperTrait;

    protected PunchoutGatewayCommunicationTester $tester;

    protected StoreTransfer $storeTransfer;

    public function _before()
    {
        $this->storeTransfer = $this->getLocator()->store()->facade()->getAllStores()[0];
    }

    public function testValidateOciWithNonOciProtocolTypeSkipsValidation(): void
    {
        // Arrange
        $form = $this->createConnectionForm($this->buildCxmlFormOptions());

        // Act
        $form->submit($this->buildCxmlFormData('test-connection', 'test-slug'));

        // Assert
        $this->assertFalse($this->hasRequestUrlAlreadyExistsError($form));
    }

    public function testValidateOciWithUniqueRequestUrlDoesNotAddError(): void
    {
        // Arrange
        $form = $this->createConnectionForm($this->buildOciFormOptions());

        // Act
        $form->submit($this->buildOciFormData(sprintf('unique-slug-%s', uniqid())));

        // Assert
        $this->assertFalse($this->hasRequestUrlAlreadyExistsError($form));
    }

    public function testValidateOciWithDuplicateRequestUrlAddsError(): void
    {
        // Arrange
        $slug = sprintf('oci-slug-%s', uniqid());
        $this->tester->havePunchoutConnection([
            'fk_store' => $this->storeTransfer->getIdStore(),
            'request_url' => PunchoutGatewayConfig::OCI_URL_PREFIX . $slug,
            'protocol_type' => PunchoutGatewayConfig::PROTOCOL_TYPE_OCI,
        ]);
        $form = $this->createConnectionForm($this->buildOciFormOptions());

        // Act
        $form->submit($this->buildOciFormData($slug));

        // Assert
        $this->assertTrue($this->hasRequestUrlAlreadyExistsError($form));
    }

    public function testValidateOciWithDuplicateRequestUrlFromExcludedConnectionDoesNotAddError(): void
    {
        // Arrange
        $slug = sprintf('oci-slug-%s', uniqid());
        $existingConnection = $this->tester->havePunchoutConnection([
            'fk_store' => $this->storeTransfer->getIdStore(),
            'request_url' => PunchoutGatewayConfig::OCI_URL_PREFIX . $slug,
            'protocol_type' => PunchoutGatewayConfig::PROTOCOL_TYPE_OCI,
        ]);
        $form = $this->createConnectionForm($this->buildOciFormOptions($existingConnection->getIdPunchoutConnection()));

        // Act
        $form->submit($this->buildOciFormData($slug));

        // Assert
        $this->assertFalse($this->hasRequestUrlAlreadyExistsError($form));
    }

    public function testValidateProcessorPluginWithCompatiblePluginDoesNotAddError(): void
    {
        // Arrange
        $form = $this->createConnectionForm($this->buildPluginFormOptions(DefaultOciProcessorPlugin::class, 'oci'));

        // Act
        $form->submit($this->buildPluginFormData('oci', DefaultOciProcessorPlugin::class));

        // Assert
        $this->assertFalse($this->hasProcessorPluginIncompatibleError($form));
    }

    public function testValidateProcessorPluginWithIncompatiblePluginAddsError(): void
    {
        // Arrange
        $form = $this->createConnectionForm($this->buildPluginFormOptions(DefaultCxmlProcessorPlugin::class, 'oci'));

        // Act
        $form->submit($this->buildPluginFormData('oci', DefaultCxmlProcessorPlugin::class));

        // Assert
        $this->assertTrue($this->hasProcessorPluginIncompatibleError($form));
    }

    protected function createConnectionForm(array $options = []): FormInterface
    {
        return $this->createFormFactory()->create(PunchoutConnectionFormType::class, null, $options);
    }

    protected function createFormFactory(): FormFactoryInterface
    {
        return Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->addExtension(new PreloadedExtension([
                new PunchoutOciConfigurationFormType(),
                new PunchoutCxmlConfigurationFormType(),
            ], []))
            ->getFormFactory();
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildOciFormOptions(?int $idPunchoutConnection = null): array
    {
        return [
            PunchoutConnectionFormType::OPTION_PROTOCOL_TYPE_CHOICES => [
                'OCI' => PunchoutGatewayConfig::PROTOCOL_TYPE_OCI,
                'cXML' => PunchoutGatewayConfig::PROTOCOL_TYPE_CXML,
            ],
            PunchoutConnectionFormType::OPTION_STORE_CHOICES => ['Test Store' => 1],
            PunchoutConnectionFormType::OPTION_PROCESSOR_PLUGINS_CHOICES => [
                'OCI Plugin' => DefaultOciProcessorPlugin::class,
            ],
            PunchoutConnectionFormType::OPTION_PROCESSOR_PLUGINS_TYPE_MAP => [
                DefaultOciProcessorPlugin::class => PunchoutGatewayConfig::PROTOCOL_TYPE_OCI,
            ],
            CxmlFormType::OPTION_IS_CREATE => true,
            PunchoutConnectionFormType::OPTION_ID_PUNCHOUT_CONNECTION => $idPunchoutConnection,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildCxmlFormOptions(): array
    {
        return [
            PunchoutConnectionFormType::OPTION_PROTOCOL_TYPE_CHOICES => [
                'cXML' => PunchoutGatewayConfig::PROTOCOL_TYPE_CXML,
            ],
            PunchoutConnectionFormType::OPTION_STORE_CHOICES => ['Test Store' => 1],
            PunchoutConnectionFormType::OPTION_PROCESSOR_PLUGINS_CHOICES => [
                'cXML Plugin' => DefaultCxmlProcessorPlugin::class,
            ],
            PunchoutConnectionFormType::OPTION_PROCESSOR_PLUGINS_TYPE_MAP => [
                DefaultCxmlProcessorPlugin::class => PunchoutGatewayConfig::PROTOCOL_TYPE_CXML,
            ],
            CxmlFormType::OPTION_IS_CREATE => true,
            PunchoutConnectionFormType::OPTION_ID_PUNCHOUT_CONNECTION => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildPluginFormOptions(string $pluginClass, string $protocolType): array
    {
        return [
            PunchoutConnectionFormType::OPTION_PROTOCOL_TYPE_CHOICES => [
                'OCI' => PunchoutGatewayConfig::PROTOCOL_TYPE_OCI,
            ],
            PunchoutConnectionFormType::OPTION_STORE_CHOICES => ['Test Store' => 1],
            PunchoutConnectionFormType::OPTION_PROCESSOR_PLUGINS_CHOICES => [
                'Plugin' => $pluginClass,
            ],
            PunchoutConnectionFormType::OPTION_PROCESSOR_PLUGINS_TYPE_MAP => [
                $pluginClass => $protocolType,
            ],
            CxmlFormType::OPTION_IS_CREATE => true,
            PunchoutConnectionFormType::OPTION_ID_PUNCHOUT_CONNECTION => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildOciFormData(string $requestUrlSlug): array
    {
        return [
            'name' => 'Test Connection',
            'idStore' => 1,
            'protocolType' => PunchoutGatewayConfig::PROTOCOL_TYPE_OCI,
            'processorPluginClass' => DefaultOciProcessorPlugin::class,
            'isActive' => '1',
            'allowIframe' => '',
            'requestUrl' => $requestUrlSlug,
            'ociConfiguration' => ['formMethod' => 'POST', 'usernameField' => '', 'passwordField' => ''],
            'cxmlConfiguration' => ['senderIdentity' => '', 'senderSharedSecret' => ''],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildCxmlFormData(string $name, string $requestUrlSlug): array
    {
        return [
            'name' => $name,
            'idStore' => 1,
            'protocolType' => PunchoutGatewayConfig::PROTOCOL_TYPE_CXML,
            'processorPluginClass' => DefaultCxmlProcessorPlugin::class,
            'isActive' => '1',
            'allowIframe' => '',
            'requestUrl' => $requestUrlSlug,
            'ociConfiguration' => ['formMethod' => 'POST', 'usernameField' => '', 'passwordField' => ''],
            'cxmlConfiguration' => ['senderIdentity' => '', 'senderSharedSecret' => ''],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildPluginFormData(string $protocolType, string $pluginClass): array
    {
        return [
            'name' => 'Test Connection',
            'idStore' => 1,
            'protocolType' => $protocolType,
            'processorPluginClass' => $pluginClass,
            'isActive' => '1',
            'allowIframe' => '',
            'requestUrl' => 'test-slug',
            'ociConfiguration' => ['formMethod' => 'POST', 'usernameField' => '', 'passwordField' => ''],
            'cxmlConfiguration' => ['senderIdentity' => '', 'senderSharedSecret' => ''],
        ];
    }

    protected function hasRequestUrlAlreadyExistsError(FormInterface $form): bool
    {
        if (!$form->has('requestUrl')) {
            return false;
        }

        foreach ($form->get('requestUrl')->getErrors() as $error) {
            if (str_contains($error->getMessage(), 'already exists')) {
                return true;
            }
        }

        return false;
    }

    protected function hasProcessorPluginIncompatibleError(FormInterface $form): bool
    {
        foreach ($form->get('processorPluginClass')->getErrors() as $error) {
            if (str_contains($error->getMessage(), 'not compatible')) {
                return true;
            }
        }

        return false;
    }
}
