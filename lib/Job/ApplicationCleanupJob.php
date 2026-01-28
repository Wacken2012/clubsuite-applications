<?php
namespace OCA\ClubSuiteApplications\Job;

use OCP\BackgroundJob\TimedJob;

class ApplicationCleanupJob extends TimedJob {
    public function __construct() { parent::__construct(); }
    public function run($argument) {
        // placeholder: archive old applications, clean history
    }
}
