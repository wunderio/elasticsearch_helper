<?php

namespace Drupal\elasticsearch_helper_views\EventSubscriber;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\elasticsearch_helper_views\Ajax\ElasticsearchDebugCommand;
use Drupal\elasticsearch_helper_views\Plugin\views\query\Elasticsearch;
use Drupal\views\Ajax\ViewAjaxResponse;
use Drupal\views\ViewExecutable;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class AjaxResponseSubscriber
 */
class AjaxResponseSubscriber implements EventSubscriberInterface {

  /** @var \Drupal\Core\Session\AccountInterface $currentUser */
  protected $currentUser;

  /** @var \Drupal\Core\Config\ConfigFactoryInterface $configFactory */
  protected $configFactory;

  /**
   * AjaxResponseSubscriber constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(AccountInterface $current_user, ConfigFactoryInterface $config_factory) {
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::RESPONSE => 'onResponse'];
  }

  /**
   * Prints Elasticsearch query to console for debugging.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   */
  public function onResponse(FilterResponseEvent $event) {
    // Do nothing if use does not have permission to administer views.
    if (!$this->currentUser->hasPermission('administer views')) {
      return;
    }

    // Do nothing if "Show SQL query" setting is disabled.
    if (!$this->configFactory->get('views.settings')->get('ui.show.sql_query.enabled')) {
      return;
    }

    // Get response.
    $response = $event->getResponse();

    if ($response instanceof ViewAjaxResponse) {
      // Get view.
      $view = $response->getView();

      if ($view->ajaxEnabled() && $queryHandler = $view->getQuery()) {
        if ($queryHandler instanceof Elasticsearch) {
          $this->addDebugCommand($response, $view);
        }
      }
    }
  }

  /**
   * Adds debug command to response.
   */
  protected function addDebugCommand(AjaxResponse $response, ViewExecutable $view) {
    $response->addAttachments(['library' => ['elasticsearch_helper_views/debug']]);
    $response->addCommand(new ElasticsearchDebugCommand($view->build_info['query']));
  }

}
