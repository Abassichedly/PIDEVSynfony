<?php
// src/Repository/SportSpaceRepository.php
namespace App\Repository;

use App\Entity\SportSpace;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

class SportSpaceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SportSpace::class);
    }

    /**
     * Find all sport spaces ordered by ID
     * @return SportSpace[]
     */
    public function findAll(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.idSportSpace', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find sport space by ID
     */
    public function findById(int $id): ?SportSpace
    {
        return $this->find($id);
    }

    /**
     * Save a sport space entity
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(SportSpace $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Update a sport space entity
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function update(SportSpace $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove a sport space entity
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(SportSpace $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find available sport spaces
     * @return SportSpace[]
     */
    public function findAvailable(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.availability = :available')
            ->setParameter('available', true)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find sport spaces by type
     * @return SportSpace[]
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.type = :type')
            ->andWhere('s.availability = :available')
            ->setParameter('type', $type)
            ->setParameter('available', true)
            ->getQuery()
            ->getResult();
    }

    public function findAllSorted(string $sort = 'name_asc'): array
    {
        return $this->findWithFilters(null, 'all', $sort, 'all');
    }
    
    public function findWithFilters(?string $search, ?string $type, ?string $sort, ?string $availability): array
{
    $qb = $this->createQueryBuilder('s');

    // Search filter
    if ($search) {
        $qb->andWhere('s.name LIKE :search OR s.location LIKE :search OR s.phone LIKE :search')
           ->setParameter('search', '%'.$search.'%');
    }

    // Type filter
    if ($type && $type !== 'all') {
        $qb->andWhere('s.type = :type')
           ->setParameter('type', $type);
    }

    // Availability filter
    if ($availability && $availability !== 'all') {
        $qb->andWhere('s.availability = :availability')
           ->setParameter('availability', $availability === 'available');
    }

    // Sorting
    switch ($sort) {
        case 'name_desc':
            $qb->orderBy('s.name', 'DESC');
            break;
        case 'location_asc':
            $qb->orderBy('s.location', 'ASC');
            break;
        case 'location_desc':
            $qb->orderBy('s.location', 'DESC');
            break;
        case 'type_asc':
            $qb->orderBy('s.type', 'ASC');
            break;
        default: // name_asc
            $qb->orderBy('s.name', 'ASC');
    }

    return $qb->getQuery()->getResult();
}
public function findAllWithLocations()
{
    return $this->createQueryBuilder('s')
        ->select('s.idSportSpace as id, s.name, s.location as address')
        ->getQuery()
        ->getResult();
}
public function findAllTypes(): array
{
    // Return both existing DB types and hardcoded options
    $dbTypes = $this->createQueryBuilder('s')
        ->select('DISTINCT s.type')
        ->orderBy('s.type', 'ASC')
        ->getQuery()
        ->getSingleColumnResult();

    $defaultTypes = [
        'football', 'basketball', 'pilates', 'handball',
        'swimming', 'yoga', 'tennis', 'box', 'hockey', 'gym'
    ];

    // Merge and remove duplicates
    return array_unique(array_merge($dbTypes, $defaultTypes));
}
public function findByAlphabeticalOrder()
{
    return $this->createQueryBuilder('s')
        ->orderBy('s.name', 'ASC')
        ->getQuery()
        ->getResult();
}
}