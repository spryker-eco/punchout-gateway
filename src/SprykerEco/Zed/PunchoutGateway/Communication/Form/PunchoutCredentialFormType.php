<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Communication\Form;

use Generated\Shared\Transfer\PunchoutCredentialTransfer;
use Spryker\Zed\Gui\Communication\Form\Type\Select2ComboBoxType;
use Spryker\Zed\Kernel\Communication\Form\AbstractType;
use SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface getRepository()
 * @method \SprykerEco\Zed\PunchoutGateway\Communication\PunchoutGatewayCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig getConfig()
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayFacadeInterface getFacade()
 */
class PunchoutCredentialFormType extends AbstractType
{
    public const string FIELD_PASSWORD = 'password';

    protected const string FIELD_USERNAME = PunchoutCredentialTransfer::USERNAME;

    protected const string FIELD_ID_CUSTOMER = PunchoutCredentialTransfer::ID_CUSTOMER;

    protected const string FIELD_IS_ACTIVE = PunchoutCredentialTransfer::IS_ACTIVE;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addUsernameField($builder)
            ->addPasswordField($builder)
            ->addIdCustomerField($builder)
            ->addIsActiveField($builder);
    }

    protected function addUsernameField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_USERNAME, TextType::class, [
            'label' => 'Username',
            'required' => true,
            'constraints' => [
                new NotBlank(),
                new Length(['max' => 255]),
            ],
        ]);

        return $this;
    }

    protected function addPasswordField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_PASSWORD, RepeatedType::class, [
            'type' => PasswordType::class,
            'required' => true,
            'first_options' => ['label' => 'Password'],
            'second_options' => ['label' => 'Repeat Password'],
            'invalid_message' => 'Passwords do not match.',
            'constraints' => [
                new NotBlank(),
                new Length(['min' => 6]),
            ],
        ]);

        return $this;
    }

    protected function addIdCustomerField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_ID_CUSTOMER, Select2ComboBoxType::class, [
            'label' => 'Customer ID',
            'required' => true,
            'choice_loader' => $this->getFactory()->createCustomerChoiceLoader(),
            'choice_value' => fn (?int $id) => $id !== null ? (string)$id : '',
            'attr' => [
                'data-autocomplete-url' => PunchoutGatewayConfig::URL_CUSTOMER_SUGGEST,
                'placeholder' => 'Search customer by email or name...',
            ],
        ]);

        return $this;
    }

    protected function addIsActiveField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_IS_ACTIVE, CheckboxType::class, [
            'label' => 'Active',
            'required' => false,
        ]);

        return $this;
    }
}
