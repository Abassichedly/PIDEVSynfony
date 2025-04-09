<?php

namespace App\Controller;

use App\Repository\SportSpaceRepository;
use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\SportSpaceType;
use App\Entity\SportSpace;
use App\Form\ReservationType;
use App\Entity\Reservation;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Dompdf\Dompdf;
use Dompdf\Options;

final class RESnSSPController extends AbstractController
{
    #[Route('/test', name: 'test')]
    public function index(): Response
    {
        return $this->render('base.html.twig',[]);
    }

    #[Route('/sportspaces', name: 'sportspaces')]
    public function showSportSpaces(SportSpaceRepository $repo, Request $request): Response
    {
        if ($request->isXmlHttpRequest()) {
            $search = $request->query->get('search');
            $type = $request->query->get('type');
            $sort = $request->query->get('sort');
            $availability = $request->query->get('availability');
            $charts = $request->query->get('charts');
    
            $sportSpaces = $repo->findWithFilters($search, $type, $sort, $availability);
    
            if ($charts) {
                $typeData = [];
                $statusData = [];
                
                $typeCounts = [];
                foreach ($sportSpaces as $space) {
                    $type = $space->getType();
                    $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
                }
                foreach ($typeCounts as $type => $count) {
                    $typeData[] = [$type, $count];
                }
                
                $statusCounts = [
                    'Available' => 0,
                    'Unavailable' => 0
                ];
                foreach ($sportSpaces as $space) {
                    $status = $space->isAvailability() ? 'Available' : 'Unavailable';
                    $statusCounts[$status]++;
                }
                foreach ($statusCounts as $status => $count) {
                    $statusData[] = [$status, $count];
                }
                
                return $this->json([
                    'typeData' => $typeData,
                    'statusData' => $statusData
                ]);
            } else {
                return $this->render('sportspace/_table.html.twig', [
                    'sportSpaces' => $sportSpaces
                ]);
            }
        }
        
        $types = $repo->findAllTypes();
        $initialData = $repo->findWithFilters(null, 'all', 'name_asc', 'all');
        
        return $this->render('sportspace/afficheSportSpace.html.twig', [
            'sportSpaces' => $initialData,
            'sportTypes' => $types
        ]);
    }

    #[Route('/reservations', name: 'reservations')]
    public function showReservations(ReservationRepository $repo, Request $request): Response
    {
        $search = trim($request->query->get('search', ''));
        $dateFrom = $request->query->get('dateFrom');
        $dateTo = $request->query->get('dateTo');
        $sort = $request->query->get('sort', 'date_asc');
        $duration = $request->query->get('duration');
        $charts = $request->query->get('charts');
    
        $reservations = $repo->findWithFilters($search, $dateFrom, $dateTo, $sort, $duration);
    
        if ($request->isXmlHttpRequest()) {
            if ($charts) {
                $durationData = [];
                $spaceData = [];
                
                $durationCounts = [];
                foreach ($reservations as $reservation) {
                    $duration = $reservation->getDuration() . 'h';
                    $durationCounts[$duration] = ($durationCounts[$duration] ?? 0) + 1;
                }
                foreach ($durationCounts as $duration => $count) {
                    $durationData[] = [$duration, $count];
                }
                
                $spaceCounts = [];
                foreach ($reservations as $reservation) {
                    $spaceName = $reservation->getSportSpace()->getName();
                    $spaceCounts[$spaceName] = ($spaceCounts[$spaceName] ?? 0) + 1;
                }
                foreach ($spaceCounts as $space => $count) {
                    $spaceData[] = [$space, $count];
                }
                
                return $this->json([
                    'durationData' => $durationData,
                    'spaceData' => $spaceData
                ]);
            } else {
                $html = $this->renderView('reservation/_table.html.twig', [
                    'reservations' => $reservations
                ]);
                return new Response($html);
            }
        }
        
        return $this->render('reservation/afficheReservation.html.twig', [
            'reservations' => $reservations
        ]);
    }
    #[Route('/addSportSpaces', name: 'addSportSpaces')]
    public function addSSP(EntityManagerInterface $em, Request $req, MailerInterface $mailer, \Psr\Log\LoggerInterface $logger, \Symfony\Component\Mailer\Transport\TransportInterface $transport): Response
    {
        $sportspace = new SportSpace();
        $form = $this->createForm(SportSpaceType::class, $sportspace);
        $form->handleRequest($req);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Persist the sport space to the database
            $em->persist($sportspace);
            $em->flush();
    
            // Get the current date for the email
            $date = (new \DateTime())->format('Y-m-d');
    
            // Send a confirmation email to the sport space's email address
            try {
                // Validate the recipient email address
                $recipientEmail = $sportspace->getEmail();
                if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                    throw new \Exception('Invalid recipient email address: ' . ($recipientEmail ?: 'empty'));
                }
    
                $email = (new Email())
                    ->from('mohamedchedhly.abassi@etudiant-fsegt.utm.tn')
                    ->to($recipientEmail)
                    ->subject('Confirmation of Collaboration with SPORTIFY')
                    ->html("Dear " . $sportspace->getName() . " Team,<br><br>" .
                           "I hope this email finds you well.<br><br>" .
                           "We are writing to formally confirm our collaboration with your esteemed sports facility on " . $date . ". " .
                           "As an organization committed to excellence, we highly appreciate the opportunity to work with a venue as distinguished as yours, " .
                           "known for its outstanding infrastructure and commitment to sports development.<br><br>" .
                           "Through this partnership, we aim to provide an optimal experience for all participants, benefiting from the high-quality facilities " .
                           "and professional environment that your establishment offers. We truly believe that this collaboration will be mutually enriching " .
                           "and contribute to the promotion of sports and well-being within our community.<br><br>" .
                           "We kindly ask you to confirm your availability on the mentioned date and share any specific requirements or conditions that need to be considered " .
                           "for a seamless collaboration. Should you require any further details or have any inquiries, please do not hesitate to reach out.<br><br>" .
                           "We greatly appreciate your time and consideration and look forward to working together to make this initiative a success.<br><br>" .
                           "Best regards,<br>" .
                           "Head of Department<br>" .
                           "SPORTIFY<br>" .
                           "sportify@gmail.com<br>" .
                           "55228866");
    
                // Log the email details before sending
                $logger->info('Attempting to send email', [
                    'from' => 'mohamedchedhly.abassi@etudiant-fsegt.utm.tn',
                    'to' => $recipientEmail,
                    'subject' => 'Confirmation of Collaboration with SPORTIFY',
                ]);
    
                // Send the email using the transport directly
                $transport->send($email);
    
                // Log success
                $logger->info('Email sent successfully to ' . $recipientEmail);
    
                // Add a flash message to confirm the addition and email sending
                $this->addFlash('success', 'Sport space added successfully, and a confirmation email has been sent to ' . $recipientEmail . '.');
            } catch (\Exception $e) {
                // Log the error with more context
                $logger->error('Failed to send email: ' . $e->getMessage(), [
                    'exception' => $e,
                    'email' => $sportspace->getEmail() ?? 'not set',
                    'trace' => $e->getTraceAsString(),
                ]);
    
                // Add a flash message to notify the user of the email failure
                $this->addFlash('error', 'Sport space added successfully, but failed to send confirmation email: ' . $e->getMessage());
            }
    
            // Redirect to the sport spaces list
            return $this->redirectToRoute('sportspaces');
        }
    
        return $this->renderForm('sportspace/ajoutSportSpace.html.twig', ['f' => $form]);
    }


    
    #[Route('/deleteSportSpace/{id}', name: 'delete_sportspace')]
    public function deleteSportSpace($id, SportSpaceRepository $repo): Response
    {
        $sportspace = $repo->findById($id);
         if ($sportspace) {
            $repo->remove($sportspace);
            $this->addFlash('success', 'Sport space deleted successfully!');
         } else {
             $this->addFlash('error', 'Sport space not found!');
            }
             return $this->redirectToRoute('sportspaces');
            }
   #[Route('/updateSportSpace/{id}', name: 'updateSportSpace')]
    public function editSSP(EntityManagerInterface $em, Request $req, $id, SportSpaceRepository $rep): Response
    {
        $sportSpace = $rep->find($id);
        if (!$sportSpace) {
            $this->addFlash('error', 'Sport space not found!');
            return $this->redirectToRoute('sportspaces');
        }

        $form = $this->createForm(SportSpaceType::class, $sportSpace);
        $form->add('edit', SubmitType::class, ['label' => 'Update Sport Space']);
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Sport space updated successfully!');
            return $this->redirectToRoute('sportspaces');
        }

        return $this->render('sportspace/updateSportSpace.html.twig', [
            'f' => $form // Pass the entity for use in the template
        ]);
    }
    private function sendReservationListEmail(
        SportSpace $sportSpace,
        array $reservations,
        MailerInterface $mailer,
        \Psr\Log\LoggerInterface $logger,
        \Symfony\Component\Mailer\Transport\TransportInterface $transport
    ): void {
        // Generate PDF
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($pdfOptions);

        $html = $this->renderView('reservation/reservations_pdf.html.twig', [
            'sportSpace' => $sportSpace,
            'reservations' => $reservations,
            'date' => new \DateTime()
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfOutput = $dompdf->output();

        // Send email with PDF attachment
        try {
            $recipientEmail = $sportSpace->getEmail();
            if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Invalid recipient email address: ' . ($recipientEmail ?: 'empty'));
            }

            $email = (new Email())
                ->from('mohamedchedhly.abassi@etudiant-fsegt.utm.tn')
                ->to($recipientEmail)
                ->subject('Updated Reservation List for ' . $sportSpace->getName())
                ->html("Dear " . $sportSpace->getName() . " Team,<br><br>" .
                       "Please find attached the updated list of reservations for your sport space as of " . date('Y-m-d') . ".<br><br>" .
                       "Best regards,<br>" .
                       "SPORTIFY Team")
                ->attach($pdfOutput, 'reservations_' . date('Y-m-d') . '.pdf', 'application/pdf');

            $logger->info('Attempting to send reservation list email', [
                'to' => $recipientEmail,
                'sportSpace' => $sportSpace->getName()
            ]);

            $transport->send($email);

            $logger->info('Reservation list email sent successfully to ' . $recipientEmail);
            $this->addFlash('success', 'Email with updated reservation list sent to ' . $recipientEmail);
        } catch (\Exception $e) {
            $logger->error('Failed to send reservation list email: ' . $e->getMessage(), [
                'exception' => $e,
                'email' => $sportSpace->getEmail() ?? 'not set'
            ]);
            $this->addFlash('error', 'Operation completed but failed to send email: ' . $e->getMessage());
        }
    }

    #[Route('/addReservation', name: 'addReservation')]
    public function addReservation(
        EntityManagerInterface $em, 
        Request $req, 
        ReservationRepository $reservationRepo,
        MailerInterface $mailer,
        \Psr\Log\LoggerInterface $logger,
        \Symfony\Component\Mailer\Transport\TransportInterface $transport
    ): Response
    {
        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            $sportSpace = $reservation->getSportSpace();
            
            // Persist the new reservation
            $em->persist($reservation);
            $em->flush();

            // Get updated reservations and send email
            $reservations = $reservationRepo->findReservationsBySportSpaceEntity($sportSpace);
            $this->sendReservationListEmail($sportSpace, $reservations, $mailer, $logger, $transport);

            $this->addFlash('success', 'Reservation created successfully!');
            return $this->redirectToRoute('reservations');
        }

        return $this->render('reservation/ajoutReservation.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/deleteReservation/{id}', name: 'delete_reservation')]
    public function deleteReservation(
        $id, 
        ReservationRepository $repo,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        \Psr\Log\LoggerInterface $logger,
        \Symfony\Component\Mailer\Transport\TransportInterface $transport
    ): Response
    {
        $reservation = $repo->find($id);
        if ($reservation) {
            $sportSpace = $reservation->getSportSpace();
            $repo->remove($reservation);
            
            // Flush changes to database
            $em->flush();

            // Get updated reservations and send email
            $reservations = $repo->findReservationsBySportSpaceEntity($sportSpace);
            $this->sendReservationListEmail($sportSpace, $reservations, $mailer, $logger, $transport);

            $this->addFlash('success', 'Reservation deleted successfully!');
        } else {
            $this->addFlash('error', 'Reservation not found!');
        }
        return $this->redirectToRoute('reservations');
    }
    
    #[Route('/updateReservation/{id}', name: 'update_reservation')]
    public function editReservation(
        EntityManagerInterface $em, 
        Request $req, 
        $id, 
        ReservationRepository $rep,
        MailerInterface $mailer,
        \Psr\Log\LoggerInterface $logger,
        \Symfony\Component\Mailer\Transport\TransportInterface $transport
    ): Response
    {
        $reservation = $rep->find($id);
        if (!$reservation) {
            $this->addFlash('error', 'Reservation not found!');
            return $this->redirectToRoute('reservations');
        }

        $sportSpace = $reservation->getSportSpace(); // Store sportSpace before potential changes
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            // Get updated reservations and send email
            $updatedSportSpace = $reservation->getSportSpace(); // Check if sportSpace changed
            $reservations = $rep->findReservationsBySportSpaceEntity($updatedSportSpace);
            $this->sendReservationListEmail($updatedSportSpace, $reservations, $mailer, $logger, $transport);

            // If sportSpace changed, send email to the old sportSpace too
            if ($sportSpace !== $updatedSportSpace) {
                $oldReservations = $rep->findReservationsBySportSpaceEntity($sportSpace);
                $this->sendReservationListEmail($sportSpace, $oldReservations, $mailer, $logger, $transport);
            }

            $this->addFlash('success', 'Reservation updated successfully!');
            return $this->redirectToRoute('reservations');
        }

        return $this->render('reservation/updateReservation.html.twig', [
            'f' => $form->createView(),
            'reservation' => $reservation
        ]);
    }
}