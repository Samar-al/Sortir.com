<?php

namespace App\Command;

use App\Repository\TripRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-statuses',
    description: 'Add a short description for your command',
)]
class UpdateStatusesCommand extends Command
{
    protected static $defaultName = 'app:update-statuses';
    private $tripRepository;
    private $entityManager;

    public function __construct(TripRepository $tripRepository, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->tripRepository = $tripRepository;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
        ->setDescription('Update trip statuses based on date conditions')
        ->setHelp('This command updates trip statuses based on date conditions (passed, in progress, closed, and archived).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

         // Set the correct timezone for the current time
        $timezone = new \DateTimeZone('Europe/Paris'); // Adjust this to your timezone
        $now = new \DateTime('now', $timezone);

        // Get trips that need status updates
        $trips = $this->tripRepository->findTripsNeedingStatusUpdate();

        foreach ($trips as $trip) {
            $dateHourStart = $trip->getDateHourStart();
            $durationInHours = $trip->getDuration(); // Duration is stored as hours
            $dateRegistrationLimit = $trip->getDateRegistrationLimit();

            // Set timezone for $dateHourStart and $now to ensure they match
            $dateHourStart->setTimezone($timezone);
            $dateRegistrationLimit->setTimezone($timezone);


            // Calculate the end of the trip by adding the duration to `dateHourStart`
            $dateHourEnd = (clone $dateHourStart)->modify("+{$durationInHours} hours");


            // Ensure the comparison is done only on 'Y-m-d H:i' precision
            $formattedStart = $dateHourStart->format('Y-m-d H:i');
            $formattedNow = $now->format('Y-m-d H:i');
            $formattedEnd = $dateHourEnd->format('Y-m-d H:i');

            // 1. If 1 month has passed since `dateHourStart`, archive the trip
            $oneMonthAfterStart = (clone $dateHourStart)->modify('+1 month');
            if ($oneMonthAfterStart <= $now) {
                $trip->setArchived(true);
               
            }

             // 2. If current time is between `dateHourStart` and `dateHourEnd`, set the state to 'activity in progress'
            if ($formattedStart <= $formattedNow && $formattedNow < $formattedEnd) {
                $inProgressState = $this->tripRepository->findStateByLabel('activity in progress');
                if ($inProgressState) {
                    $trip->setState($inProgressState);
                    
                } 
            } 


            // 3. If `dateHourEnd` is in the past, set the state to 'passed'
            if ($dateHourEnd < $now) {
                $passedState = $this->tripRepository->findStateByLabel('passed');
                if ($passedState) {
                    $trip->setState($passedState);
                    
                }
            }

            // 4. If `dateRegistrationLimit` is reached and the state is not 'closed'
            if ($dateRegistrationLimit <= $now && $trip->getState()->getLabel() !== 'closed') {
                $closedState = $this->tripRepository->findStateByLabel('closed');
                if ($closedState) {
                    $trip->setState($closedState);
                    
                }
            }
        }

        // Persist the changes to the database
        $this->entityManager->flush();

        $io->success('Trip statuses updated successfully.');

        return Command::SUCCESS;
    }
}
