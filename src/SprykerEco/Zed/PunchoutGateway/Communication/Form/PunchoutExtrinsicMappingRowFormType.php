<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerEco\Zed\PunchoutGateway\Communication\Form;

use Spryker\Zed\Gui\Communication\Form\Type\AutosuggestType;
use Spryker\Zed\Kernel\Communication\Form\AbstractType;
use SprykerEco\Shared\PunchoutGateway\PunchoutGatewayConfig as SharedPunchoutGatewayConfig;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @method \SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface getRepository()
 * @method \SprykerEco\Zed\PunchoutGateway\Communication\PunchoutGatewayCommunicationFactory getFactory()
 * @method \SprykerEco\Zed\PunchoutGateway\PunchoutGatewayConfig getConfig()
 * @method \SprykerEco\Zed\PunchoutGateway\Business\PunchoutGatewayFacadeInterface getFacade()
 */
class PunchoutExtrinsicMappingRowFormType extends AbstractType
{
    public const string FIELD_EXTRINSIC_NAME = 'extrinsicName';

    public const string FIELD_SOURCE = 'source';

    public const string OPTION_SOURCE_SUGGESTIONS_URL = 'source_suggestions_url';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(static::FIELD_EXTRINSIC_NAME, TextType::class, [
                'label' => 'Extrinsic name',
                'required' => true,
                'attr' => ['placeholder' => 'e.g. ImageURL'],
                'constraints' => [
                    new NotBlank(),
                    new Regex(['pattern' => '/^[A-Za-z0-9_]+$/']),
                    new Callback(static function (mixed $value, ExecutionContextInterface $context): void {
                        if (in_array($value, SharedPunchoutGatewayConfig::EXTRINSIC_DENY_LIST, true)) {
                            $context->addViolation('Extrinsic name "%name%" is reserved for user identity.', ['%name%' => $value]);
                        }
                    }),
                ],
            ])
            ->add(static::FIELD_SOURCE, AutosuggestType::class, [
                'label' => 'Source expression',
                'required' => true,
                AutosuggestType::URL => $options[static::OPTION_SOURCE_SUGGESTIONS_URL],
                'attr' => [
                    'placeholder' => 'e.g. item.sku or "" to force an empty value',
                ],
                'constraints' => [
                    new NotBlank(),
                    new Regex([
                        'pattern' => '/^(?:[A-Za-z_][A-Za-z0-9_]*\.[^&]+|"[^"]*"|\'[^\']*\')(?:&(?:[A-Za-z_][A-Za-z0-9_]*\.[^&]+|"[^"]*"|\'[^\']*\'))*$/',
                        'message' => 'Must be a plugin expression (pluginKey.field), a quoted constant ("EA"), segments joined by & (item.sku&"_suffix"), or "" to force an empty value.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            static::OPTION_SOURCE_SUGGESTIONS_URL => '',
        ]);
    }
}
