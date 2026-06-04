<?php

namespace Drupal\smart_site_monitor\StatusCollector;

use Drupal\Core\State\StateInterface;
use Drupal\smart_site_monitor\Service\UpdateProjectDataProvider;
use Drupal\update\UpdateManagerInterface;

class ModulesStatusCollector implements StatusCollectorInterface {

  public function __construct(
    protected UpdateManagerInterface $updateManager,
    protected UpdateProjectDataProvider $updateProjectDataProvider,
    protected StateInterface $state,
  ) {}

  public function collect(): array {
    $projects = $this->updateManager->getProjects();
    $project_data = $this->updateProjectDataProvider->getProjectData();
    $available_releases = \Drupal::keyValueExpirable('update_available_releases')->getAll();

    $modules = [];
    $updates_available = 0;
    $security_updates = 0;
    $last_checked_timestamp = NULL;

    foreach ($projects as $project_name => $project) {
      if (($project['project_type'] ?? NULL) !== 'module') {
        continue;
      }

      $status_data = $project_data[$project_name] ?? [];
      $release_data = $available_releases[$project_name] ?? [];
      $last_fetch = $release_data['last_fetch'] ?? NULL;
      if (is_numeric($last_fetch)) {
        $last_fetch = (int) $last_fetch;
        $last_checked_timestamp = $last_checked_timestamp === NULL ? $last_fetch : max($last_checked_timestamp, $last_fetch);
      }
      $current_version = (string) ($project['info']['version'] ?? $status_data['existing_version'] ?? '');
      $latest_version = (string) ($status_data['recommended'] ?? $status_data['latest_version'] ?? $current_version);
      $has_security_update = !empty($status_data['security updates']) || (($status_data['status'] ?? NULL) === UpdateManagerInterface::NOT_SECURE);
      $has_update = $has_security_update || in_array($status_data['status'] ?? NULL, [
        UpdateManagerInterface::NOT_CURRENT,
        UpdateManagerInterface::NOT_SUPPORTED,
        UpdateManagerInterface::REVOKED,
      ], TRUE) || ($latest_version !== '' && $latest_version !== $current_version);

      $included_modules = array_keys($project['includes'] ?? []);
      if (empty($included_modules)) {
        $included_modules = [$project_name];
      }

      foreach ($included_modules as $module_name) {
        if ($has_update) {
          $updates_available++;
        }

        if ($has_security_update) {
          $security_updates++;
        }

        $modules[] = [
          'name' => $module_name,
          'current' => $current_version,
          'latest' => $latest_version,
          'security' => $has_security_update,
        ];
      }
    }

    usort($modules, static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));
    if ($last_checked_timestamp === NULL) {
      $state_last_check = $this->state->get('update.last_check');
      $last_checked_timestamp = is_numeric($state_last_check) ? (int) $state_last_check : NULL;
    }
    $last_checked_iso8601 = $last_checked_timestamp ? gmdate('c', $last_checked_timestamp) : NULL;

    return [
      'module_summary' => [
        'total_modules' => count($modules),
        'updates_available' => $updates_available,
        'security_updates' => $security_updates,
        'last_checked_timestamp' => $last_checked_timestamp,
        'last_checked_iso8601' => $last_checked_iso8601,
      ],
      'modules' => $modules,
    ];
  }

}
