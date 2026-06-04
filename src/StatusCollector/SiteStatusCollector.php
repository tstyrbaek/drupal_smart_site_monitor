<?php

namespace Drupal\smart_site_monitor\StatusCollector;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;

class SiteStatusCollector implements StatusCollectorInterface {

  public function __construct(
    protected StateInterface $state,
    protected ConfigFactoryInterface $configFactory,
  ) {}

  public function collect(): array {
    $maintenance_mode = (bool) $this->state->get('system.maintenance_mode', FALSE);
    $site_name = (string) $this->configFactory->get('system.site')->get('name');

    return [
      'site_status' => [
        'site_name' => $site_name,
        'is_online' => !$maintenance_mode,
        'is_offline' => $maintenance_mode,
        'maintenance_mode' => $maintenance_mode,
      ],
    ];
  }

}
