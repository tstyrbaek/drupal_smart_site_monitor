<?php

namespace Drupal\smart_site_monitor\StatusCollector;

use Drupal\Core\State\StateInterface;
use Drupal\smart_site_monitor\Service\UpdateProjectDataProvider;
use Drupal\update\UpdateManagerInterface;

class DrupalUpdateCollector implements StatusCollectorInterface {

  public function __construct(
    protected UpdateProjectDataProvider $updateProjectDataProvider,
    protected StateInterface $state,
  ) {}

  public function collect(): array {
    $version = \Drupal::VERSION;
    $current_version = $version;
    $latest_version = $version;
    $update_status = NULL;
    $security_updates = NULL;
    $available = FALSE;
    $last_checked_timestamp = NULL;
    $last_checked_iso8601 = NULL;
    $project_data = $this->updateProjectDataProvider->getProjectData();
    $drupal_project = $project_data['drupal'] ?? NULL;

    if (is_array($drupal_project)) {
      $available = TRUE;
      $status = $drupal_project['status'] ?? NULL;
      $current_version = $version;
      $latest_version = (string) ($drupal_project['recommended'] ?? $drupal_project['latest_version'] ?? $current_version);
      $update_status = $this->mapStatus($status);
      $security_updates = $this->countSecurityUpdates($status, $drupal_project);
    }

    $available_releases = \Drupal::keyValueExpirable('update_available_releases')->get('drupal');
    $last_fetch = $available_releases['last_fetch'] ?? $this->state->get('update.last_check');
    $last_checked_timestamp = is_numeric($last_fetch) ? (int) $last_fetch : NULL;
    $last_checked_iso8601 = $last_checked_timestamp ? gmdate('c', $last_checked_timestamp) : NULL;

    return [
      'cms' => [
        'type' => 'Drupal',
        'version' => $version,
        'current' => $current_version,
        'latest' => $latest_version,
        'update_status' => $update_status,
        'security_updates' => $security_updates,
        'available' => $available,
        'last_checked_timestamp' => $last_checked_timestamp,
        'last_checked_iso8601' => $last_checked_iso8601,
      ],
    ];
  }

  protected function mapStatus(?int $status): ?string {
    return match ($status) {
      UpdateManagerInterface::CURRENT => 'current',
      UpdateManagerInterface::NOT_CURRENT => 'update_available',
      UpdateManagerInterface::NOT_SECURE => 'security_update_required',
      UpdateManagerInterface::NOT_SUPPORTED => 'not_supported',
      UpdateManagerInterface::REVOKED => 'revoked',
      default => NULL,
    };
  }

  /**
   * Counts security updates that are relevant for the installed core version.
   *
   * Drupal's raw update project data can still contain security releases even
   * when the installed version is already current. In that case we do not want
   * to report a non-zero count, because there is no actionable security update
   * for the site owner.
   */
  protected function countSecurityUpdates(?int $status, array $drupal_project): int {
    if ($status === UpdateManagerInterface::CURRENT) {
      return 0;
    }

    return isset($drupal_project['security updates']) && is_array($drupal_project['security updates'])
      ? count($drupal_project['security updates'])
      : 0;
  }

}
