<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Zed\PunchoutGateway\Communication\Form;

use Codeception\Test\Unit;
use Spryker\Zed\Gui\Communication\Form\Type\Select2ComboBoxType;
use SprykerEco\Zed\PunchoutGateway\Communication\Form\PunchoutCredentialFormType;
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
 * @group PunchoutCredentialFormTypeTest
 */
class PunchoutCredentialFormTypeTest extends Unit
{
    use LocatorHelperTrait;

    protected PunchoutGatewayCommunicationTester $tester;

    public function testValidateUsernameWithEmptyUsernameSkipsValidation(): void
    {
        // Arrange
        $form = $this->createCredentialForm();

        // Act
        $form->submit($this->buildFormData(''));

        // Assert
        $this->assertFalse($this->hasUsernameAlreadyExistsError($form));
    }

    public function testValidateUsernameWithNonExistingUsernameDoesNotAddError(): void
    {
        // Arrange
        $storeTransfer = $this->getLocator()->store()->facade()->getAllStores()[0];
        $connectionTransfer = $this->tester->havePunchoutConnection(['fk_store' => $storeTransfer->getIdStore()]);
        $form = $this->createCredentialForm([
            PunchoutCredentialFormType::OPTION_ID_PUNCHOUT_CONNECTION => $connectionTransfer->getIdPunchoutConnection(),
        ]);

        // Act
        $form->submit($this->buildFormData(sprintf('UniqueUser_%s', uniqid())));

        // Assert
        $this->assertFalse($this->hasUsernameAlreadyExistsError($form));
    }

    public function testValidateUsernameWithDuplicateUsernameForSameConnectionAddsError(): void
    {
        // Arrange
        $username = sprintf('ExistingUser_%s', uniqid());
        $storeTransfer = $this->getLocator()->store()->facade()->getAllStores()[0];
        $connectionTransfer = $this->tester->havePunchoutConnection(['fk_store' => $storeTransfer->getIdStore()]);
        $this->tester->havePunchoutCredential([
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnection(),
            'username' => $username,
        ]);
        $form = $this->createCredentialForm([
            PunchoutCredentialFormType::OPTION_ID_PUNCHOUT_CONNECTION => $connectionTransfer->getIdPunchoutConnection(),
        ]);

        // Act
        $form->submit($this->buildFormData($username));

        // Assert
        $this->assertTrue($this->hasUsernameAlreadyExistsError($form));
    }

    public function testValidateUsernameWithDuplicateUsernameForAnotherConnectionNoError(): void
    {
        // Arrange
        $username = sprintf('ExistingUser_%s', uniqid());
        $storeTransfer = $this->getLocator()->store()->facade()->getAllStores()[0];
        $connectionTransfer = $this->tester->havePunchoutConnection(['fk_store' => $storeTransfer->getIdStore(), 'protocolType' => 'oci', 'request_url' => 'aaa']);
        $connectionTransfer2 = $this->tester->havePunchoutConnection(['fk_store' => $storeTransfer->getIdStore(), 'protocolType' => 'oci', 'request_url' => 'bbb']);
        $this->tester->havePunchoutCredential([
            'fk_punchout_connection' => $connectionTransfer->getIdPunchoutConnection(),
            'username' => $username,
        ]);
        $form = $this->createCredentialForm([
            PunchoutCredentialFormType::OPTION_ID_PUNCHOUT_CONNECTION => $connectionTransfer2->getIdPunchoutConnection(),
        ]);

        // Act
        $form->submit($this->buildFormData($username));

        // Assert
        $this->assertFalse($this->hasUsernameAlreadyExistsError($form));
    }

    protected function createCredentialForm(array $options = []): FormInterface
    {
        return $this->createFormFactory()->create(PunchoutCredentialFormType::class, null, $options);
    }

    protected function createFormFactory(): FormFactoryInterface
    {
        return Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->addExtension(new PreloadedExtension([new Select2ComboBoxType()], []))
            ->getFormFactory();
    }

    protected function hasUsernameAlreadyExistsError(FormInterface $form): bool
    {
        foreach ($form->get('username')->getErrors() as $error) {
            if (str_contains($error->getMessage(), 'already exists')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildFormData(string $username): array
    {
        return [
            'username' => $username,
            'password' => ['first' => 'password1', 'second' => 'password1'],
            'idCustomer' => '',
            'isActive' => false,
        ];
    }
}
