<?php

namespace Drupal\smart_site_monitor\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SmartSiteMonitorSettingsForm extends ConfigFormBase {

  public function getFormId(): string {
    return 'smart_site_monitor_settings_form';
  }

  protected function getEditableConfigNames(): array {
    return ['smart_site_monitor.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('smart_site_monitor.settings');

    $form['api_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API token'),
      '#default_value' => (string) $config->get('api_token'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->configFactory->getEditable('smart_site_monitor.settings')
      ->set('api_token', (string) $form_state->getValue('api_token'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
