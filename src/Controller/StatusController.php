<?php

namespace Drupal\smart_site_monitor\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\smart_site_monitor\Service\StatusResponseBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class StatusController implements ContainerInjectionInterface {

  public function __construct(
    protected StatusResponseBuilder $statusResponseBuilder,
    protected ConfigFactoryInterface $configFactory,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('smart_site_monitor.status_response_builder'),
      $container->get('config.factory'),
    );
  }

  public function status(Request $request): JsonResponse {
    $configured_token = (string) $this->configFactory->get('smart_site_monitor.settings')->get('api_token');
    if ($configured_token === '') {
      return new JsonResponse(['message' => 'Unauthorized'], 401, ['Cache-Control' => 'no-store']);
    }

    $provided_token = $this->getBearerToken($request);
    if ($provided_token === '' || !hash_equals($configured_token, $provided_token)) {
      return new JsonResponse(['message' => 'Unauthorized'], 401, ['Cache-Control' => 'no-store']);
    }

    return new JsonResponse($this->statusResponseBuilder->build(), 200, ['Cache-Control' => 'no-store']);
  }

  protected function getBearerToken(Request $request): string {
    $attribute_token = (string) ($request->attributes->get('smart_site_monitor_bearer_token') ?? '');
    if ($attribute_token !== '') {
      return $attribute_token;
    }

    $authorization = (string) ($request->headers->get('Authorization') ?? '');
    if ($authorization === '') {
      $authorization = (string) ($request->server->get('HTTP_AUTHORIZATION') ?? '');
    }

    if (preg_match('/^Bearer\s+(.*)$/i', trim($authorization), $matches) !== 1) {
      return '';
    }

    return trim($matches[1]);
  }

}
