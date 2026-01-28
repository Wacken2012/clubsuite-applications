<?php
namespace OCA\ClubSuiteApplications\Listener;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

use OCA\ClubSuiteApplications\Events\AntraegeBasicEvent;

class AntraegeBasicEventListener implements IEventListener {
    public function handle(Event $event): void {
        if (!($event instanceof AntraegeBasicEvent)) {
            return;
        }
        error_log('AntraegeBasicEvent received in Antraege: ' . $event->getId());
    }
}
