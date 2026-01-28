<?php
namespace OCA\ClubSuiteApplications\Listener;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

use OCA\ClubSuiteApplications\Events\AntraegeCallbackEvent;

class AntraegeCallbackEventListener implements IEventListener {
    public function handle(Event $event): void {
        if (!($event instanceof AntraegeCallbackEvent)) {
            return;
        }
        $payload = $event->getPayload();
        $event->triggerCallback(['handledBy' => 'Antraege', 'count' => count($payload)]);
    }
}
