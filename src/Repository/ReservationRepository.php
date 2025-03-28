<?php
// src/Repository/ReservationRepository.php
namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\SportSpace; 
use Doctrine\Common\Collections\ArrayCollection;


class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * Find all reservations with sport space data
     * @return Reservation[]
     */
    public function findAll(): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.sportSpace', 's')
            ->addSelect('s')
            ->orderBy('r.idReservation', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find reservation by ID
     */
    public function findById(int $id): ?Reservation
    {
        return $this->find($id);
    }

    /**
     * Save a reservation entity
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Reservation $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Update a reservation entity
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function update(Reservation $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Remove a reservation entity
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Reservation $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find reservations by sport space
     * @return Reservation[]
     */
    public function findBySportSpace(int $sportSpaceId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.sportSpace = :sportSpaceId')
            ->setParameter('sportSpaceId', $sportSpaceId)
            ->orderBy('r.date', 'ASC')
            ->addOrderBy('r.time', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find reservations by date range
     * @return Reservation[]
     */
    public function findByDateRange(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.date BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('r.date', 'ASC')
            ->addOrderBy('r.time', 'ASC')
            ->getQuery()
            ->getResult();
    }
    public function findAllWithSportSpace(): array
{
    return $this->createQueryBuilder('r')
        ->leftJoin('r.sportSpace', 's')
        ->addSelect('s')
        ->orderBy('r.date', 'DESC')
        ->addOrderBy('r.time', 'DESC')
        ->getQuery()
        ->getResult();
}

public function isTimeSlotAvailable(\DateTimeInterface $date, string $time, SportSpace $space): bool
{
    $qb = $this->createQueryBuilder('r');
    
    return empty($qb
        ->where('r.date = :date')
        ->andWhere('TIME(r.time) = :time')
        ->andWhere('r.sportSpace = :space')
        ->setParameter('date', $date)
        ->setParameter('time', $time)
        ->setParameter('space', $space)
        ->getQuery()
        ->getResult());
}
public function findWithFilters(?string $search, ?string $dateFrom, ?string $dateTo, ?string $sort): array
{
    $qb = $this->createQueryBuilder('r')
        ->join('r.sportSpace', 's')  // Use regular join instead of leftJoin
        ->addSelect('s');           // Select the joined sportSpace

    // Search filter (only when search term exists)
    if (!empty($search)) {
        $qb->andWhere('s.name LIKE :search')
           ->setParameter('search', '%' . $search . '%');
    }

    // Date range filter
    if ($dateFrom) {
        $qb->andWhere('r.date >= :dateFrom')
           ->setParameter('dateFrom', new \DateTime($dateFrom));
    }
    if ($dateTo) {
        $qb->andWhere('r.date <= :dateTo')
           ->setParameter('dateTo', new \DateTime($dateTo));
    }

    // Sorting
    switch ($sort) {
        case 'date_desc':
            $qb->orderBy('r.date', 'DESC')->addOrderBy('r.time', 'ASC');
            break;
        case 'time_asc':
            $qb->orderBy('r.time', 'ASC')->addOrderBy('r.date', 'ASC');
            break;
        case 'time_desc':
            $qb->orderBy('r.time', 'DESC')->addOrderBy('r.date', 'DESC');
            break;
        case 'duration_asc':
            $qb->orderBy('r.duration', 'ASC')->addOrderBy('r.date', 'ASC');
            break;
        case 'duration_desc':
            $qb->orderBy('r.duration', 'DESC')->addOrderBy('r.date', 'DESC');
            break;
        default: // date_asc
            $qb->orderBy('r.date', 'ASC')->addOrderBy('r.time', 'ASC');
    }

    return $qb->getQuery()->getResult();
}
public function findReservationsBySportSpaceEntity(SportSpace $sportSpace): array
    {
        if (!$sportSpace) {
            return [];
        }

        return $this->createQueryBuilder('r')
            ->leftJoin('r.sportSpace', 's')
            ->addSelect('s')
            ->where('r.sportSpace = :sportSpace')
            ->setParameter('sportSpace', $sportSpace)
            ->orderBy('r.date', 'ASC')
            ->addOrderBy('r.time', 'ASC')
            ->getQuery()
            ->getResult();
    }
}