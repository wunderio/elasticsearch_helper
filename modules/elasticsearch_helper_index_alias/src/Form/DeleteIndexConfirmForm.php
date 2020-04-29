<?php

namespace Drupal\elasticsearch_helper_index_alias\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Elasticsearch\Client;

/**
 * Class DeleteIndexConfirmForm.
 *
 * Ask the user for confirmation before dropping index.
 */
class DeleteIndexConfirmForm extends ConfirmFormBase {

  /**
   * Redirect route after form actions.
   */
  protected const REDIRECT_ROUTE = 'elasticsearch_helper_index_alias.manage_alias_controller.indices_status';

  /**
   * Elasticsearch\Client definition.
   *
   * @var \Elasticsearch\Client
   */
  protected $elasticsearchHelperElasticsearchClient;

  /**
   * Constructs a new DeleteIndexConfirmForm object.
   */
  public function __construct(
    Client $elasticsearch_helper_elasticsearch_client
  ) {
    $this->elasticsearchHelperElasticsearchClient = $elasticsearch_helper_elasticsearch_client;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $index = NULL) {
    $this->index = $index;

    $form_state->set('index', $index);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('elasticsearch_helper.elasticsearch_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $index_name = $form_state->get('index');

    if (!empty($index_name)) {
      /** @var \Elasticsearch\Client $client */
      $client = \Drupal::service('elasticsearch_helper.elasticsearch_client');

      $client->indices()->delete(['index' => $index_name]);

      \Drupal::messenger()->addMessage(t('Index deleted: @index', ['@index' => $index_name]), 'status');
    }

    $form_state->setRedirect(self::REDIRECT_ROUTE);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "elasticsearch_helper_delete_confirm_form";
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url(self::REDIRECT_ROUTE);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Confirm to drop index: @index?', ['@index' => $this->index]);
  }

}
