<?php
// src/Controller/TestController.php
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


class TestController extends AbstractController
{
    #[Route('/test', name: 'app_test')]
    public function index(): Response
    {
        return $this->render('base.html.twig');
    }

    #[Route('/sportspaces', name: 'sportspaces')]
    public function showSportSpaces(SportSpaceRepository $repo, Request $request): Response
    {
        // For AJAX requests
        if ($request->isXmlHttpRequest()) {
            $search = $request->query->get('search');
            $type = $request->query->get('type');
            $sort = $request->query->get('sort');
            $availability = $request->query->get('availability');
    
            $sportSpaces = $repo->findWithFilters($search, $type, $sort, $availability);
    
            return $this->render('sportspace/_table.html.twig', [
                'sportSpaces' => $sportSpaces
            ]);
        }
    
        // Initial page load
        $types = $repo->findAllTypes();
        $initialData = $repo->findWithFilters(null, 'all', 'name_asc', 'all');
        
        return $this->render('sportspace/afficheSportSpace.html.twig', [
            'sportSpaces' => $initialData,
            'sportTypes' => $types
        ]);
    }
    
    #[Route('/sportspaces/types', name: 'sportspaces_types')]
    public function getSportTypes(SportSpaceRepository $repo): JsonResponse
    {
        $types = $repo->findAllTypes();
        return $this->json($types);
    }

    #[Route('/reservations', name: 'reservations')]
    public function showReservations(ReservationRepository $repo, Request $request): Response
    {
        $search = trim($request->query->get('search', ''));
        $dateFrom = $request->query->get('dateFrom');
        $dateTo = $request->query->get('dateTo');
        $sort = $request->query->get('sort', 'date_asc');
    
        if ($request->isXmlHttpRequest()) {
            $reservations = $repo->findWithFilters($search, $dateFrom, $dateTo, $sort);
            return $this->render('reservation/_table.html.twig', [
                'reservations' => $reservations
            ]);
        }
    
        // Initial load - empty search to get all records
        $reservations = $repo->findWithFilters('', null, null, 'date_asc');
        
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
    #[Route('/addReservation', name: 'addReservation')]
    public function addReservation(EntityManagerInterface $em, Request $req): Response
    {
        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($reservation);
            $em->flush();
            return $this->redirectToRoute('reservations');
        }

        return $this->render('reservation/ajoutReservation.html.twig', [
            'form' => $form->createView()
        ]);
    }
    #[Route('/deleteReservation/{id}', name: 'delete_reservation')]
    public function deleteReservation($id, ReservationRepository $repo): Response
    {
        $reservation = $repo->find($id);
        if ($reservation) {
            $repo->remove($reservation);
            $this->addFlash('success', 'Reservation deleted successfully!');
        } else {
            $this->addFlash('error', 'Reservation not found!');
        }
        return $this->redirectToRoute('reservations');
    }
    
    #[Route('/updateReservation/{id}', name: 'update_reservation')]
public function editReservation(EntityManagerInterface $em, Request $req, $id, ReservationRepository $rep): Response
{
    $reservation = $rep->find($id);
    if (!$reservation) {
        $this->addFlash('error', 'Reservation not found!');
        return $this->redirectToRoute('reservations');
    }

    $form = $this->createForm(ReservationType::class, $reservation);
    $form->handleRequest($req);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->flush();
        $this->addFlash('success', 'Reservation updated successfully!');
        return $this->redirectToRoute('reservations');
    }

    return $this->render('reservation/updateReservation.html.twig', [
        'f' => $form->createView(),
        'reservation' => $reservation
    ]);
}
}