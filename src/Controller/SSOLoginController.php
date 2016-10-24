<?php

namespace Drupal\openid_fb\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SSOLoginController.
 *
 * @package Drupal\openid_fb\Controller
 */
class SSOLoginController extends ControllerBase {

  /**
   * Drupal\Core\Form\FormBuilder definition.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(FormBuilder $form_builder) {
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * Login page.
   *
   * @return string
   *   Return Hello string.
   */
  public function loginPage() {
    if (\Drupal::currentUser()->isAnonymous()) {
      return $this->formBuilder->getForm('Drupal\openid_connect\Form\LoginForm');
    }
    else {
      return $this->redirect('<front>');
    }
  }

}
