<?php

namespace App\DataFixtures;

use App\Entity\Ad;
use App\Entity\Booking;
use App\Entity\Comment;
use App\Entity\Image;
use App\Entity\Role;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Factory::create('fr_FR');

        $adminRole = new Role();
        $adminRole->setTitle('ROLE_ADMIN');
        $manager->persist($adminRole);

        $adminUser = new User();
        $adminUser->setFirstName('Mickael')
                  ->setLastName('ANDREO')
                  ->setEmail("mickaelandreo@icloud.com")
                  ->setHash($this->encoder->encodePassword($adminUser, 'password'))
                  ->setAvatar('https://media-exp1.licdn.com/dms/image/C4D03AQFdIVeiAXdU3Q/profile-displayphoto-shrink_200_200/0?e=1591833600&v=beta&t=PMXyyZMGqX5kMiJth5acpLoijwJ6mOtioB7iEWRPJ4g')
                  ->setIntroduction($faker->sentence())
                  ->setDescription('<p>'.join('</p><p>', $faker->paragraphs(3)).'</p>')
                  ->addUserRole($adminRole)
        ;

        $manager->persist($adminUser);

        // Gestion des utilisateurs
        $users = [];
        $genres = ['male', 'female'];

        for ($i=1; $i <= 10 ; $i++) { 
            $user = new User();

            $genre = $faker->randomElement($genres);

            $picture = "https://randomuser.me/api/portraits";
            $pictureId = $faker->numberBetween(1, 99).'.jpg';

            $picture .= ($genre == 'male' ? '/men' : '/women') . '/'.$pictureId;

            $hash = $this->encoder->encodePassword($user, 'password');

            $user->setFirstName($faker->firstName($genre))
                 ->setLastName($faker->lastName($genre))
                 ->setEmail($faker->email)
                 ->setIntroduction($faker->sentence())
                 ->setDescription('<p>'.join('</p><p>', $faker->paragraphs(3)).'</p>')
                 ->setHash($hash)
                 ->setAvatar($picture)
            ;

            $manager->persist($user);

            $users[] = $user;
        }

        // Gestion des annonces
        for ($i=1; $i < 30; $i++) { 
            $ad = new Ad();

            $title          = $faker->sentence();
            $introduction   = $faker->paragraph(2);
            $content        = '<p>'.join('</p><p>', $faker->paragraphs(5)).'</p>';
            $coverImageUrl  = "https://picsum.photos/1000/350?random=".mt_rand(1,999);

            $user = $users[mt_rand(0, count($users) - 1)];

            $ad->setTitle($title)
               ->setCoverImage($coverImageUrl)
               ->setIntroduction($introduction)
               ->setContent($content)
               ->setPrice(mt_rand(40, 200))
               ->setRooms(mt_rand(1, 4))
               ->setAuthor($user)
            ;

            // Gestion des images des annonces
            for ($j=1; $j <= mt_rand(2, 5); $j++) { 
                $image = new Image();

                $imageUrl = "https://picsum.photos/640/480?random=".mt_rand(1,999);

                $image->setUrl($imageUrl)
                      ->setCaption($faker->sentence())
                      ->setAd($ad)
                ;

                $manager->persist($image);
            }

            // Gestion des réservations
            for ($j=1; $j < mt_rand(0, 10); $j++) { 
                $booking = new Booking();

                $createdAt  = $faker->dateTimeBetween('-6 months');
                $startDate  = $faker->dateTimeBetween('-3 months');
                $duration   = mt_rand(3, 10);
                $endDate    = (clone $startDate)->modify("+$duration days");
                $amount     = $ad->getPrice() * $duration;
                $booker     = $users[mt_rand(0, count($users) - 1)];
                $comment    = $faker->paragraph();

                $booking->setBooker($booker)
                        ->setStartDate($startDate)
                        ->setEndDate($endDate)
                        ->setCreatedAt($createdAt)
                        ->setAmount($amount)
                        ->setAd($ad)
                        ->setComment($comment)
                ;

                $manager->persist($booking);

                // Gestion des commentaires
                if (mt_rand(0,1)) {
                    $comment = new Comment();
                    $comment->setContent($faker->paragraph())
                            ->setRating(mt_rand(1,5))
                            ->setAuthor($booker)
                            ->setAd($ad)
                    ;

                    $manager->persist($comment);
                }
            }
            
            $manager->persist($ad);
        }
        $manager->flush();
    }
}
