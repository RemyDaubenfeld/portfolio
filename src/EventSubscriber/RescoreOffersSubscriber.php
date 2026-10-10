<?php

namespace App\EventSubscriber;

use App\Entity\Department;
use App\Entity\NegativeKeyword;
use App\Entity\SearchCriteria;
use App\Service\JobSearch\OfferScorer;
use EasyCorp\Bundle\EasyAdminBundle\Event\AbstractLifecycleEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityDeletedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Les notes de pertinence dépendent des mots-clés et des départements : on recalcule
 * les offres à étudier dès qu'ils sont modifiés dans l'admin.
 */
class RescoreOffersSubscriber implements EventSubscriberInterface
{
    private const SCORING_ENTITIES = [SearchCriteria::class, NegativeKeyword::class, Department::class];

    public function __construct(private readonly OfferScorer $scorer)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AfterEntityPersistedEvent::class => 'onScoringConfigChanged',
            AfterEntityUpdatedEvent::class => 'onScoringConfigChanged',
            AfterEntityDeletedEvent::class => 'onScoringConfigChanged',
        ];
    }

    public function onScoringConfigChanged(AbstractLifecycleEvent $event): void
    {
        foreach (self::SCORING_ENTITIES as $class) {
            if ($event->getEntityInstance() instanceof $class) {
                $this->scorer->rescoreToReview();

                return;
            }
        }
    }
}
