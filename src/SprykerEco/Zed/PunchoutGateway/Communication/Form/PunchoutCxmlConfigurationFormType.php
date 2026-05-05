<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Communication\Form;

use Spryker\Zed\Kernel\Communication\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface getRepository()
 * @method \SprykerEco\Zed\PunchoutGateway\Communication\PunchoutGatewayCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig getConfig()
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayFacadeInterface getFacade()
 */
class PunchoutCxmlConfigurationFormType extends AbstractType
{
    protected const string FIELD_SENDER_IDENTITY = 'senderIdentity';

    protected const string FIELD_SENDER_SHARED_SECRET = 'senderSharedSecret';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addSenderIdentityField($builder)
            ->addSenderSharedSecretField($builder);
    }

    protected function addSenderIdentityField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_SENDER_IDENTITY, TextType::class, [
            'label' => 'Sender Identity',
            'required' => true,
            'constraints' => [new NotBlank()],
            'attr' => ['placeholder' => 'e.g. MyCompanyDomain'],
        ]);

        return $this;
    }

    protected function addSenderSharedSecretField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_SENDER_SHARED_SECRET, TextType::class, [
            'label' => 'Sender Shared Secret',
            'required' => false,
            'attr' => [
                'placeholder' => 'Leave blank to keep current secret',
                'autocomplete' => 'new-password',
            ],
            'help' => 'Leave blank to keep the current secret.',
        ]);

        return $this;
    }
}
