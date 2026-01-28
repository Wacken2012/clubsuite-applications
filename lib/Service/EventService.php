<?php
namespace OCA\ClubSuiteApplications\Service;

use OCP\EventDispatcher\IEventDispatcher;
use OCA\ClubSuiteApplications\Events\AntraegeBasicEvent;
use OCA\ClubSuiteApplications\Events\AntraegeCallbackEvent;
use OCA\ClubSuiteApplications\Events\AntraegeRequestDataEvent;

class EventService {
    private IEventDispatcher $dispatcher;

    public function __construct(IEventDispatcher $dispatcher) {
        $this->dispatcher = $dispatcher;
    }

    public function dispatchBasicEvent(array $payload): void {
        $event = new AntraegeBasicEvent(uniqid('antr_', true), time(), $payload);
        $this->dispatcher->dispatch($event);
    }

    public function dispatchCallbackEvent(array $payload, callable $callback): void {
        $event = new AntraegeCallbackEvent(uniqid('antr_cb_', true), time(), $payload, $callback);
        $this->dispatcher->dispatch($event);
    }

    public function dispatchRequestDataEvent(callable $callback): void {
        $event = new AntraegeRequestDataEvent(uniqid('antr_req_', true), time(), [], $callback);
        $this->dispatcher->dispatch($event);
    }
}
