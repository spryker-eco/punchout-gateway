<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Communication\Form;

use Spryker\Zed\Kernel\Communication\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface getRepository()
 * @method \SprykerEco\Zed\PunchoutGateway\Communication\PunchoutGatewayCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig getConfig()
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayFacadeInterface getFacade()
 */
class PunchoutCxmlConfigurationFormType extends AbstractType
{
    public const string OPTION_IS_CREATE = 'is_create';

    public const string OPTION_IS_CXML = 'is_cxml';

    public const string OPTION_ID_PUNCHOUT_CONNECTION = 'id_punchout_connection';

    public const string FIELD_SENDER_IDENTITY = 'senderIdentity';

    protected const string FIELD_SENDER_SHARED_SECRET = 'senderSharedSecret';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addSenderIdentityField($builder, $options[static::OPTION_IS_CXML], $options[static::OPTION_IS_CREATE])
            ->addSenderSharedSecretField($builder, $options[static::OPTION_IS_CREATE]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($options) {
            if (!$options[static::OPTION_IS_CXML]) {
                return;
            }

            $this->validateSenderIdentityUniqueness($event, $options[static::OPTION_ID_PUNCHOUT_CONNECTION]);
            $this->validateSharedSecret($event, $options[static::OPTION_ID_PUNCHOUT_CONNECTION]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            static::OPTION_IS_CREATE => false,
            static::OPTION_IS_CXML => false,
            static::OPTION_ID_PUNCHOUT_CONNECTION => null,
        ]);
    }

    protected function addSenderIdentityField(FormBuilderInterface $builder, bool $isCxml, bool $isCreate): static
    {
        $options = [
            'label' => 'Sender Identity',
            'required' => $isCxml || !$isCreate,
            'attr' => ['placeholder' => 'e.g. MyCompanyDomain'],
        ];

        if ($isCxml || !$isCreate) {
            $options['constraints'] = [new NotBlank()];
        }

        $builder->add(static::FIELD_SENDER_IDENTITY, TextType::class, $options);

        return $this;
    }

    protected function validateSharedSecret(FormEvent $event, ?int $excludeId): void
    {
        $sharedSecret = $event->getForm()->get(static::FIELD_SENDER_SHARED_SECRET)->getData();

        if (!$sharedSecret && !$excludeId) {
            $event->getForm()
                ->get(static::FIELD_SENDER_SHARED_SECRET)
                ->addError(new FormError('Shared Secret is mandatory for a cXML connection.'));
        }
    }

    protected function validateSenderIdentityUniqueness(FormEvent $event, ?int $excludeId): void
    {
        $senderIdentity = $event->getForm()->get(static::FIELD_SENDER_IDENTITY)->getData();

        if (!$senderIdentity) {
            return;
        }

        $existing = $this->getRepository()->findCxmlConnectionBySenderIdentity($senderIdentity);

        if ($existing === null || $existing->getIdPunchoutConnection() === $excludeId) {
            return;
        }

        $event->getForm()
            ->get(static::FIELD_SENDER_IDENTITY)
            ->addError(new FormError('A cXML connection with this Sender Identity already exists.'));
    }

    protected function addSenderSharedSecretField(FormBuilderInterface $builder, bool $isCreate): static
    {
        $options = [
            'label' => 'Sender Shared Secret',
            'required' => false,
            'attr' => [
                'placeholder' => $isCreate ? '' : 'Leave blank to keep current secret',
                'autocomplete' => 'new-password',
            ],
        ];

        if (!$isCreate) {
            $options['help'] = 'Leave blank to keep the current secret.';
        }

        $builder->add(static::FIELD_SENDER_SHARED_SECRET, PasswordType::class, $options);

        return $this;
    }
}
