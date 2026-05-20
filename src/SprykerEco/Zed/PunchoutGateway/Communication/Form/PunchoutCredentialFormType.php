<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Communication\Form;

use Generated\Shared\Transfer\PunchoutCredentialCriteriaTransfer;
use Generated\Shared\Transfer\PunchoutCredentialTransfer;
use Spryker\Zed\Gui\Communication\Form\Type\Select2ComboBoxType;
use Spryker\Zed\Kernel\Communication\Form\AbstractType;
use SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
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

    public const string OPTION_IS_EDIT = 'is_edit';

    public const string OPTION_PRESELECTED_ID_CUSTOMER = 'preselected_id_customer';

    public const string OPTION_ID_PUNCHOUT_CONNECTION = 'id_punchout_connection';

    public const string OPTION_PRESELECTED_ID_PUNCHOUT_CREDENTIAL = 'preselected_id_punchout_credential';

    protected const string FIELD_USERNAME = PunchoutCredentialTransfer::USERNAME;

    protected const string FIELD_ID_CUSTOMER = PunchoutCredentialTransfer::ID_CUSTOMER;

    protected const string FIELD_IS_ACTIVE = PunchoutCredentialTransfer::IS_ACTIVE;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addUsernameField($builder)
            ->addPasswordField($builder, $options)
            ->addIdCustomerField($builder, $options)
            ->addIsActiveField($builder);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($options): void {
            $this->validateUsername(
                $event,
                $options[static::OPTION_ID_PUNCHOUT_CONNECTION],
                $options[static::OPTION_PRESELECTED_ID_PUNCHOUT_CREDENTIAL],
            );
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            static::OPTION_IS_EDIT => false,
            static::OPTION_PRESELECTED_ID_CUSTOMER => null,
            static::OPTION_ID_PUNCHOUT_CONNECTION => 0,
            static::OPTION_PRESELECTED_ID_PUNCHOUT_CREDENTIAL => null,
        ]);
    }

    protected function validateUsername(FormEvent $event, int $idPunchoutConnection, ?int $idPunchoutCredential): void
    {
        $form = $event->getForm();
        $username = $form->get(static::FIELD_USERNAME)->getData();

        if (!$username) {
            return;
        }

        $criteriaTransfer = (new PunchoutCredentialCriteriaTransfer())
            ->setUsername($username);

        if ($idPunchoutConnection) {
            $criteriaTransfer->setIdPunchoutConnection($idPunchoutConnection);
        }

        $credentials = $this->getRepository()->getPunchoutCredentialCollection($criteriaTransfer)->getPunchoutCredentials();

        if ($credentials->count() === 0) {
            return;
        }

        $form->get(static::FIELD_USERNAME)
            ->addError(new FormError('A credential with this username already exists for this connection.'));
    }

    protected function addUsernameField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_USERNAME, TextType::class, [
            'label' => 'Username',
            'required' => true,
            'attr' => ['autocomplete' => 'off'],
            'constraints' => [
                new NotBlank(),
                new Length(['max' => 255]),
            ],
        ]);

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function addPasswordField(FormBuilderInterface $builder, array $options = []): static
    {
        $isEdit = (bool)($options[static::OPTION_IS_EDIT] ?? false);
        $constraints = [];

        if (!$isEdit) {
            $constraints[] = new NotBlank();
            $constraints[] = new Length(['min' => 6]);
        }

        $builder->add(static::FIELD_PASSWORD, RepeatedType::class, [
            'type' => PasswordType::class,
            'required' => !$isEdit,
            'first_options' => ['label' => 'Password', 'attr' => ['autocomplete' => 'new-password']],
            'second_options' => ['label' => 'Repeat Password', 'attr' => ['autocomplete' => 'new-password']],
            'invalid_message' => 'Passwords do not match.',
            'constraints' => $constraints,
        ]);

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function addIdCustomerField(FormBuilderInterface $builder, array $options = []): static
    {
        $builder->add(static::FIELD_ID_CUSTOMER, Select2ComboBoxType::class, [
            'label' => 'Customer ID',
            'required' => true,
            'choice_loader' => $this->getFactory()->createCustomerChoiceLoader($options[static::OPTION_PRESELECTED_ID_CUSTOMER] ?? null),
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
