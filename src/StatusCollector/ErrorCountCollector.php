<?php

namespace Drupal\smart_site_monitor\StatusCollector;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\RfcLogLevel;

class ErrorCountCollector implements StatusCollectorInterface {

  public function __construct(
    protected Connection $database,
    protected ModuleHandlerInterface $moduleHandler,
    protected TimeInterface $time,
  ) {}

  public function collect(): array {
    if (!$this->moduleHandler->moduleExists('dblog') || !$this->database->schema()->tableExists('watchdog')) {
      return [
        'errors' => [
          'last_24h' => NULL,
          'source' => 'dblog',
          'available' => FALSE,
        ],
      ];
    }

    $since = $this->time->getCurrentTime() - 86400;

    $count = (int) $this->database->select('watchdog', 'w')
      ->condition('severity', RfcLogLevel::ERROR, '<=')
      ->condition('timestamp', $since, '>=')
      ->countQuery()
      ->execute()
      ->fetchField();

    return [
      'errors' => [
        'last_24h' => $count,
        'source' => 'dblog',
        'available' => TRUE,
      ],
    ];
  }

}
