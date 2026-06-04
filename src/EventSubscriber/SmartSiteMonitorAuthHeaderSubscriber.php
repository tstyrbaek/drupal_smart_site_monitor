<?php

namespace Drupal\smart_site_monitor\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SmartSiteMonitorAuthHeaderSubscriber implements EventSubscriberInterface {

  public function __construct(protected ConfigFactoryInterface $configFactory) {}

  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => ['onRequest', 1000],
    ];
  }

  public function onRequest(RequestEvent $event): void {
    $request = $event->getRequest();
    $path = $request->getPathInfo();

    if (!str_starts_with($path, '/smart-site-monitor/status')) {
      return;
    }

    $authorization = (string) ($request->headers->get('Authorization') ?? '');
    if ($authorization === '') {
      $authorization = (string) ($request->server->get('HTTP_AUTHORIZATION') ?? '');
    }

    $token = '';
    if ($authorization !== '' && preg_match('/^Bearer\s+(.*)$/i', trim($authorization), $matches) === 1) {
      $token = trim($matches[1]);
    }

    $request->attributes->set('smart_site_monitor_bearer_token', $token);

    $request->headers->remove('Authorization');
    $request->server->remove('HTTP_AUTHORIZATION');
  }

}
