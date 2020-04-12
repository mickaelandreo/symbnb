<?php

namespace App\Controller;

use App\Entity\Ad;
use App\Entity\Booking;
use App\Entity\Comment;
use App\Form\BookingType;
use App\Form\CommentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BookingController extends AbstractController
{
    /**
     * Permet d'ajouter une réservation sur une annonce
     * 
     * @Route("/ads/{slug}/book", name="booking_create")
     * 
     * @IsGranted("ROLE_USER")
     * 
     * @param Ad $ad
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */
    public function book(Ad $ad, Request $request, EntityManagerInterface $manager)
    {
        $booking = new Booking();

        $form = $this->createForm(BookingType::class, $booking);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();

            $booking->setBooker($user)
                    ->setAd($ad)
            ;

            // Si les dates ne sont pas disponibles, message d'erreur
            if (!$booking->isBookableDates()) {
                $this->addFlash(
                    'warning',
                    "Les dates que vous avez choisies sont indisponibles"
                );
            } else {
                // Sinon enregistrement et redirection
                
                $manager->persist($booking);
                $manager->flush();

                $this->addFlash(
                    'success',
                    "Votre réservation a bien été prise en compte"
                );
    
                return $this->redirectToRoute('booking_show', [
                    'id'        => $booking->getId()
                ]);
            }
        }

        return $this->render('booking/book.html.twig', [
            'ad' => $ad,
            'form' => $form->createView()
        ]);
    }

    /**
     * Permet d'afficher la page d'une réservation
     * 
     * @Route("/booking/{id}", name="booking_show")
     *
     * @param Booking $booking
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */
    public function show(Booking $booking, Request $request, EntityManagerInterface $manager)
    {
        $comment = new Comment();

        $form = $this->createForm(CommentType::class, $comment);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $comment->setAd($booking->getAd())
                    ->setAuthor($this->getUser())
            ;

            $manager->persist($comment);
            $manager->flush();

            $this->addFlash(
                'success',
                'Votre avis a bien été ajoutée à la réservation'
            );
        }

        return $this->render('booking/show.html.twig', [
            'booking' => $booking,
            'form'    => $form->createView()
        ]);
    }
}
