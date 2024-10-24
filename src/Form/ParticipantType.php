<?php

namespace App\Form;

use App\Entity\Base;
use App\Entity\Participant;
use App\Entity\Trip;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ParticipantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, ['label' =>'Nom d\'utilisateur'])
            ->add('firstname', TextType::class, ['label' =>'Prénom'])
            ->add('lastname', TextType::class, ['label' =>'Nom'])
            ->add('phoneNumber', TextType::class, [
                'label'=>'Numéro de téléphone',
                'required' =>false
            ])
            ->add('mail', EmailType::class, ['label' =>'Adresse mail']);

        if ($options['is_edit']=== true) {
            $builder
                ->add('currentPassword', PasswordType::class, [
                    "mapped" => false,
                    "label" => "Mot de passe actuel",
                    "required" => true,
                ])
                ->add('plainPassword', PasswordType::class, [
                    "mapped" => false,
                    "label" => "Nouveau mot de passe",
                    "required" => false,
                ])
                ->add('confirmPassword', PasswordType::class, [
                    "mapped" => false,
                    "label" => "Confirmez nouveau mot de passe",
                    "required" => false,
                ]);

        } else {
            $builder
                ->add('plainPassword', PasswordType::class, [
                    "mapped" => false,
                    "label" => "Mot de passe",
                    "required" => true,
                ])
                ->add('confirmPassword', PasswordType::class, [
                    "mapped" => false,
                    "label" => "Confirmez mot de passe",
                    "required" => true,
                ])
            ;
        }

        $builder
            ->add('base', EntityType::class, [
                'label' => 'Site de rattachement',
                'class' => Base::class,
                'choice_label' => 'name',
            ])
            ->add('photo',FileType::class,[
                'label'=> "Ma photo (image* jpg,png...)",
                'mapped' => false,
                'required' =>false,
                'constraints'=> [
                    new File([
                        'maxSize'=> '5M',
                        'mimeTypes'=>[
                            "image/*",
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file'
                    ])
                ],
                'attr'=>[
                    'accept'=>'image/*'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
            'is_edit' => false
        ]);
    }
}
