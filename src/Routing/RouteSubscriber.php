<?php

namespace Drupal\openid_fb\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\openid_fb\Routing
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {

    // Change path '/user/login' to '/non-sso-login'.
    if ($route = $collection->get('user.login')) {
      $route->setPath('/non-sso-login');
    }
  }
}
