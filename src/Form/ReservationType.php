<?php

namespace App\Form;

use App\Entity\ActiviteEcologique;
use App\Entity\Reservation;
use App\Repository\ActiviteEcologiqueRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationType extends AbstractType
{
    public function __construct(private readonly ActiviteEcologiqueRepository $activiteEcologiqueRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom complet',
            ])
            ->add('date_reservation', DateType::class, [
                'label' => 'Date de réservation (interne)',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('date_reservation_choice', TextType::class, [
                'label' => 'Date de réservation',
                'mapped' => false,
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
            ])
            ->add('nombre_personnes', IntegerType::class, [
                'label' => 'Nombre de personnes',
            ])
            ->add('activiteEcologique', EntityType::class, [
                'class' => ActiviteEcologique::class,
                'choice_label' => 'nom_activite',
                'label' => 'Activité écologique',
                'placeholder' => '-- Choisissez une activité --',
                'choice_attr' => static function (?ActiviteEcologique $activite): array {
                    if (!$activite || !$activite->getDate_activite()) {
                        return [];
                    }

                    return [
                        'data-activity-date' => $activite->getDate_activite()->format('Y-m-d'),
                    ];
                },
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();

            if (!is_array($data)) {
                return;
            }

            $selectedDate = isset($data['date_reservation_choice']) ? trim((string) $data['date_reservation_choice']) : '';
            $normalizedSelectedDate = self::normalizeDateInput($selectedDate);

            if ($normalizedSelectedDate !== null) {
                $data['date_reservation'] = $normalizedSelectedDate;
                $event->setData($data);
            }
        });

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event): void {
            $reservation = $event->getData();
            $form = $event->getForm();

            if (!$reservation instanceof Reservation) {
                return;
            }

            $existingDate = $reservation->getDate_reservation();
            if ($existingDate instanceof \DateTimeInterface) {
                $form->get('date_reservation_choice')->setData($existingDate->format('Y-m-d'));
            }
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $reservation = $event->getData();
            $form = $event->getForm();

            if (!$reservation instanceof Reservation) {
                return;
            }

            $selectedDate = $form->get('date_reservation_choice')->getData();
            $activite = $reservation->getActiviteEcologique();

            if (!$activite || !$activite->getDate_activite()) {
                return;
            }

            $allowedDates = $this->activiteEcologiqueRepository->findDatesForReservationByName((string) $activite->getNom_activite());
            if (count($allowedDates) === 0) {
                $allowedDates = [$activite->getDate_activite()->format('Y-m-d')];
            }

            if (!is_string($selectedDate) || $selectedDate === '') {
                $form->get('date_reservation_choice')->addError(new FormError('Veuillez choisir une date.'));
                return;
            }

            $normalizedSelectedDate = self::normalizeDateInput(trim($selectedDate));

            if ($normalizedSelectedDate === null) {
                $form->get('date_reservation_choice')->addError(new FormError('Format de date invalide. Utilisez YYYY-MM-DD ou JJ/MM/AAAA.'));
                return;
            }

            if (!in_array($normalizedSelectedDate, $allowedDates, true)) {
                $form->get('date_reservation_choice')->addError(new FormError('Veuillez choisir une date valide de l\'activité sélectionnée.'));
                return;
            }

            $date = \DateTime::createFromFormat('!Y-m-d', $normalizedSelectedDate);
            if (!$date) {
                $form->get('date_reservation_choice')->addError(new FormError('Format de date invalide.'));
                return;
            }

            $reservation->setDate_reservation($date);
        });
    }

    private static function normalizeDateInput(string $selectedDate): ?string
    {
        if ($selectedDate === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
            $parsedDate = \DateTimeImmutable::createFromFormat('!' . $format, $selectedDate);
            if ($parsedDate !== false && $parsedDate->format($format) === $selectedDate) {
                return $parsedDate->format('Y-m-d');
            }
        }

        return null;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}