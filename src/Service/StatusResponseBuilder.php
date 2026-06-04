<?php

namespace Drupal\smart_site_monitor\Service;

use Drupal\smart_site_monitor\StatusCollector\StatusCollectorInterface;

class StatusResponseBuilder {

  protected array $collectors = [];

  public function addCollector(StatusCollectorInterface $collector): void {
    $this->collectors[] = $collector;
  }

  public function build(): array {
    $data = [];

    foreach ($this->collectors as $collector) {
      $data = array_merge($data, $collector->collect());
    }

    $data['generated_at'] = gmdate('c');

    return $data;
  }

}
