<?php

namespace App\Form;

use App\Validator\ShortCode;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

class ShortUrlType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('url', UrlType::class, [
                'label' => 'URL',
                'help' => 'Please enter a valid URL (starting with http://).',
                'attr' => [
                    'placeholder' => 'Enter the URL to shorten',
                    'class' => 'form-control',
                ],
                'required' => true,
                'default_protocol' => 'https',
                'constraints' => [
                    new NotBlank(),
                    new Url(requireTld: true),
                ],
            ])
            ->add('shortCode', null, [
                'label' => 'Short Code',
                'help' => 'You can optionnaly customize the short code to use. Leave this field empty to have a random short code be generated',
                'attr' => [
                    'placeholder' => 'Enter a custom short code (optional)',
                    'class' => 'form-control',
                ],
                'required' => false,
                'constraints' => [
                    new ShortCode(),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Shorten this URL',
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
