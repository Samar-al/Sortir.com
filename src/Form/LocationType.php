<?php

namespace App\Form;

use App\Entity\City;
use App\Entity\Location;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class LocationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('name', TextType::class, [
            'label' => 'Nom du lieu',
            'attr' => ['class' => 'form-control'],
            'constraints' => [
                new NotBlank([
                    'message' => 'Le nom du lieu ne peut pas être vide.',
                ]),
            ],
        ])
        ->add('latitude', NumberType::class, [
            'label' => 'Latitude',
            'attr' => ['class' => 'form-control'],
            'scale' => 8,
            'html5' => true,  // Force it to render as <input type="number">
            'constraints' => [
                new Range([
                    'min' => -90,
                    'max' => 90,
                    'notInRangeMessage' => 'La latitude doit être comprise entre -90 et 90.',
                ]),
            ],
        ])
        ->add('longitude', NumberType::class, [
            'label' => 'Longitude',
            'attr' => ['class' => 'form-control'],
            'scale' => 8,
            'html5' => true,  // Force it to render as <input type="number">
            'constraints' => [
                new Range([
                    'min' => -180,
                    'max' => 180,
                    'notInRangeMessage' => 'La longitude doit être comprise entre -180 et 180.',
                ]),
            ],
        ])
        ->add('streetNumber', IntegerType::class, [
            'label' => 'Numéro de rue',
            'attr' => ['class' => 'form-control'],
            'constraints' => [
                new NotBlank([
                    'message' => 'Le numéro de rue ne peut pas être vide.',
                ]),
            ],
        ])
        ->add('streetName', TextType::class, [
            'label' => 'Nom de rue',
            'attr' => ['class' => 'form-control'],
            'constraints' => [
                new NotBlank([
                    'message' => 'Le nom de rue ne peut pas être vide.',
                ]),
            ],
        ])
        ->add('city', EntityType::class, [
            'class' => City::class,
            'choice_label' => 'name',
            'label' => 'Ville',
            'attr' => ['class' => 'form-select'],
            'placeholder' => 'Sélectionnez une ville',
            'constraints' => [
                new NotBlank([
                    'message' => 'La ville doit être sélectionnée.',
                ]),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Location::class,
        ]);
    }
}
