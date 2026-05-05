<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Communication\Form;

use Spryker\Zed\Kernel\Communication\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface getRepository()
 * @method \SprykerEco\Zed\PunchoutGateway\Communication\PunchoutGatewayCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig getConfig()
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayFacadeInterface getFacade()
 */
class PunchoutOciConfigurationFormType extends AbstractType
{
    protected const string FIELD_FORM_METHOD = 'formMethod';

    protected const string FIELD_USERNAME_FIELD = 'usernameField';

    protected const string FIELD_PASSWORD_FIELD = 'passwordField';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addFormMethodField($builder)
            ->addUsernameFieldField($builder)
            ->addPasswordFieldField($builder);
    }

    protected function addFormMethodField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_FORM_METHOD, ChoiceType::class, [
            'label' => 'Form Method',
            'required' => true,
            'choices' => ['POST' => 'POST', 'GET' => 'GET'],
            'constraints' => [new NotBlank()],
        ]);

        return $this;
    }

    protected function addUsernameFieldField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_USERNAME_FIELD, TextType::class, [
            'label' => 'Username Field Name',
            'required' => false,
            'attr' => ['placeholder' => 'e.g. USERNAME'],
        ]);

        return $this;
    }

    protected function addPasswordFieldField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_PASSWORD_FIELD, TextType::class, [
            'label' => 'Password Field Name',
            'required' => false,
            'attr' => ['placeholder' => 'e.g. PASSWORD'],
        ]);

        return $this;
    }
}
