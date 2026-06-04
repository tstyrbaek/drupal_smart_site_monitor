<?php

namespace Drupal\smart_site_monitor\StatusCollector;

interface StatusCollectorInterface {

  public function collect(): array;

}
