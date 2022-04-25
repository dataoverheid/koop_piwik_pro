<?php

declare(strict_types = 1);

namespace Drupal\koop_piwik_pro\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Piwik PRO Settings Form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['koop_piwik_pro.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'koop_piwik_pro_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('koop_piwik_pro.settings');

    $form['site'] = [
      '#type' => 'details',
      '#title' => $this->t('Site variables'),
      '#open' => TRUE,
      '#required' => TRUE,
    ];

    $form['site']['site_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site name'),
      '#default_value' => $config->get('site_name'),
      '#required' => TRUE,
    ];

    $form['site']['site_environment'] = [
      '#type' => 'select',
      '#title' => $this->t('DTAP Environment'),
      '#options' => [
        'test' => $this->t('Testing', [], ['context' => 'DTAP']),
        'acceptance' => $this->t('Acceptance', [], ['context' => 'DTAP']),
        'production' => $this->t('Production', [], ['context' => 'DTAP']),
      ],
      '#default_value' => $config->get('site_environment'),
      '#required' => TRUE,
    ];

    $form['piwik_pro'] = [
      '#type' => 'details',
      '#title' => $this->t('Piwik PRO variables'),
      '#open' => TRUE,
      '#required' => TRUE,
    ];

    $form['piwik_pro']['domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Domain'),
      '#default_value' => $config->get('domain') ?? 'https://koop.piwik.pro/containers/',
      '#required' => TRUE,
    ];

    $form['piwik_pro']['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Piwik ID'),
      '#default_value' => $config->get('id'),
      '#required' => TRUE,
    ];

    $form['piwik_pro']['dataLayerName'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Data layer name'),
      '#default_value' => $config->get('dataLayerName') ?? 'dataLayer',
      '#required' => TRUE,
    ];

    $form['piwik_pro']['dataLayerEnabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable DataLayers'),
      '#default_value' => $config->get('dataLayerEnabled'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('koop_piwik_pro.settings');
    $config->set('site_name', $form_state->getValue('site_name'));
    $config->set('site_environment', $form_state->getValue('site_environment'));
    $domain = $form_state->getValue('domain');
    if (substr($domain, 0, -1) !== '/') {
      $domain .= '/';
    }
    $config->set('domain', $domain);
    $config->set('id', $form_state->getValue('id'));
    $config->set('dataLayerName', $form_state->getValue('dataLayerName'));
    $config->set('dataLayerEnabled', $form_state->getValue('dataLayerEnabled'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
