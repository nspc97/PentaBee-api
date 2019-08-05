<?php

namespace App\Repository;

use App\Entity\Feedback;
use App\Entity\User;
use App\Filters\FeedbackPagination;
use App\Filters\FeedbackSort;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Feedback|null find($id, $lockMode = null, $lockVersion = null)
 * @method Feedback|null findOneBy(array $criteria, array $orderBy = null)
 * @method Feedback[]    findAll()
 * @method Feedback[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeedbackRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Feedback::class);
    }

    /**
     * Persist a Feedback.
     * @param Feedback $feedback
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Feedback $feedback): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($feedback);
        $entityManager->flush();
    }

    /**
     * @param User $user
     * @return float
     * @throws NonUniqueResultException
     */
    public function getAvgStars(User $user): float
    {
        $queryBuilder = $this->createQueryBuilder('feedback');
        $queryBuilder->select('SUM (feedback.stars) / COUNT (feedback)')
            ->where('feedback.userTo = :user')
            ->setParameter('user', $user);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getUserFeedback(User $user, FeedbackSort $feedbackSort): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('feedback');
        $queryBuilder->select('feedback')
            ->where('feedback.userTo = :user')
            ->setParameter('user', $user);
        if ($feedbackSort->createdAt !== null) {
            $queryBuilder->orderBy('feedback.createdAt', $feedbackSort->createdAt);
        }
        if ($feedbackSort->stars !== null) {
            $queryBuilder->orderBy('feedback.stars', $feedbackSort->stars);
        }

        return $queryBuilder;
    }

    public function getUserFeedbackPaginated(
        User $user,
        FeedbackSort $feedbackSort,
        FeedbackPagination $feedbackPagination
    ): Query {
        $queryBuilder = $this->getUserFeedback($user, $feedbackSort);

        $currentPage = $feedbackPagination->currentPage < 1 ? 1 : $feedbackPagination->currentPage;
        $firstResult = ($currentPage - 1) * $feedbackPagination->pageSize;

        $query = $queryBuilder
            ->setFirstResult($firstResult)
            ->setMaxResults($feedbackPagination->pageSize)
            ->getQuery();

        return $query;
    }
}