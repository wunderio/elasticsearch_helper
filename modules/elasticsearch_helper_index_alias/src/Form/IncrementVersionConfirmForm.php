<?php

namespace Drupal\elasticsearch_helper_index_alias\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\elasticsearch_helper_index_alias\AliasService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class IncrementVersionConfirmForm.
 *
 * Ask the user to confirm that version number should be incremented.
 */
class IncrementVersionConfirmForm extends ConfirmFormBase {

  /**
   * Redirect route after form actions.
   */
  protected const REDIRECT_ROUTE = 'elasticsearch_helper_index_alias.manage_alias_controller.indices_status';

  /**
   * Alias service definition.
   *
   * @var \Drupal\elasticsearch_helper_index_alias\AliasService
   */
  protected $aliasService;

  /**
   * Constructs a new DeleteIndexConfirmForm object.
   */
  public function __construct(AliasService $alias_service) {
    $this->aliasService = $alias_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('elasticsearch_helper_index_alias.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->aliasService->incrementVersion();

    $form_state->setRedirect('elasticsearch_helper_index_alias.manage_alias_controller.aliases');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "elasticsearch_helper_index_alias_update_form";
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('elasticsearch_helper_index_alias.manage_alias_controller.aliases');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Increment the index version?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Note: Run index setup drush command and export configuration after version is incremented');
  }

}
