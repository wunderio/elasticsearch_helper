<?php

namespace Drupal\elasticsearch_helper_instant\Controller;

use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\elasticsearch_helper_instant\ElasticsearchInstantSearchService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class InstantSearchResults.
 */
class InstantSearchResults extends ControllerBase {

  /**
   * The request_stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The elasticsearch_helper_instant.search service.
   *
   * @var \Drupal\elasticsearch_helper_instant\ElasticsearchInstantSearchService
   */
  protected $search;

  /**
   * The page_cache_kill_switch service.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $pageCacheKillSwitch;

  /**
   * Constructs a new InstantSearchResults object.
   */
  public function __construct(
    RequestStack $request_stack,
    ElasticsearchInstantSearchService $search,
    KillSwitch $page_cache_kill_switch
  ) {
    $this->requestStack = $request_stack;
    $this->search = $search;
    $this->pageCacheKillSwitch = $page_cache_kill_switch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('elasticsearch_helper_instant.search'),
      $container->get('page_cache_kill_switch')
    );
  }

  /**
   * Return results for given search keyword.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Return a response object with the output.
   */
  public function retrieveResults() {
    $query = $this->requestStack->getCurrentRequest()->query;
    $options = [
      'rendermode' => $query->get('rendermode'),
      'format' => $query->get('format'),
      'debug' => $query->get('debug'),
    ];

    // Do the query.
    $result = $this->search->query($query->get('searchphrase'));

    // Prepare output.
    $output = $this->search->render($result, $options);

    // Prepare response.
    if (is_array($output)) {
      $response = new JsonResponse($output);
      return $response;
    }

    $response = new Response();
    $response->setContent($output);
    $this->pageCacheKillSwitch->trigger();
    return $response;
  }

}
