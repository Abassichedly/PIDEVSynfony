<?php
namespace App\Form;

use App\Entity\SportSpace;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class SportSpaceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('location', TextType::class, [
                'label' => 'Location',
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('email', EmailType::class, [  // Use EmailType for email validation
                'label' => 'Email',
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('phone', IntegerType::class, [  // Use IntegerType for phone
                'label' => 'Phone',
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Football' => 'football',
                    'Basketball' => 'basketball',
                    'Pilates' => 'pilates',
                    'Handball' => 'handball',
                    'Swimming' => 'swimming',
                    'Yoga' => 'yoga',
                    'Tennis' => 'tennis',
                    'Box' => 'box',
                    'Hockey' => 'hockey',
                    'Gym' => 'gym',
                ],
                'attr' => ['class' => 'form-select'],
                'label' => 'Sport Type',
                'required' => true,
            ])
            ->add('availability', ChoiceType::class, [
                'label' => 'Availability',
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'expanded' => true,  // Radio buttons
                'multiple' => false,
                'label_attr' => ['class' => 'd-block mb-2'],
                'attr' => ['class' => 'radio-group'],
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SportSpace::class,
        ]);
    }
}