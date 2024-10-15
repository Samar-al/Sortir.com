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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TripType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('dateHourStart', null, [
                'widget' => 'single_text',
            ])
            ->add('duration')
            ->add('dateRegistrationLimit', null, [
                'widget' => 'single_text',
            ])
            ->add('numMaxRegistration')
            ->add('tripDetails')
            ->add('location', EntityType::class, [
                'class' => Location::class,
                'choice_label' => 'name',
            ])
            ->add('base', EntityType::class, [
                'class' => Base::class,
                'choice_label' => 'name',
                'label' => 'Site organisateur',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('location', EntityType::class, [
                'class' => Location::class,
                'choice_label' => 'name',
                'label' => 'Lieu organisateur',
                'attr' => ['class' => 'form-control'],
            ])
        ;

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trip::class,
        ]);
    }
}
