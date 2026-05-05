<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEco\Zed\PunchoutGateway\Communication\Form;

use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Spryker\Zed\Kernel\Communication\Form\AbstractType;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface getRepository()
 * @method \SprykerEco\Zed\PunchoutGateway\Communication\PunchoutGatewayCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig getConfig()
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayFacadeInterface getFacade()
 */
class PunchoutConnectionFormType extends AbstractType
{
    public const string OPTION_STORE_CHOICES = 'store_choices';

    public const string OPTION_PROTOCOL_TYPE_CHOICES = 'protocol_type_choices';

    protected const string FIELD_NAME = PunchoutConnectionTransfer::NAME;

    protected const string FIELD_ID_STORE = PunchoutConnectionTransfer::ID_STORE;

    protected const string FIELD_PROTOCOL_TYPE = PunchoutConnectionTransfer::PROTOCOL_TYPE;

    protected const string FIELD_IS_ACTIVE = PunchoutConnectionTransfer::IS_ACTIVE;

    protected const string FIELD_ALLOW_IFRAME = PunchoutConnectionTransfer::ALLOW_IFRAME;

    protected const string FIELD_REQUEST_URL = PunchoutConnectionTransfer::REQUEST_URL;

    protected const string FIELD_PROCESSOR_PLUGIN_CLASS = PunchoutConnectionTransfer::PROCESSOR_PLUGIN_CLASS;

    protected const string FIELD_CXML_CONFIGURATION = PunchoutConnectionTransfer::CXML_CONFIGURATION;

    protected const string FIELD_OCI_CONFIGURATION = PunchoutConnectionTransfer::OCI_CONFIGURATION;

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addNameField($builder)
            ->addIdStoreField($builder, $options)
            ->addProtocolTypeField($builder, $options)
            ->addIsActiveField($builder)
            ->addAllowIframeField($builder)
            ->addRequestUrlField($builder)
            ->addProcessorPluginClassField($builder);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $this->addProtocolConfigurationFields($event);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $this->addProtocolConfigurationFields($event);
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $this->validatePostSubmit($event);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            static::OPTION_STORE_CHOICES => [],
            static::OPTION_PROTOCOL_TYPE_CHOICES => [],
        ]);
    }

    protected function addNameField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_NAME, TextType::class, [
            'label' => 'Connection Name',
            'required' => true,
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
    protected function addIdStoreField(FormBuilderInterface $builder, array $options): static
    {
        $builder->add(static::FIELD_ID_STORE, ChoiceType::class, [
            'label' => 'Store',
            'required' => true,
            'choices' => $options[static::OPTION_STORE_CHOICES],
            'constraints' => [new NotBlank()],
        ]);

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function addProtocolTypeField(FormBuilderInterface $builder, array $options): static
    {
        $builder->add(static::FIELD_PROTOCOL_TYPE, ChoiceType::class, [
            'label' => 'Protocol Type',
            'required' => true,
            'choices' => $options[static::OPTION_PROTOCOL_TYPE_CHOICES],
            'constraints' => [new NotBlank()],
            'attr' => ['data-protocol-type-selector' => 'true'],
            'disabled' => $options['disabled'] ?? false,
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

    protected function addAllowIframeField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_ALLOW_IFRAME, CheckboxType::class, [
            'label' => 'Allow iFrame',
            'required' => false,
        ]);

        return $this;
    }

    protected function addRequestUrlField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_REQUEST_URL, TextType::class, [
            'label' => 'Request URL',
            'required' => false,
            'constraints' => [
                new Regex([
                    'pattern' => '~^' . str_replace('/', '\\/', PunchoutGatewayConfig::OCI_URL_PREFIX) . PunchoutGatewayConfig::OCI_URL_SLUG . '$~',
                    'message' => 'Enter an absolute URL, that starts with ' . PunchoutGatewayConfig::OCI_URL_PREFIX . ', only `_`, `-`, letters and numbers are allowed.',
                ]),
            ],
            'attr' => ['placeholder' => 'https://'],
            'help' => 'This is an absolute URL without a domain.',
        ]);

        return $this;
    }

    protected function addProcessorPluginClassField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_PROCESSOR_PLUGIN_CLASS, TextType::class, [
            'label' => 'Processor Plugin Class',
            'required' => false,
            'attr' => ['placeholder' => 'e.g. \\SprykerEco\\Zed\\PunchoutGateway\\Communication\\Plugin\\PunchoutGateway\\DefaultCxmlProcessorPlugin'],
        ]);

        return $this;
    }

    protected function addProtocolConfigurationFields(FormEvent $event): void
    {
        $data = $event->getData();
        $form = $event->getForm();

        $protocolType = null;

        if (is_array($data)) {
            $protocolType = $data[static::FIELD_PROTOCOL_TYPE] ?? null;
        }

        if (empty($data[PunchoutConnectionTransfer::ID_PUNCHOUT_CONNECTION])) {
            return;
        }

        if ($protocolType === PunchoutGatewayConfig::PROTOCOL_TYPE_CXML) {
            $form->add(static::FIELD_CXML_CONFIGURATION, PunchoutCxmlConfigurationFormType::class, [
                'label' => false,
                'required' => false,
            ]);

            $form->remove(static::FIELD_REQUEST_URL);
            $form->remove(static::FIELD_PROTOCOL_TYPE);

            $form->add(static::FIELD_PROTOCOL_TYPE, TextType::class, [
                'label' => 'Protocol Type',
                'disabled' => true,
            ]);
        }

        if ($protocolType === PunchoutGatewayConfig::PROTOCOL_TYPE_OCI) {
            $form->add(static::FIELD_OCI_CONFIGURATION, PunchoutOciConfigurationFormType::class, [
                'label' => false,
                'required' => false,
            ]);
        }
    }

    protected function validatePostSubmit(FormEvent $event): void
    {
        $form = $event->getForm();

        if (!$form->isSubmitted()) {
            return;
        }

        $data = $event->getData();

        $protocolType = null;

        if (is_array($data)) {
            $protocolType = $data[static::FIELD_PROTOCOL_TYPE] ?? null;
        }

        if ($protocolType === PunchoutGatewayConfig::PROTOCOL_TYPE_OCI) {
            if ($data[static::FIELD_IS_ACTIVE] && !$data[static::FIELD_REQUEST_URL]) {
                $form->get(static::FIELD_REQUEST_URL)->addError(new FormError('Provide a non empty URL before activating the connection.'));
            }
        }
    }
}
