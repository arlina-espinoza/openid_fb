<?php

namespace Drupal\openid_fb\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Routing\RedirectDestination;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class Redirect403Subscriber.
 *
 * @package Drupal\openid_fb
 */
class Redirect403Subscriber implements EventSubscriberInterface {

  /**
   * Drupal\Core\Routing\RedirectDestination definition.
   *
   * @var \Drupal\Core\Routing\RedirectDestination
   */
  protected $redirectDestination;

  /**
   * Constructor.
   */
  public function __construct(RedirectDestination $redirect_destination) {
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION] = ['onKernelException'];

    return $events;
  }

  /**
   * This method is called whenever the kernel.exception event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function onKernelException(Event $event) {
    $exception = $event->getException();

    // Only do something for 403 exceptions.
    if (!($exception instanceof AccessDeniedHttpException)) {
      return;
    }

    $options['query'] = $this->redirectDestination->getAsArray();
    $options['absolute'] = TRUE;

    if (\Drupal::currentUser()->isAnonymous()) {
      // Handle redirection to the login page.
      $url = Url::fromRoute('openid_fb.sso_login_controller_loginPage', array(), $options)->toString();
      $response = new RedirectResponse($url, 302);
      $event->setResponse($response);
    }
  }
}
