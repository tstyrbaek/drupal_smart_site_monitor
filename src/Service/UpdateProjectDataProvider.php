<?php

namespace Drupal\smart_site_monitor\Service;

use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;

/**
 * Provides freshly calculated Drupal update project data.
 */
class UpdateProjectDataProvider {

  protected ?array $projectData = NULL;

  public function __construct(
    protected KeyValueExpirableFactoryInterface $keyValueExpirableFactory,
  ) {}

  /**
   * Returns update status per project, recalculated on each request.
   *
   * Drupal caches this data for up to an hour in the update key-value store.
   * Admin pages such as admin/reports/updates invalidate that cache on every
   * visit, but other routes (including this module's API) do not.
   */
  public function getProjectData(): array {
    if ($this->projectData !== NULL) {
      return $this->projectData;
    }

    $this->keyValueExpirableFactory->get('update')->delete('update_project_data');

    $available = update_get_available(TRUE);
    if (empty($available)) {
      $this->projectData = [];
      return $this->projectData;
    }

    $this->projectData = update_calculate_project_data($available);
    return $this->projectData;
  }

}
