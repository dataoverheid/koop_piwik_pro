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
      '#title' => $this->t('Datalayer name'),
      '#default_value' => $config->get('dataLayerName') ?? 'dataLayer',
      '#required' => TRUE,
    ];

    $form['piwik_pro']['dataLayerEnabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable DataLayers'),
      '#default_value' => $config->get('dataLayerEnabled') ?? FALSE,
    ];

    $form['visibility'] = [
      '#type' => 'details',
      '#title' => $this->t('What pages to track'),
      '#open' => TRUE,
      '#required' => TRUE,
    ];

    $form['visibility']['visibility_disable_admin_pages'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable on admin pages.'),
      '#default_value' => $config->get('visibility_disable_admin_pages') ?? FALSE,
    ];

    $form['visibility']['visibility_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Add tracking to specific pages'),
      '#options' => [
        0 => $this->t('Every page except the listed pages'),
        1 => $this->t('The listed pages only'),
      ],
      '#default_value' => $config->get('visibility_mode') ?? 0,
    ];

    $visibilityPages = $config->get('visibility_pages');
    $form['visibility']['visibility_pages'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Pages'),
      '#title_display' => 'invisible',
      '#default_value' => !empty($visibilityPages) ? $visibilityPages : '',
      '#description' => $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", ['%blog' => '/blog', '%blog-wildcard' => '/blog/*', '%front' => '<front>']),
      '#rows' => 10,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Validate URL.
    $value = rtrim($form_state->getValue('domain'), '/');
    if (!filter_var( $value, FILTER_VALIDATE_URL)) {
      $form_state->setErrorByName('domain', $this->t('Invalid Piwik PRO Domain.'));
    }

    // Validate Site ID.
    $value = strtolower($form_state->getValue('id'));
    if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $value ) !== 1) {
      $form_state->setErrorByName('id', $this->t('Invalid Piwik PRO ID.'));
    }

    // Validate visibility pages.
    if ($visibilityPages = trim($form_state->getValue('visibility_pages'))) {
      $form_state->setValue('visibility_pages', $visibilityPages);

      // Verify that every path is prefixed with a slash.
      $wrongPages = [];
      foreach (preg_split('/(\r\n?|\n)/', $visibilityPages) as $page) {
        if (!str_starts_with($page, '/') && $page !== '<front>') {
          $wrongPages[] = $page;
        }
      }
      if ($wrongPages) {
        $form_state->setErrorByName('visibility_pages', $this->formatPlural(
          count($wrongPages),
          'The path @pages is not prefixed with a slash.',
          'The paths @pages are not prefixed with a slash.',
          ['@page' => implode(', ', $wrongPages)]
        ));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('koop_piwik_pro.settings');
    $config->set('site_name', $form_state->getValue('site_name'));
    $config->set('site_environment', $form_state->getValue('site_environment'));
    $domain = $form_state->getValue('domain');
    if (!str_ends_with($domain, '/')) {
      $domain .= '/';
    }
    $config->set('domain', $domain);
    $config->set('id', $form_state->getValue('id'));
    $config->set('dataLayerName', $form_state->getValue('dataLayerName'));
    $config->set('dataLayerEnabled', $form_state->getValue('dataLayerEnabled'));
    $config->set('visibility_disable_admin_pages', $form_state->getValue('visibility_disable_admin_pages'));
    $config->set('visibility_mode', $form_state->getValue('visibility_mode'));
    $config->set('visibility_pages', $form_state->getValue('visibility_pages'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
