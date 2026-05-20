<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Zed\PunchoutGateway\Communication\Form;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\StoreTransfer;
use SprykerEco\Zed\PunchoutGateway\Communication\Form\PunchoutCxmlConfigurationFormType;
use SprykerEcoTest\Zed\PunchoutGateway\PunchoutGatewayCommunicationTester;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Validation;

/**
 * @group SprykerEcoTest
 * @group Zed
 * @group PunchoutGateway
 * @group Communication
 * @group Form
 * @group PunchoutCxmlConfigurationFormTypeTest
 */
class PunchoutCxmlConfigurationFormTypeTest extends Unit
{
    use LocatorHelperTrait;

    protected PunchoutGatewayCommunicationTester $tester;

    protected StoreTransfer $storeTransfer;

    public function _before()
    {
        $this->storeTransfer = $this->getLocator()->store()->facade()->getAllStores()[0];
    }

    public function testValidateSenderIdentityUniquenessWithNonCxmlModeSkipsValidation(): void
    {
        // Arrange
        $identity = sprintf('cxml-identity-%s', uniqid());
        $this->tester->havePunchoutConnection([
            'fk_store' => $this->storeTransfer->getIdStore(),
            'sender_identity' => $identity,
        ]);
        $form = $this->createCxmlConfigurationForm($this->buildFormOptions(isCxml: false));

        // Act
        $form->submit($this->buildFormData($identity));

        // Assert
        $this->assertFalse($this->hasSenderIdentityAlreadyExistsError($form));
    }

    public function testValidateSenderIdentityUniquenessWithEmptySenderIdentitySkipsValidation(): void
    {
        // Arrange
        $form = $this->createCxmlConfigurationForm($this->buildFormOptions(isCxml: true));

        // Act
        $form->submit($this->buildFormData(''));

        // Assert
        $this->assertFalse($this->hasSenderIdentityAlreadyExistsError($form));
    }

    public function testValidateSenderIdentityUniquenessWithUniqueIdentityDoesNotAddError(): void
    {
        // Arrange
        $form = $this->createCxmlConfigurationForm($this->buildFormOptions(isCxml: true));

        // Act
        $form->submit($this->buildFormData(sprintf('unique-identity-%s', uniqid())));

        // Assert
        $this->assertFalse($this->hasSenderIdentityAlreadyExistsError($form));
    }

    public function testValidateSenderIdentityUniquenessWithDuplicateIdentityAddsError(): void
    {
        // Arrange
        $identity = sprintf('cxml-identity-%s', uniqid());
        $this->tester->havePunchoutConnection([
            'fk_store' => $this->storeTransfer->getIdStore(),
            'sender_identity' => $identity,
        ]);
        $form = $this->createCxmlConfigurationForm($this->buildFormOptions(isCxml: true));

        // Act
        $form->submit($this->buildFormData($identity));

        // Assert
        $this->assertTrue($this->hasSenderIdentityAlreadyExistsError($form));
    }

    public function testValidateSenderIdentityUniquenessWithExcludedConnectionDoesNotAddError(): void
    {
        // Arrange
        $identity = sprintf('cxml-identity-%s', uniqid());
        $existingConnection = $this->tester->havePunchoutConnection([
            'fk_store' => $this->storeTransfer->getIdStore(),
            'sender_identity' => $identity,
        ]);
        $form = $this->createCxmlConfigurationForm(
            $this->buildFormOptions(isCxml: true, idPunchoutConnection: $existingConnection->getIdPunchoutConnection()),
        );

        // Act
        $form->submit($this->buildFormData($identity));

        // Assert
        $this->assertFalse($this->hasSenderIdentityAlreadyExistsError($form));
    }

    public function testValidateSharedSecretWithNonCxmlModeSkipsValidation(): void
    {
        // Arrange
        $form = $this->createCxmlConfigurationForm($this->buildFormOptions(isCxml: false));

        // Act
        $form->submit($this->buildFormData('some-identity', ''));

        // Assert
        $this->assertFalse($this->hasSharedSecretMandatoryError($form));
    }

    public function testValidateSharedSecretMissingOnCreateAddsError(): void
    {
        // Arrange
        $form = $this->createCxmlConfigurationForm($this->buildFormOptions(isCxml: true, isCreate: true));

        // Act
        $form->submit($this->buildFormData(sprintf('identity-%s', uniqid()), ''));

        // Assert
        $this->assertTrue($this->hasSharedSecretMandatoryError($form));
    }

    public function testValidateSharedSecretMissingOnUpdateDoesNotAddError(): void
    {
        // Arrange
        $form = $this->createCxmlConfigurationForm($this->buildFormOptions(isCxml: true, idPunchoutConnection: 1234));

        // Act
        $form->submit($this->buildFormData(sprintf('identity-%s', uniqid()), ''));

        // Assert
        $this->assertFalse($this->hasSharedSecretMandatoryError($form));
    }

    public function testValidateSharedSecretProvidedOnCreateDoesNotAddError(): void
    {
        // Arrange
        $form = $this->createCxmlConfigurationForm($this->buildFormOptions(isCxml: true));

        // Act
        $form->submit($this->buildFormData(sprintf('identity-%s', uniqid()), 'secret-value'));

        // Assert
        $this->assertFalse($this->hasSharedSecretMandatoryError($form));
    }

    protected function createCxmlConfigurationForm(array $options = []): FormInterface
    {
        return $this->createFormFactory()->create(PunchoutCxmlConfigurationFormType::class, null, $options);
    }

    protected function createFormFactory(): FormFactoryInterface
    {
        return Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->getFormFactory();
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildFormOptions(
        bool $isCxml = false,
        bool $isCreate = false,
        ?int $idPunchoutConnection = null,
    ): array {
        return [
            PunchoutCxmlConfigurationFormType::OPTION_IS_CXML => $isCxml,
            PunchoutCxmlConfigurationFormType::OPTION_IS_CREATE => $isCreate,
            PunchoutCxmlConfigurationFormType::OPTION_ID_PUNCHOUT_CONNECTION => $idPunchoutConnection,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildFormData(string $senderIdentity, string $senderSharedSecret = ''): array
    {
        return [
            'senderIdentity' => $senderIdentity,
            'senderSharedSecret' => $senderSharedSecret,
        ];
    }

    protected function hasSenderIdentityAlreadyExistsError(FormInterface $form): bool
    {
        foreach ($form->get('senderIdentity')->getErrors() as $error) {
            if (str_contains($error->getMessage(), 'already exists')) {
                return true;
            }
        }

        return false;
    }

    protected function hasSharedSecretMandatoryError(FormInterface $form): bool
    {
        foreach ($form->get('senderSharedSecret')->getErrors() as $error) {
            if (str_contains($error->getMessage(), 'mandatory')) {
                return true;
            }
        }

        return false;
    }
}
