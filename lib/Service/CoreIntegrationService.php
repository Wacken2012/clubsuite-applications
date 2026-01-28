<?php
declare(strict_types=1);

namespace OCA\ClubSuiteApplications\Service;

use OCA\ClubSuiteApplications\Db\ApplicationEntity;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;

/**
 * Integration service for clubsuite-core (Member management)
 * 
 * Uses cross-app service calls via Nextcloud's DI container.
 * Falls back gracefully if clubsuite-core is not installed.
 */
class CoreIntegrationService {
    private LoggerInterface $logger;
    private bool $coreAvailable;
    private $memberService = null;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
        $this->coreAvailable = $this->checkCoreAvailable();
    }

    /**
     * Check if clubsuite-core app is available
     */
    private function checkCoreAvailable(): bool {
        try {
            return class_exists('\OCA\ClubSuiteCore\Service\MemberService');
        } catch (\Throwable $e) {
            $this->logger->debug('clubsuite-core not available: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the MemberService from clubsuite-core
     */
    private function getMemberService(): ?object {
        if (!$this->coreAvailable) {
            return null;
        }
        
        if ($this->memberService === null) {
            try {
                $app = new \OCA\ClubSuiteCore\AppInfo\Application();
                $this->memberService = $app->getContainer()->get(\OCA\ClubSuiteCore\Service\MemberService::class);
            } catch (\Throwable $e) {
                $this->logger->warning('Could not get MemberService: ' . $e->getMessage());
                return null;
            }
        }
        
        return $this->memberService;
    }

    /**
     * Check if core integration is available
     */
    public function isAvailable(): bool {
        return $this->coreAvailable && $this->getMemberService() !== null;
    }

    /**
     * Find a member by ID
     * 
     * @param int $memberId
     * @return array|null Member data as array, or null if not found
     */
    public function findMember(int $memberId): ?array {
        $service = $this->getMemberService();
        if ($service === null) {
            return null;
        }

        try {
            $member = $service->getMember($memberId);
            return $member->jsonSerialize();
        } catch (DoesNotExistException $e) {
            $this->logger->debug("Member {$memberId} not found");
            return null;
        } catch (\Throwable $e) {
            $this->logger->warning('Error finding member: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Validate that a member exists
     */
    public function memberExists(int $memberId): bool {
        return $this->findMember($memberId) !== null;
    }

    /**
     * Create a new member from an approved application
     * 
     * @param ApplicationEntity $application
     * @return int|null The new member ID, or null on failure
     */
    public function createMemberFromApplication(ApplicationEntity $application): ?int {
        $service = $this->getMemberService();
        if ($service === null) {
            $this->logger->warning('Cannot create member: clubsuite-core not available');
            return null;
        }

        try {
            $data = $application->getData();
            
            $memberData = [
                'firstname' => $data['firstName'] ?? '',
                'lastname' => $data['lastName'] ?? '',
                'email' => $data['email'] ?? '',
                'phone' => $data['phone'] ?? '',
                'street' => $data['street'] ?? '',
                'zip' => $data['zip'] ?? '',
                'city' => $data['city'] ?? '',
                'status' => 'active',
                'eintrittsdatum' => $application->getApprovedAt()?->format('Y-m-d') ?? date('Y-m-d'),
            ];

            // Add IBAN if provided
            if (!empty($data['iban'])) {
                $memberData['iban'] = $data['iban'];
            }

            $this->logger->info('Creating member from application #' . $application->getId(), [
                'app' => 'clubsuite-applications',
                'firstname' => $memberData['firstname'],
                'lastname' => $memberData['lastname']
            ]);

            $member = $service->createMember($memberData);
            
            $this->logger->info('Member created with ID: ' . $member->getId(), [
                'app' => 'clubsuite-applications',
                'memberId' => $member->getId()
            ]);

            return $member->getId();
            
        } catch (\Throwable $e) {
            $this->logger->error('Error creating member from application: ' . $e->getMessage(), [
                'app' => 'clubsuite-applications',
                'applicationId' => $application->getId(),
                'exception' => $e
            ]);
            return null;
        }
    }

    /**
     * List all members (for dropdown selection)
     * 
     * @return array Array of members
     */
    public function listMembers(): array {
        $service = $this->getMemberService();
        if ($service === null) {
            return [];
        }

        try {
            $members = $service->listMembers();
            return array_map(fn($m) => $m->jsonSerialize(), $members);
        } catch (\Throwable $e) {
            $this->logger->warning('Error listing members: ' . $e->getMessage());
            return [];
        }
    }
}
