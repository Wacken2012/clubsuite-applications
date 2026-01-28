<?php
namespace OCA\ClubSuiteApplications\Service;

use OCP\ICache;

class CacheService {
    private $cache;
    public function __construct(ICache $cache) { $this->cache = $cache; }
    public function get(string $k) { return $this->cache->get($k); }
    public function set(string $k, $v, int $ttl = 300) { $this->cache->set($k, $v, $ttl); }
    public function delete(string $k) { $this->cache->delete($k); }
}
