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

        
        $timezone = new \DateTimeZone('Europe/Paris'); 
        $now = new \DateTimeImmutable('now', $timezone); 
        
        $trips = $this->tripRepository->findTripsNeedingStatusUpdate();

        foreach ($trips as $trip) {
            $dateHourStart = $trip->getDateHourStart();
            $dateHourStart = new \DateTimeImmutable($dateHourStart->format('Y-m-d H:i:s'), $timezone);
            $durationInHours = $trip->getDuration(); // Duration is stored as hours
            $dateRegistrationLimit = $trip->getDateRegistrationLimit();
            $dateRegistrationLimit = new \DateTimeImmutable($dateRegistrationLimit->format('Y-m-d H:i:s'), $timezone);

            // Calculate the end of the trip by adding the duration to `dateHourStart`
            $dateHourEnd = (clone $dateHourStart)->modify("+{$durationInHours} hours");

            // Ensure the comparison is done only on 'Y-m-d H:i' precision
            $formattedStart = $dateHourStart->format('Y-m-d H:i');
            $formattedNow = $now->format('Y-m-d H:i');
            $formattedEnd = $dateHourEnd->format('Y-m-d H:i');

            // 1. If the number of participants has reached or exceeded the max allowed, set the state to 'closed'
            if (count($trip->getParticipants()) >= $trip->getNumMaxRegistration() && $trip->getState()->getLabel() !== 'closed') {
                $closedState = $this->tripRepository->findStateByLabel('closed');
                if ($closedState) {
                    $trip->setState($closedState);
                }
            }

            // 2. If 1 month has passed since `dateHourStart`, archive the trip
            $oneMonthAfterStart = (clone $dateHourStart)->modify('+1 month');
            if ($oneMonthAfterStart <= $now) {
                $trip->setArchived(true);
               
            }

             // 3. If current time is between `dateHourStart` and `dateHourEnd`, set the state to 'activity in progress'
            if ($formattedStart <= $formattedNow && $formattedNow < $formattedEnd) {
                $inProgressState = $this->tripRepository->findStateByLabel('activity in progress');
                if ($inProgressState) {
                    $trip->setState($inProgressState);
                    
                } 
            } 


            // 4. If `dateHourEnd` is in the past, set the state to 'passed'
            if ($dateHourEnd < $now) {
                $passedState = $this->tripRepository->findStateByLabel('passed');
                if ($passedState) {
                    $trip->setState($passedState);
                    
                }
            }
            dump($trip->getId());
           
            dump($dateRegistrationLimit);
            dump($dateHourStart);
            dump($now);
            // 5. If `dateRegistrationLimit` is reached and the state is not 'closed'
            if ($dateRegistrationLimit <= $now && $trip->getState()->getLabel() == 'open') {
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
