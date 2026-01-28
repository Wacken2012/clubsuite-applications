<?php
declare(strict_types=1);

namespace OCA\ClubSuiteApplications\Events;

use OCA\ClubSuiteApplications\Db\ApplicationEntity;
use OCP\EventDispatcher\Event;

/**
 * Event fired when an application is approved.
 * Listeners can use this to create members, send notifications, etc.
 */
class ApplicationApprovedEvent extends Event {
    private ApplicationEntity $application;
    private bool $createMember;

    public function __construct(ApplicationEntity $application, bool $createMember = true) {
        parent::__construct();
        $this->application = $application;
        $this->createMember = $createMember;
    }

    public function getApplication(): ApplicationEntity {
        return $this->application;
    }

    public function shouldCreateMember(): bool {
        return $this->createMember;
    }
}
