<?php
declare(strict_types=1);

namespace OCA\ClubSuiteApplications\Listener;

use OCA\ClubSuiteApplications\Events\ApplicationApprovedEvent;
use OCA\ClubSuiteApplications\Service\CoreIntegrationService;
use OCA\ClubSuiteApplications\Db\ApplicationMapper;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

/**
 * Listener that creates a new member in clubsuite-core when an application is approved.
 * Only triggers for membership applications that don't already have a member_id.
 * 
 * @implements IEventListener<ApplicationApprovedEvent>
 */
class CreateMemberFromApplicationListener implements IEventListener {
    private CoreIntegrationService $coreService;
    private ApplicationMapper $mapper;
    private LoggerInterface $logger;

    public function __construct(
        CoreIntegrationService $coreService,
        ApplicationMapper $mapper,
        LoggerInterface $logger
    ) {
        $this->coreService = $coreService;
        $this->mapper = $mapper;
        $this->logger = $logger;
    }

    public function handle(Event $event): void {
        if (!($event instanceof ApplicationApprovedEvent)) {
            return;
        }

        $application = $event->getApplication();

        // Only process membership applications
        if ($application->getType() !== 'membership') {
            $this->logger->debug('Skipping non-membership application', [
                'app' => 'clubsuite-applications',
                'type' => $application->getType()
            ]);
            return;
        }

        // Skip if member creation is disabled
        if (!$event->shouldCreateMember()) {
            $this->logger->debug('Member creation disabled for this event');
            return;
        }

        // Skip if already has a member ID
        if ($application->getMemberId() !== null) {
            $this->logger->debug('Application already has member ID', [
                'app' => 'clubsuite-applications',
                'memberId' => $application->getMemberId()
            ]);
            return;
        }

        // Check if core integration is available
        if (!$this->coreService->isAvailable()) {
            $this->logger->info('clubsuite-core not available, skipping member creation');
            return;
        }

        // Create the member
        $memberId = $this->coreService->createMemberFromApplication($application);
        
        if ($memberId !== null) {
            // Update the application with the new member ID
            $application->setMemberId($memberId);
            $this->mapper->update($application);
            
            $this->logger->info('Member created and linked to application', [
                'app' => 'clubsuite-applications',
                'applicationId' => $application->getId(),
                'memberId' => $memberId
            ]);
        }
    }
}
