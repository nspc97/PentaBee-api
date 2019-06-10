<?php

namespace App\Service;

use App\DTO\ActivityDTO;
use App\Entity\Activity;
use App\Entity\Technology;
use App\Entity\ActivityType;
use App\Entity\User;
use App\Exceptions\EntityNotFound;
use App\Repository\ActivityRepository;
use App\Repository\TechnologyRepository;
use App\Repository\TypeRepository;
use App\Repository\UserRepository;

class ActivityTransformer
{

    /**
     * @var UserRepository
     */
    private $userRepo;
    /**
     * @var TechnologyRepository
     */
    private $techRepo;
    /**
     * @var TypeRepository
     */
    private $typeRepo;
    /**
     * @var ActivityRepository
     */
    private $activityRepo;

    public function __construct(
        UserRepository $userRepo,
        TechnologyRepository $techRepo,
        TypeRepository $typeRepo,
        ActivityRepository $activityRepo
    ) {
        $this->userRepo = $userRepo;
        $this->techRepo = $techRepo;
        $this->typeRepo = $typeRepo;
        $this->activityRepo = $activityRepo;
    }


    /**
     * @param ActivityDTO $dto
     * @param Activity $entity
     * @throws EntityNotFound
     */
    private function addTechnologies(ActivityDTO $dto, Activity $entity): void
    {
        /** @var Technology $tech */
        foreach ($dto->technologies as $tech) {
            $techID = $tech->id;
            $techToAdd = $this->techRepo->find($techID);
            if (!$techToAdd) {
                $entityNotFound = new EntityNotFound(
                    Technology::class,
                    $techID,
                    'No technology found.'
                );
                throw $entityNotFound;
            }
            $entity->addTechnology($techToAdd);
        }
    }

    /**
     * @param ActivityDTO $dto
     * @param Activity $entity
     * @throws EntityNotFound
     */
    private function addTypes(ActivityDTO $dto, Activity $entity): void
    {
        /** @var ActivityType $activityType */
        foreach ($dto->types as $activityType) {
            $activityTypeID = $activityType->id;
            $activityTypeToAdd = $this->typeRepo->find($activityTypeID);
            if (!$activityTypeToAdd) {
                $entityNotFound = new EntityNotFound(
                    ActivityType::class,
                    $activityTypeID,
                    'No activity type found.'
                );
                throw $entityNotFound;
            }
            $entity->addType($activityTypeToAdd);
        }
    }

    /**
     * @param Activity $entity
     */
    private function resetTechTypeCollections(Activity $entity): void
    {
        foreach ($entity->getTechnologies() as $techToRemove) {
            $entity->removeTechnology($techToRemove);
        }
        foreach ($entity->getTypes() as $typeToRemove) {
            $entity->removeType($typeToRemove);
        }
    }

    /**
     * @param ActivityDTO $dto
     * @return Activity
     * @throws EntityNotFound
     */
    public function transform(
        ActivityDTO $dto
    ): Activity {

        if ($dto->id !== null) {
            $entity = $this->activityRepo->find($dto->id);
            if (!$entity) {
                $entityNotFound = new EntityNotFound(
                    Activity::class,
                    $dto->id,
                    'No activity found.'
                );
                throw $entityNotFound;
            }
            $this->resetTechTypeCollections($entity);
        } else {
            $entity = new Activity();
        }
        $entity->setName($dto->name);
        $entity->setDescription($dto->description);
        $entity->setApplicationDeadline($dto->applicationDeadline);
        $entity->setFinalDeadline($dto->finalDeadline);
        $entity->setCreatedAt($dto->createdAt);
        $entity->setUpdatedAt($dto->updatedAt);
        $entity->setStatus($dto->status);

        /** @var User $tempUser */
        $tempUser = $this->userRepo->find($dto->owner);
        $entity->setOwner($tempUser);

        $this->addTechnologies($dto, $entity);
        $this->addTypes($dto, $entity);

        return $entity;
    }
}