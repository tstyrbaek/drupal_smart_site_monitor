<?php

declare(strict_types=1);

namespace Drupal\Tests\smart_site_monitor\Unit;

use Drupal\Core\State\StateInterface;
use Drupal\smart_site_monitor\Service\UpdateProjectDataProvider;
use Drupal\smart_site_monitor\StatusCollector\DrupalUpdateCollector;
use Drupal\smart_site_monitor\StatusCollector\ModulesStatusCollector;
use Drupal\Tests\UnitTestCase;
use Drupal\update\UpdateManagerInterface;

final class DrupalUpdateCollectorTest extends UnitTestCase {

  public function testCurrentCoreDoesNotReportSecurityUpdates(): void {
    $provider = $this->createMock(UpdateProjectDataProvider::class);
    $state = $this->createMock(StateInterface::class);

    $collector = new class($provider, $state) extends DrupalUpdateCollector {

      public function countSecurityUpdatesForTest(?int $status, array $drupal_project): int {
        return $this->countSecurityUpdates($status, $drupal_project);
      }

    };

    $this->assertSame(0, $collector->countSecurityUpdatesForTest(UpdateManagerInterface::CURRENT, [
      'security updates' => [
        [
          'version' => '11.3.14',
        ],
      ],
    ]));
  }

  public function testNonCurrentCoreCountsSecurityUpdates(): void {
    $provider = $this->createMock(UpdateProjectDataProvider::class);
    $state = $this->createMock(StateInterface::class);

    $collector = new class($provider, $state) extends DrupalUpdateCollector {

      public function countSecurityUpdatesForTest(?int $status, array $drupal_project): int {
        return $this->countSecurityUpdates($status, $drupal_project);
      }

    };

    $this->assertSame(1, $collector->countSecurityUpdatesForTest(UpdateManagerInterface::NOT_CURRENT, [
      'security updates' => [
        [
          'version' => '11.3.14',
        ],
      ],
    ]));
  }

  public function testCurrentModuleDoesNotReportSecurityUpdates(): void {
    $update_manager = $this->createMock(\Drupal\update\UpdateManagerInterface::class);
    $provider = $this->createMock(UpdateProjectDataProvider::class);
    $state = $this->createMock(StateInterface::class);

    $collector = new class($update_manager, $provider, $state) extends ModulesStatusCollector {

      public function hasSecurityUpdateForTest(array $status_data): bool {
        return $this->hasSecurityUpdate($status_data);
      }

    };

    $this->assertFalse($collector->hasSecurityUpdateForTest([
      'status' => UpdateManagerInterface::CURRENT,
      'security updates' => [
        [
          'version' => '1.0.1',
        ],
      ],
    ]));
  }

}
