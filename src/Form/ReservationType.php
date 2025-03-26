<?php
namespace App\Form;

use App\Entity\Reservation;
use App\Entity\SportSpace;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\CallbackTransformer;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Generate time choices (8:00, 8:30, ..., 23:30)
        $timeChoices = [];
        for ($h = 8; $h < 24; $h++) {
            foreach (['00', '30'] as $m) {
                $time = sprintf('%02d:%s', $h, $m);
                $timeChoices[$time] = $time;
            }
        }

        $builder
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'yyyy-MM-dd',
                'attr' => [
                    'class' => 'form-control datepicker',
                    'autocomplete' => 'off'
                ]
            ])
            ->add('duration', ChoiceType::class, [
                'choices' => [
                    '30 minutes' => 0.5,
                    '1 hour' => 1,
                    '1.5 hours' => 1.5,
                    '2 hours' => 2,
                    '2.5 hours' => 2.5,
                    '3 hours' => 3
                ],
                'attr' => ['class' => 'form-select mb-3'],
                'label' => 'Duration',
                'label_attr' => ['class' => 'form-label']
            ])
            ->add('time', ChoiceType::class, [
                'choices' => $timeChoices,
                'attr' => [
                    'class' => 'form-control timepicker',
                    'autocomplete' => 'off'
                ],
                'label_attr' => ['class' => 'form-label']
            ])
            ->add('sportSpace', EntityType::class, [
                'class' => SportSpace::class,
                'choice_label' => 'name',
                'attr' => ['class' => 'form-select'],
                'label_attr' => ['class' => 'form-label']
            ]);

        // Add transformer for time field
        $builder->get('time')->addModelTransformer(new CallbackTransformer(
            function ($timeAsObject) {
                return $timeAsObject ? $timeAsObject->format('H:i') : null;
            },
            function ($timeAsString) {
                return $timeAsString ? \DateTime::createFromFormat('H:i', $timeAsString) : null;
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
            'available_spaces' => []
        ]);
    }
}