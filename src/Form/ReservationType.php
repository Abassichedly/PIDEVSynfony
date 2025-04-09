<?php
namespace App\Form;

use App\Entity\Reservation;
use App\Entity\SportSpace;
use App\Repository\SportSpaceRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\CallbackTransformer;

class ReservationType extends AbstractType
{
    private $sportSpaceRepository;

    public function __construct(SportSpaceRepository $sportSpaceRepository)
    {
        $this->sportSpaceRepository = $sportSpaceRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
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
            ->add('time', ChoiceType::class, [
                'choices' => $timeChoices,
                'attr' => [
                    'class' => 'form-control timepicker',
                    'autocomplete' => 'off'
                ],
                'label_attr' => ['class' => 'form-label']
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
                'attr' => ['class' => 'form-select'],
                'label_attr' => ['class' => 'form-label']
            ])
            ->add('sportSpace', EntityType::class, [
                'class' => SportSpace::class,
                'choice_label' => 'name',
                'attr' => ['class' => 'form-select'],
                'label_attr' => ['class' => 'form-label'],
                'choice_attr' => function(SportSpace $sportSpace) {
                    return [
                        'data-address' => $sportSpace->getLocation(),
                        'data-name' => $sportSpace->getName()
                    ];
                },
                'query_builder' => function() {
                    return $this->sportSpaceRepository->createQueryBuilder('s')
                        ->orderBy('s.name', 'ASC');
                }
            ]);

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
        ]);
    }
}