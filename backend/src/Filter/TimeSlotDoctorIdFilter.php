<?php

declare(strict_types=1);

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Uid\Uuid;

/**
 * Friendly query param `doctor_id` (UUID string) — equivalent to filtering by doctor.id.
 */
final class TimeSlotDoctorIdFilter extends AbstractFilter
{
    protected function filterProperty(
        string $property,
        mixed $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        if ('doctor_id' !== $property || !$this->isPropertyEnabled($property, $resourceClass)) {
            return;
        }

        if (!\is_string($value) || '' === $value) {
            return;
        }

        try {
            $uuid = Uuid::fromString($value);
        } catch (\InvalidArgumentException) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $parameterName = $queryNameGenerator->generateParameterName('doctor_id');
        $queryBuilder->andWhere(\sprintf('%s.doctor = :%s', $rootAlias, $parameterName))
            ->setParameter($parameterName, $uuid, 'uuid');
    }

    public function getDescription(string $resourceClass): array
    {
        if (!$this->isPropertyEnabled('doctor_id', $resourceClass)) {
            return [];
        }

        return [
            'doctor_id' => [
                'property' => 'doctor_id',
                'type' => 'string',
                'required' => false,
                'is_collection' => false,
                'openapi' => [
                    'description' => 'Filter by doctor UUID (same as doctor.id).',
                ],
            ],
        ];
    }
}
