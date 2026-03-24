<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\Dto\BookSlotRequest;
use App\Booking\Command\BookSlotCommand;
use App\Booking\Command\BookSlotResult;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class BookSlotController
{
    #[Route('/api/slots/{id}/book', name: 'api_slot_book', methods: ['POST'])]
    public function __invoke(
        Request $request,
        string $id,
        MessageBusInterface $bus,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
    ): JsonResponse {
        try {
            $uuid = Uuid::fromString($id);
        } catch (\InvalidArgumentException) {
            return $this->problem(
                status: Response::HTTP_BAD_REQUEST,
                title: 'Invalid slot id',
                detail: 'The slot id must be a valid UUID.',
            );
        }

        if ('' === $request->getContent()) {
            return $this->problem(
                status: Response::HTTP_BAD_REQUEST,
                title: 'Invalid request body',
                detail: 'JSON body with patientEmail is required.',
            );
        }

        try {
            $body = $serializer->deserialize($request->getContent(), BookSlotRequest::class, 'json');
        } catch (NotEncodableValueException) {
            return $this->problem(
                status: Response::HTTP_BAD_REQUEST,
                title: 'Invalid JSON',
                detail: 'Request body could not be parsed as JSON.',
            );
        }

        if (!$body instanceof BookSlotRequest) {
            return $this->problem(
                status: Response::HTTP_BAD_REQUEST,
                title: 'Invalid request body',
                detail: 'Expected an object with patientEmail.',
            );
        }

        $violations = $validator->validate($body);
        if ($violations->count() > 0) {
            return $this->problem(
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
                title: 'Validation failed',
                detail: (string) $violations->get(0)->getMessage(),
            );
        }

        try {
            $envelope = $bus->dispatch(new BookSlotCommand($uuid, $body->patientEmail));
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();
            if ($previous instanceof HttpExceptionInterface) {
                return $this->problem(
                    status: $previous->getStatusCode(),
                    title: match ($previous->getStatusCode()) {
                        Response::HTTP_NOT_FOUND => 'Not Found',
                        Response::HTTP_CONFLICT => 'Conflict',
                        default => 'Request error',
                    },
                    detail: $previous->getMessage(),
                );
            }

            throw $e;
        } catch (HttpExceptionInterface $e) {
            return $this->problem(
                status: $e->getStatusCode(),
                title: match ($e->getStatusCode()) {
                    Response::HTTP_NOT_FOUND => 'Not Found',
                    Response::HTTP_CONFLICT => 'Conflict',
                    default => 'Request error',
                },
                detail: $e->getMessage(),
            );
        }

        $handled = $envelope->last(HandledStamp::class);
        if (!$handled instanceof HandledStamp) {
            throw new \LogicException('BookSlotCommand was not handled by any handler.');
        }

        $result = $handled->getResult();
        if (!$result instanceof BookSlotResult) {
            throw new \LogicException('Unexpected handler return type.');
        }

        return new JsonResponse(
            [
                'id' => $result->id,
                'isBooked' => $result->isBooked,
                'startTime' => $result->startTime->format(\DateTimeInterface::ATOM),
                'endTime' => $result->endTime->format(\DateTimeInterface::ATOM),
            ],
            Response::HTTP_OK,
            headers: ['Content-Type' => 'application/json'],
        );
    }

    /**
     * @return JsonResponse JSON Problem Details (RFC 7807-style fields)
     */
    private function problem(int $status, string $title, string $detail): JsonResponse
    {
        return new JsonResponse(
            [
                'title' => $title,
                'detail' => $detail,
                'status' => $status,
            ],
            $status,
            headers: ['Content-Type' => 'application/problem+json'],
        );
    }
}
