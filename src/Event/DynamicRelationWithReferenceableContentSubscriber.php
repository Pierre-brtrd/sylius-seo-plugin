<?php

declare(strict_types=1);

namespace Dedi\SyliusSEOPlugin\Event;

use Dedi\SyliusSEOPlugin\Domain\SEO\Adapter\ReferenceableInterface;
use Dedi\SyliusSEOPlugin\Entity\SEOContent;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\PreUpdate;

#[AsEntityListener(event: PreUpdate::class, entity: SEOContent::class, method: 'loadClassMetadata')]
class DynamicRelationWithReferenceableContentSubscriber implements EventSubscriber
{
    public const REFERENCIABLE_FIELD_NAME = 'referenceableContent';

    public function getSubscribedEvents(): array
    {
        return [Events::loadClassMetadata];
    }

    public function loadClassMetadata(PreUpdateEventArgs $eventArgs): void
    {
        dd($eventArgs);
        $metadata = $eventArgs->getClassMetadata();
        if (
            !$metadata->getReflectionClass()->implementsInterface(ReferenceableInterface::class) ||
            !$metadata->getReflectionClass()->hasProperty(self::REFERENCIABLE_FIELD_NAME)
        ) {
            return;
        }

        $namingStrategy = $eventArgs
            ->getEntityManager()
            ->getConfiguration()
            ->getNamingStrategy()
        ;

        $metadata->mapOneToOne([
            'targetEntity' => SEOContent::class,
            'fieldName' => self::REFERENCIABLE_FIELD_NAME,
            'cascade' => ['persist', 'remove'],
            'joinTable' => [
                'name' => strtolower($namingStrategy->classToTableName($metadata->getName())) . '_referenceable_content_id',
                'referencedColumnName' => $namingStrategy->referenceColumnName(),
            ],
        ]);
    }
}
