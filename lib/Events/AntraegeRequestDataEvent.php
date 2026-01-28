<?php
namespace OCA\ClubSuiteApplications\Events;

use OCP\EventDispatcher\Event;

class AntraegeRequestDataEvent extends Event {
    private string $id;
    private int $timestamp;
    private array $payload;
    private $callback;

    public function __construct(string $id, int $timestamp, array $payload = [], ?callable $callback = null) {
        $this->id = $id;
        $this->timestamp = $timestamp;
        $this->payload = $payload;
        $this->callback = $callback;
    }

    public function getId(): string { return $this->id; }
    public function getTimestamp(): int { return $this->timestamp; }
    public function getPayload(): array { return $this->payload; }

    public function respond($data): void {
        if (is_callable($this->callback)) {
            ($this->callback)($data);
        }
    }
}
