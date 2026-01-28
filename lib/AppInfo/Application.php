<?php
declare(strict_types=1);

namespace OCA\ClubSuiteApplications\AppInfo;

use OCA\ClubSuiteApplications\Privacy\Register;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\IContainer;
use OCA\ClubSuiteApplications\Db\ApplicationMapper;
use OCA\ClubSuiteApplications\Service\ApplicationService;
use OCA\ClubSuiteApplications\Service\CacheService;
use OCA\ClubSuiteApplications\Service\EventService;
use OCA\ClubSuiteApplications\Service\CoreIntegrationService;
use OCA\ClubSuiteApplications\Service\FinanceIntegrationService;
use OCA\ClubSuiteApplications\Listener\AntraegeBasicEventListener;
use OCA\ClubSuiteApplications\Listener\AntraegeCallbackEventListener;
use OCA\ClubSuiteApplications\Listener\AntraegeRequestDataEventListener;
use OCA\ClubSuiteApplications\Listener\CreateMemberFromApplicationListener;
use OCA\ClubSuiteApplications\Listener\CreateTransactionFromInvoiceListener;
use OCA\ClubSuiteApplications\Events\AntraegeBasicEvent;
use OCA\ClubSuiteApplications\Events\AntraegeCallbackEvent;
use OCA\ClubSuiteApplications\Events\AntraegeRequestDataEvent;
use OCA\ClubSuiteApplications\Events\ApplicationApprovedEvent;
use OCA\ClubSuiteApplications\Events\InvoiceCreatedEvent;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;

if (!\class_exists('OCA\ClubSuiteApplications\AppInfo\Application', false)) {
class Application extends App implements IBootstrap {
    public const APP_ID = 'clubsuite-applications';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void {
        // Register existing event listeners
        $context->registerEventListener(AntraegeBasicEvent::class, AntraegeBasicEventListener::class);
        $context->registerEventListener(AntraegeCallbackEvent::class, AntraegeCallbackEventListener::class);
        $context->registerEventListener(AntraegeRequestDataEvent::class, AntraegeRequestDataEventListener::class);
        
        // Register integration event listeners
        $context->registerEventListener(ApplicationApprovedEvent::class, CreateMemberFromApplicationListener::class);
        $context->registerEventListener(InvoiceCreatedEvent::class, CreateTransactionFromInvoiceListener::class);
        
        // Register services via DI
        $context->registerService(CoreIntegrationService::class, function($c) {
            return new CoreIntegrationService(
                $c->get(LoggerInterface::class)
            );
        });
        
        $context->registerService(FinanceIntegrationService::class, function($c) {
            return new FinanceIntegrationService(
                $c->get(LoggerInterface::class)
            );
        });
    }

    public function boot(IBootContext $context): void {
        $context->injectFn(function(\Psr\Container\ContainerInterface $container) {
            if (\interface_exists('\OCP\Privacy\IManager')) {
                $container->get(\OCP\Privacy\IManager::class)->registerProvider(Register::class);
            }
        });
    }
}

}
