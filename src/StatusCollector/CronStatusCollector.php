<?php

namespace Drupal\smart_site_monitor\StatusCollector;

use Drupal\Core\State\StateInterface;

class CronStatusCollector implements StatusCollectorInterface {

  public function __construct(protected StateInterface $state) {}

  public function collect(): array {
    $last_run = $this->state->get('system.cron_last');

    return [
      'cron' => [
        'last_run_timestamp' => $last_run ? (int) $last_run : NULL,
        'last_run_iso8601' => $last_run ? gmdate('c', (int) $last_run) : NULL,
      ],
    ];
  }

}
