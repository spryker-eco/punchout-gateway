<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Communication\Form;

use Spryker\Zed\Kernel\Communication\Form\AbstractType;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;
use SprykerEco\Zed\PunchoutGateway\Communication\Form\DataTransformer\PunchoutOciMappingDataTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface getRepository()
 * @method \SprykerEco\Zed\PunchoutGateway\Communication\PunchoutGatewayCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig getConfig()
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayFacadeInterface getFacade()
 */
class PunchoutOciConfigurationFormType extends AbstractType
{
    public const string OPTION_IS_CREATE = 'is_create';

    public const string OPTION_OCI_FIELD_CHOICES = 'oci_field_choices';

    public const string OPTION_SOURCE_SUGGESTIONS_URL = 'source_suggestions_url';

    public const string MAPPING_FIELDS = 'mappingFields';

    protected const string FIELD_FORM_METHOD = 'formMethod';

    protected const string FIELD_USERNAME_FIELD = 'usernameField';

    protected const string FIELD_PASSWORD_FIELD = 'passwordField';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this
            ->addFormMethodField($builder)
            ->addUsernameFieldField($builder)
            ->addPasswordFieldField($builder);

        if (!$options[static::OPTION_IS_CREATE]) {
            $this->addMappingFieldsSection($builder, $options);
        }

        $builder->addModelTransformer(new PunchoutOciMappingDataTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            static::OPTION_IS_CREATE => false,
            static::OPTION_OCI_FIELD_CHOICES => [],
            static::OPTION_SOURCE_SUGGESTIONS_URL => '',
        ]);
    }

    protected function addFormMethodField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_FORM_METHOD, ChoiceType::class, [
            'label' => 'Form Method',
            'required' => false,
            'placeholder' => '',
            'choices' => ['POST' => 'POST', 'GET' => 'GET'],
            'help' => 'HTTP method for the form, defaults to %method',
            'help_translation_parameters' => ['%method' => PunchoutGatewayConfig::OCI_DEFAULT_FORM_METHOD],
        ]);

        return $this;
    }

    protected function addUsernameFieldField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_USERNAME_FIELD, TextType::class, [
            'label' => 'Username Field Name',
            'required' => false,
            'help' => 'Form field name to pass username, defaults to %field',
            'help_translation_parameters' => ['%field' => PunchoutGatewayConfig::OCI_DEFAULT_USERNAME_FIELD],
            'attr' => [
                'placeholder' => 'Defaults to %field',
            ],
            'attr_translation_parameters' => ['%field' => PunchoutGatewayConfig::OCI_DEFAULT_USERNAME_FIELD],
        ]);

        return $this;
    }

    protected function addPasswordFieldField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_PASSWORD_FIELD, TextType::class, [
            'label' => 'Password Field Name',
            'required' => false,
            'help' => 'Form field name to pass password, defaults to %field',
            'help_translation_parameters' => ['%field' => PunchoutGatewayConfig::OCI_DEFAULT_PASSWORD_FIELD],
            'attr' => [
                'placeholder' => 'Defaults to %field',
            ],
            'attr_translation_parameters' => ['%field' => PunchoutGatewayConfig::OCI_DEFAULT_PASSWORD_FIELD],
        ]);

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function addMappingFieldsSection(FormBuilderInterface $builder, array $options): static
    {
        $builder->add(static::MAPPING_FIELDS, CollectionType::class, [
            'label' => 'OCI Field Mapping',
            'entry_type' => PunchoutFieldMappingRowFormType::class,
            'entry_options' => [
                PunchoutFieldMappingRowFormType::OPTION_FIELD_CHOICES => $options[static::OPTION_OCI_FIELD_CHOICES],
                PunchoutFieldMappingRowFormType::OPTION_SOURCE_SUGGESTIONS_URL => $options[static::OPTION_SOURCE_SUGGESTIONS_URL],
            ],
            'allow_add' => true,
            'allow_delete' => true,
            'prototype' => true,
            'by_reference' => false,
            'required' => false,
        ]);

        return $this;
    }
}
