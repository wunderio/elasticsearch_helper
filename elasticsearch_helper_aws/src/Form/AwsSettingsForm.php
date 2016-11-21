<?php

namespace Drupal\elasticsearch_helper_aws\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AWSSettingsForm.
 *
 * @package Drupal\elasticsearch_helper_aws\Form
 */
class AWSSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'elasticsearch_helper_aws.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'elasticsearch_helper_aws_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('elasticsearch_helper_aws.settings');

    $form['region'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Region'),
      '#default_value' => $config->get('region'),
      '#description' => $this->t('The AWS region, for example <em>us-east-1</em>'),
      '#size' => 32,
    ];

    $form['access_key_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('access key ID'),
      '#default_value' => $config->get('access_key_id'),
      '#size' => 32,
    ];

    $form['secret_access_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('secret access key'),
      '#default_value' => $config->get('secret_access_key'),
      '#size' => 32,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('elasticsearch_helper_aws.settings')
      ->set('region', $form_state->getValue('region'))
      ->set('access_key_id', $form_state->getValue('access_key_id'))
      ->set('secret_access_key', $form_state->getValue('secret_access_key'))
      ->save();
  }
}
