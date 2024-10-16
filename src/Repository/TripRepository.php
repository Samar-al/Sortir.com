<?php

namespace App\Repository;

use App\Entity\State;
use App\Entity\Trip;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<Trip>
 */
class TripRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Trip::class);
    }

    /**
     * Filter trips based on the given criteria.
     */
    public function findFilteredTrips(?string $baseName = null, ?string $name = null, ?string $startDate = null, ?string $endDate = null, ?UserInterface $user = null, ?bool $meOrganizer = null, ?bool $meRegistered = null, ?bool $meNotRegistered = null, ?bool $passedTrip = null): array
    {
        $queryBuilder = $this->createQueryBuilder('t');
    
        // Filter by base name
        if ($baseName) {
            $queryBuilder->join('t.base', 'b')
                         ->andWhere('b.name = :baseName')
                         ->setParameter('baseName', $baseName);
        }
    
        // Filter by trip name (contains)
        if ($name) {
            $queryBuilder->andWhere('t.name LIKE :name')
                         ->setParameter('name', '%' . $name . '%');
        }
    
        // Filter by start date
        if ($startDate) {
            $queryBuilder->andWhere('t.dateHourStart >= :startDate')
                         ->setParameter('startDate', new \DateTime($startDate));
        }
    
        // Filter by end date
        if ($endDate) {
            $queryBuilder->andWhere('t.dateHourStart <= :endDate')
                         ->setParameter('endDate', new \DateTime($endDate));
        }
    
        // Filter trips where the user is the organizer
        if ($meOrganizer && $user) {
            $queryBuilder->andWhere('t.organiser = :user')
                         ->setParameter('user', $user);
        }
    
        // Filter trips where the user is registered
        if ($meRegistered && $user) {
            $queryBuilder->andWhere(':user MEMBER OF t.participants')
                         ->setParameter('user', $user);
        }
    
        // Filter trips where the user is not registered
        if ($meNotRegistered && $user) {
            $queryBuilder->andWhere(':user NOT MEMBER OF t.participants')
                         ->setParameter('user', $user);
        }
    
        // Filter passed trips
        if ($passedTrip) {
            $queryBuilder->andWhere('t.dateHourStart < :today')
                         ->setParameter('today', new \DateTime());
        }
    
        return $queryBuilder->getQuery()->getResult();
    }

    public function findTripsNeedingStatusUpdate()
    {
        return $this->createQueryBuilder('t')
            ->where('t.isArchived = 0')  // Ensure only non-archived trips are fetched
            ->getQuery()
            ->getResult();
    }

    public function findStateByLabel(string $label)
    {
        return $this->getEntityManager()
            ->getRepository(State::class)
            ->findOneBy(['label' => $label]);
    }
        


    //    /**
    //     * @return Trip[] Returns an array of Trip objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Trip
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
