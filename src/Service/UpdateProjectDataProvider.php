<?php

namespace Drupal\smart_site_monitor\Service;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\update\UpdateManager;
use Drupal\update\UpdateManagerInterface;

/**
 * Provides freshly calculated Drupal update project data.
 */
class UpdateProjectDataProvider {

  protected ?array $projectData = NULL;

  public function __construct(
    protected KeyValueExpirableFactoryInterface $keyValueExpirableFactory,
    protected ModuleHandlerInterface $moduleHandler,
    protected UpdateManagerInterface $updateManager,
  ) {}

  /**
   * Returns update status per project, recalculated on each request.
   *
   * Drupal caches installed project metadata (`update_project_projects`) and
   * calculated status (`update_project_data`) for up to an hour. Admin routes
   * such as Modules and Available updates clear that cache automatically; this
   * API does not, so after Composer/Drush upgrades the cached installed
   * versions stay stale until those pages are visited.
   *
   * We clear both caches (and the UpdateManager in-memory list) before
   * recalculating, so dashboard polls see the versions actually on disk.
   */
  public function getProjectData(): array {
    if ($this->projectData !== NULL) {
      return $this->projectData;
    }

    $storage = $this->keyValueExpirableFactory->get('update');
    $storage->delete('update_project_data');
    $storage->delete('update_project_projects');
    $this->resetUpdateManagerProjects();

    $this->moduleHandler->loadInclude('update', 'inc', 'update.compare');

    $available = update_get_available(TRUE);
    if (empty($available)) {
      $this->projectData = [];
      return $this->projectData;
    }

    $this->projectData = update_calculate_project_data($available);
    return $this->projectData;
  }

  /**
   * Clears UpdateManager's request-local project list so getProjects() rebuilds.
   */
  protected function resetUpdateManagerProjects(): void {
    if (!$this->updateManager instanceof UpdateManager) {
      return;
    }

    $reflection = new \ReflectionProperty(UpdateManager::class, 'projects');
    // Required on PHP < 8.1; no-op afterwards.
    $reflection->setAccessible(TRUE);
    $reflection->setValue($this->updateManager, []);
  }

}
