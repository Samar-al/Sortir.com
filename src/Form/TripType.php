<?php

namespace App\Form;

use App\Entity\Base;
use App\Entity\City;
use App\Entity\Location;
use App\Entity\Participant;
use App\Entity\State;
use App\Entity\Trip;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TripType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('name', TextType::class, [
            'label' => 'Nom de la sortie',
            'attr' => [
                'class' => 'form-control',
                'placeholder' => 'Entrez le nom de la sortie',
            ],
        ])
        ->add('dateHourStart', DateTimeType::class, [
            'widget' => 'single_text',
            'label' => 'Date et heure de début',
            'attr' => [
                'class' => 'form-control',
                'placeholder' => 'Sélectionnez la date et l\'heure de début',
            ],
        ])
        ->add('duration', IntegerType::class, [
            'label' => 'Durée (minutes)',
            'attr' => [
                'class' => 'form-control',
                'placeholder' => 'Entrez la durée en minutes',
            ],
        ])
        ->add('dateRegistrationLimit', DateTimeType::class, [
            'widget' => 'single_text',
            'label' => 'Date limite d\'inscription',
            'attr' => [
                'class' => 'form-control',
                'placeholder' => 'Sélectionnez la date limite d\'inscription',
            ],
        ])
        ->add('numMaxRegistration', IntegerType::class, [
            'label' => 'Nombre maximum de participants',
            'attr' => [
                'class' => 'form-control',
                'placeholder' => 'Entrez le nombre maximum de participants',
            ],
        ])
        ->add('tripDetails', TextareaType::class, [
            'label' => 'Détails de la sortie',
            'attr' => [
                'class' => 'form-control',
                'rows' => 5,
                'placeholder' => 'Entrez les détails de la sortie ici...',
            ],
        ])
        ->add('base', EntityType::class, [
            'class' => Base::class,
            'choice_label' => 'name',
            'label' => 'Site organisateur',
            'attr' => ['class' => 'form-control'],
            'placeholder' => 'Sélectionnez le site organisateur',
        ])
    
        ->add('location', EntityType::class, [
            'class' => Location::class,
            'choice_label' => 'name',
            'label' => 'Lieu',
            'attr' => ['class' => 'form-control'],
            'placeholder' => 'Sélectionnez le lieu organisateur',
        ]);
       
    }
    

    

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trip::class,
        ]);
    }
}
