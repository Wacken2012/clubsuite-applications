<?php
namespace OCA\ClubSuiteApplications\Listener;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

use OCA\ClubSuiteApplications\Events\AntraegeRequestDataEvent;

class AntraegeRequestDataEventListener implements IEventListener {
    public function handle(Event $event): void {
        if (!($event instanceof AntraegeRequestDataEvent)) {
            return;
        }
        $data = ['app' => 'Antraege', 'invoices' => 0];
        $event->respond($data);
    }
}
