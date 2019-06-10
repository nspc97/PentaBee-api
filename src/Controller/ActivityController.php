<?php

namespace App\Controller;

use App\DTO\ActivityDTO;
use App\Entity\Activity;
use App\Exceptions\EntityNotFound;
use App\Service\ActivityTransformer;
use Exception;
use JMS\Serializer\DeserializationContext;
use App\Repository\ActivityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Activity controller.
 * @Route("/api/activities", name="activities")
 */
class ActivityController extends AbstractController
{
    /** @var SerializerInterface */
    private $serializer;

    /** @var ActivityTransformer */
    private $transformer;

    /** @var ValidatorInterface */
    private $validator;

    public function __construct(
        SerializerInterface $serializer,
        ActivityTransformer $transformer,
        ValidatorInterface $validator
    ) {
        $this->serializer = $serializer;
        $this->transformer = $transformer;
        $this->validator = $validator;
    }

    /**
     * Lists all Activities.
     * @Rest\Get("/")
     * @return Response
     */
    public function getActivitiesList(): Response
    {
        $activities = $this->getDoctrine()->getRepository(Activity::class)->findAll();

        /** @var SerializationContext $context */
        $context = SerializationContext::create()->setGroups(array('ActivityList'));

        $json = $this->serializer->serialize(
            $activities,
            'json',
            $context
        );

        return new JsonResponse($json, 200, [], true);
    }

    /**
     * Show details about an Activity.
     * @Rest\Get("/{id}")
     * @return Response
     */
    public function getActivityDetails($id): Response
    {
        $activity = $this->getDoctrine()->getRepository(Activity::class)->find($id);

        /** @var SerializationContext $context */
        $context = SerializationContext::create()->setGroups(array('ActivityDetails'));

        $json = $this->serializer->serialize(
            $activity,
            'json',
            $context
        );

        return new JsonResponse($json, 200, [], true);
    }

    /**
     * @Rest\Delete("/{id}/delete")
     * @param int $id
     * @param ActivityRepository $activityRepository
     * @return JsonResponse
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deleteActivity($id, ActivityRepository $activityRepository): JsonResponse
    {
        $activity = $activityRepository->find($id);

        if (!$activity) {
            return new JsonResponse(['message' => 'The activity was not found!'], Response::HTTP_NOT_FOUND);
        }

        $activityRepository->delete($activity);

        return new JsonResponse(['message' => 'The activity was successfully deleted!'], Response::HTTP_OK);
    }

    /**
     * Create an Activity.
     * @Rest\Post("/create")
     * @param ActivityRepository $activityRepository
     * @param Request $request
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createAction(ActivityRepository $activityRepository, Request $request): Response
    {
        $data = $request->getContent();

        /** @var DeserializationContext $context */
        $context = DeserializationContext::create();

        $activityDTO = $this->serializer->deserialize(
            $data,
            ActivityDTO::class,
            'json',
            $context
        );

        $errors = $this->validator->validate($activityDTO);

        if (count($errors) > 0) {
            $errorsString = (string)$errors;
            return new Response($errorsString);
        }

        try {
            $newActivity = $this->transformer->transform($activityDTO);
        } catch (EntityNotFound $exception) {
            return new JsonResponse(
                [
                    'message' => $exception->getMessage(),
                    'entity' => $exception->getEntity(),
                    'id' => $exception->getId()
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        $activityRepository->save($newActivity);

        return new JsonResponse(['message' => 'Activity successfully created!'], Response::HTTP_CREATED);
    }

    /**
     * Create an Activity.
     * @Rest\Post("/{id}/edit")
     * @param $id
     * @param ActivityRepository $activityRepository
     * @param Request $request
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function editAction($id, ActivityRepository $activityRepository, Request $request): Response
    {
        /** Verifying if the activity exists*/
        $activity = $activityRepository->find($id);

        if (!$activity) {
            return new JsonResponse(['message' => 'The activity was not found!'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->getContent();

        /** @var DeserializationContext $context */
        $context = DeserializationContext::create();

        $activityDTO = $this->serializer->deserialize(
            $data,
            ActivityDTO::class,
            'json',
            $context
        );

        $errors = $this->validator->validate($activityDTO);

        if (count($errors) > 0) {
            $errorsString = (string)$errors;
            return new JsonResponse(['message' => $errorsString], Response::HTTP_BAD_REQUEST);
        }

        try {
            $activityToEdit = $this->transformer->transform($activityDTO);
        } catch (EntityNotFound $exception) {
            return new JsonResponse(
                [
                    'message' => $exception->getMessage(),
                    'entity' => $exception->getEntity(),
                    'id' => $exception->getId()
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        $activityRepository->save($activityToEdit);

        return new JsonResponse(['message' => 'Activity successfully edited!'], Response::HTTP_OK);
    }
}