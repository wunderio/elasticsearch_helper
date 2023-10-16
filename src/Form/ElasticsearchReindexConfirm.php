<?php

namespace Drupal\elasticsearch_helper\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager;

/**
 * Provides a confirmation form for re-indexing content.
 *
 * @internal
 */
class ElasticsearchReindexConfirm extends ConfirmFormBase {

  /**
   * The temp store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager
   */
  protected $elasticsearchPluginManager;

  /**
   * Constructs a new UserMultipleCancelConfirm.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The temp store factory.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager $manager
   *   The Elasticsearch index plugin manager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, ElasticsearchIndexManager $manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->elasticsearchPluginManager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.elasticsearch_index.processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'elasticsearch_reindex_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('You are about to re-index content items?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('system.admin_content');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Confirm');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\node\NodeInterfacep[] $nodes */
    $nodes = $this->tempStoreFactory
      ->get('content_operations_reindex')
      ->get($this->currentUser()->id());
    if (!$nodes) {
      return $this->redirect('system.admin_content');
    }

    $names = [];
    $form['nodes'] = ['#tree' => TRUE];
    foreach ($nodes as $node) {
      $nid = $node->id();
      $names[$nid] = $node->label();

      $form['nodes'][$nid] = [
        '#type' => 'hidden',
        '#value' => $nid,
      ];
    }

    $hidden = [];
    $count = count($names);
    if (count($names) > 10) {
      $names = array_slice($names, 0, 10);
      $more = $count - 10;
      $hidden['hidden'] = $this->t('And @count more items ...', ['@count' => $more]);
    }

    $form['nodes']['names'] = [
      '#theme' => 'item_list',
      '#items' => $names + $hidden,
    ];

    $methods = [
      'default' => $this->t('Re-index all at once'),
      'queue' => $this->t('Add to queue'),
      'batch' => $this->t('Re-index now using batch'),
    ];

    $form['reindex_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Re-index method'),
      '#options' => $methods,
      '#required' => TRUE,
    ];

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user_id = $this->currentUser()->id();

    // Clear out the nodes from the temp store.
    $this->tempStoreFactory->get('content_operations_reindex')->delete($current_user_id);
    if ($form_state->getValue('confirm')) {
      $method = $form_state->getValue('reindex_method');
      switch ($method) {
        case 'queue':
          foreach ($form_state->getValue('nodes') as $nid => $value) {
            $this->elasticsearchPluginManager->addToQueue('node', $nid);
          }
          $message = $this->formatPlural(
            count($form_state->getValue('nodes')),
            '@count node added to queue for re-indexing.',
            '@count nodes added to queue for re-indexing.'
          );
          $this->messenger()->addStatus($message);

          break;

        case 'batch':
          $nids = $form_state->getValue('nodes');
          if (!empty($nids)) {
            $operations = [
              [[$this, '_reindex_content'], [$nids]],
            ];
            $batch = [
              'title' => $this->t('Re-indexing content ...'),
              'operations' => $operations,
              'finished' => [$this, '_reindex_content_finished'],
            ];
            batch_set($batch);
          }

          break;

        default:
          $storage = $this->entityTypeManager->getStorage('node');
          $entities = $storage->loadMultiple($form_state->getValue('nodes'));
          foreach ($entities as $entity) {
            $this->elasticsearchPluginManager->indexEntity($entity);
          }
          $message = $this->formatPlural(
            count($form_state->getValue('nodes')),
            'Performed re-index action on @count document.',
            'Performed re-index action on @count documents.'
          );
          $this->messenger()->addStatus($message);

          break;
      }

    }
    $form_state->setRedirect('system.admin_content');
  }

  /**
   *
   */
  public function _reindex_content($ids, &$context) {
    $context['message'] = 'Re-indexing ...';
    $results = [];
    $storage = $this->entityTypeManager->getStorage('node');
    foreach ($ids as $id) {
      $entity = $storage->load($id);
      $this->elasticsearchPluginManager->indexEntity($entity);
      $results[] = $id;
      $context['sandbox']['progress']++;
      $context['message'] = $this->t('Re-indexing @count of @total...', ['@count' => $context['sandbox']['progress'], '@total' => $context['sandbox']['max']]);
    }
    $context['results'] = $results;
  }

  /**
   *
   */
  public function _reindex_content_finished($success, $results, $operations) {
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One node re-indexed.', '@count nodes re-indexed.'
      );
    }
    else {
      $message = t('Finished with an error.');
    }
    $this->messenger()->addStatus($message);
  }

}
