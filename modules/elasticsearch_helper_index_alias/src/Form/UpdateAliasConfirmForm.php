<?php

namespace Drupal\elasticsearch_helper_index_alias\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\elasticsearch_helper_index_alias\AliasService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class UpdateAliasConfirmForm.
 *
 * Ask the user to confirm index alias updates.
 */
class UpdateAliasConfirmForm extends ConfirmFormBase {

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
    $this->aliasService->updateAll();

    $form_state->setRedirect('elasticsearch_helper_index_alias.elasticsearch_management_controller_aliases');
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
    return new Url('elasticsearch_helper_index_alias.elasticsearch_management_controller_aliases');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Confirm to update aliases to latest index version?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Warning: This will delete any index that matches the alias and will point the alias to the new index version! Make sure that the new index version contains all the documents! Press CANCEL now if you are not sure!');
  }

}
