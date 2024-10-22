<?php

namespace App\Form;

use App\Entity\City;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', HiddenType::class, [
                'label' => 'Ville',
                'attr' => [
                    'data-custom' => 'city_name',
                ]
            ])
            ->add('ZipCode', TextType::class, [
                'label' => 'Code postal',
                'required' => false,
                'attr' => [
                    'readonly' => true,
                    'data-custom' => 'city_ZipCode',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => City::class,
        ]);
    }

}
