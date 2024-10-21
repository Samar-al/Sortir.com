<?php

namespace App\DataFixtures;

use App\Entity\Base;
use App\Entity\City;
use App\Entity\Location;
use App\Entity\Participant;
use App\Entity\State;
use App\Entity\Trip;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker;

class AppFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;

    }

    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create("en_US");
        $populator = new \Faker\ORM\Doctrine\Populator($faker, $manager);

        // Create and persist City entities
        $cities = [];
        for ($i = 0; $i < 10; $i++) {
            $city = new City();
            $city->setName($faker->city);
            $city->setZipCode($faker->postcode);
            $manager->persist($city);
            $cities[] = $city;
        }

        // Create and persist Base entities
        $bases = [];
        for ($i = 0; $i < 5; $i++) {
            $base = new Base();
            $base->setName($faker->company);
            $manager->persist($base);
            $bases[] = $base;
        }

        // Generate and persist State entities
        $states = [
            'created' => new State(),
            'open' => new State(),
            'closed' => new State(),
            'in progress' => new State(),
            'passed' => new State(),
        ];
        foreach ($states as $label => $state) {
            $state->setLabel($label);
            $manager->persist($state);
            $states[$label] = $state; // Store for later use
        }

        // Flush to save Cities, Bases, and States
        $manager->flush();

        // Populate Locations
        $populator->addEntity(Location::class, 10, [
            'name' => function () use ($faker) {
                return $faker->streetName;
            },
            'latitude' => function () use ($faker) {
                return $faker->latitude;
            },
            'longitude' => function () use ($faker) {
                return $faker->longitude;
            },
            'streetNumber' => function () use ($faker) {
                return $faker->numberBetween(1, 500);
            },
            'streetName' => function () use ($faker) {
                return $faker->streetName;
            },
            'city' => function () use ($cities) {
                return $cities[array_rand($cities)];
            },
        ]);

        // Execute the populator for Locations
        $populator->execute();

        // Flush to ensure Locations are saved to the database
        $manager->flush();

        // Populate Participants
        $populator->addEntity(Participant::class, 50, [
            'firstname' => function () use ($faker) {
                return $faker->firstName;
            },
            'lastname' => function () use ($faker) {
                return $faker->lastName;
            },
            'mail' => function () use ($faker) {
                return $faker->email;
            },
            'roles' => ['ROLE_USER'],
            'phoneNumber' => function () use ($faker) {
                return $faker->phoneNumber;
            },
            'base' => function() use ($bases) {
                return $bases[array_rand($bases)]; 
            },
            'isAdmin' => false,
            'isActive' => true,
            'username' => function () use ($faker) {
                return $faker->unique()->userName;
            },
        ]);

        // Execute the populator for Participants
        $insertedItems = $populator->execute();

        // Manually hash the password for each Participant
        $participants = $insertedItems[Participant::class];

        foreach ($participants as $participant) {
            $hashedPassword = $this->passwordHasher->hashPassword($participant, 'password');
            $participant->setPassword($hashedPassword);
            $manager->persist($participant); // Re-persist with the hashed password
        }

        // Flush Participants to the database
        $manager->flush();

        // Retrieve all Locations and Participants from the database
        $locations = $manager->getRepository(Location::class)->findAll();
        if (empty($locations)) {
            throw new \Exception('No locations found. Make sure locations are created first.');
        }

        $participants = $manager->getRepository(Participant::class)->findAll();
        if (empty($participants)) {
            throw new \Exception('No participants found. Make sure participants are created first.');
        }

        // Create Trips
        for ($i = 0; $i < 20; $i++) {
            $trip = new Trip();
            $trip->setName($faker->sentence(3));

            $dateHourStart = \DateTimeImmutable::createFromMutable($faker->dateTimeBetween('+1 week', '+1 month'));
            $trip->setDateHourStart($dateHourStart);

            $duration = $faker->numberBetween(1, 90); // Duration in minutes
            $trip->setDuration($duration);

            $dateRegistrationLimit = \DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-1 week', '+2 weeks'));
            $trip->setDateRegistrationLimit($dateRegistrationLimit);

            $trip->setNumMaxRegistration($faker->numberBetween(5, 30));
            $trip->setTripDetails($faker->paragraph);
            
            // Assign a random Location
            $location = $locations[array_rand($locations)];
            $trip->setLocation($location);

            // Assign a random Organiser
            $organiser = $participants[array_rand($participants)];
            $trip->setOrganiser($organiser);

            $trip->setBase($bases[array_rand($bases)]);

            // Determine the state based on the current date and trip dates
            $currentDate = new \DateTimeImmutable();
            $dateHourEnd = $dateHourStart->modify("+{$duration} minutes");

            if ($currentDate > $dateHourEnd) {
                $trip->setState($states['passed']);
            } elseif ($currentDate >= $dateHourStart && $currentDate <= $dateHourEnd) {
                $trip->setState($states['in progress']);
            } elseif ($currentDate > $dateRegistrationLimit) {
                $trip->setState($states['closed']);
            } else {
                $trip->setState($states['created']);
            }

            $manager->persist($trip);
        }

        // Create an admin participant manually
        $admin = new Participant();
        $admin->setFirstname('Admin')
            ->setLastname('User')
            ->setMail('admin@example.com')
            ->setRoles(['ROLE_ADMIN'])
            ->setPhoneNumber($faker->phoneNumber)
            ->setBase($bases[array_rand($bases)])
            ->setAdmin(true)
            ->setActive(true)
            ->setUsername('admin');
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'adminpassword');
        $admin->setPassword($hashedPassword);
        $manager->persist($admin);

        // Flush all changes to the database
        $manager->flush();
    }
}
