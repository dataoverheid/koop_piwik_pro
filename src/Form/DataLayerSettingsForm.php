<?php

declare(strict_types = 1);

namespace Drupal\koop_piwik_pro\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Piwik PRO DataLayer Settings Form.
 */
class DataLayerSettingsForm extends FormBase {

  /**
   * The database connection.
   */
  protected Connection $connection;

  /**
   * Constructs a PiwikDataLayerForm object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'koop_piwik_pro_datalayer_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $dataLayer = $this->connection->select('koop_piwik_pro_datalayer', 'd')
      ->fields('d', ['route', 'handler', 'page_type'])
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);

    $dataLayerCount = $form_state->get('dataLayerCount');
    if ($dataLayerCount === NULL) {
      $dataLayerCount = \count($dataLayer) + 1;
      $form_state->set('dataLayerCount', $dataLayerCount);
    }

    $form['dataLayer'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#prefix' => '<div id="dataLayer-wrapper"><table><thead><tr><th>' . $this->t('Route') . '</th><th>' . $this->t('Handler') . '</th><th>' . $this->t('Page type') . '</th></tr></thead>',
      '#suffix' => '</table></div>',
    ];

    for ($i = 0; $i < $dataLayerCount; $i++) {
      $form['dataLayer'][$i]['route'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Route'),
        '#title_display' => 'invisible',
        '#default_value' => $dataLayer[$i]['route'] ?? NULL,
        '#prefix' => '<tr><td>',
        '#suffix' => '</td>',
      ];

      $form['dataLayer'][$i]['handler'] = [
        '#type' => 'select',
        '#title' => $this->t('Handler'),
        '#title_display' => 'invisible',
        '#options' => [
          'default' => $this->t('Default handler'),
          'search' => $this->t('Search page'),
        ],
        '#default_value' => $dataLayer[$i]['handler'] ?? NULL,
        '#prefix' => '<td>',
        '#suffix' => '</td>',
      ];

      $form['dataLayer'][$i]['page_type'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Page type'),
        '#title_display' => 'invisible',
        '#default_value' => $dataLayer[$i]['page_type'] ?? NULL,
        '#size' => 40,
        '#prefix' => '<td>',
        '#suffix' => '</td></tr>',
      ];
    }

    $form['row']['#type'] = 'actions';
    $form['row']['add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another row'),
      '#submit' => ['::addOne'],
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'dataLayer-wrapper',
      ],
      '#limit_validation_errors' => [],
    ];
    $form['row']['help'] = [
      '#title' => $this->t('Show available routes'),
      '#type' => 'link',
      '#url' => Url::fromRoute('koop_piwik_pro.list_all_routes'),
      '#attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-renderer' => 'off_canvas',
        'data-dialog-type' => 'dialog',
      ],
      '#attached' => [
        'library' => [
          'core/drupal.dialog.ajax',
        ],
      ],
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    foreach ($form_state->cleanValues()->getValue('dataLayer', []) as $i => $value) {
      $count = 0;
      if (empty($value['route'])) {
        $count++;
      }
      if (empty($value['page_type'])) {
        $count++;
      }
      if ($count > 0 && $count < 2) {
        $form_state->setErrorByName('dataLayer][' . $i, 'The row must be empty or all fields must be filled in.');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->connection->truncate('koop_piwik_pro_datalayer')->execute();

    foreach ($form_state->cleanValues()->getValue('dataLayer', []) as $value) {
      if (!empty($value['route']) && !empty($value['page_type'])) {
        $this->connection->insert('koop_piwik_pro_datalayer')
          ->fields([
            'route' => $value['route'],
            'handler' => $value['handler'],
            'page_type' => $value['page_type'],
          ])
          ->execute();
      }
    }
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOne(array &$form, FormStateInterface $form_state): void {
    $form_state->set('dataLayerCount', $form_state->get('dataLayerCount') + 1);
    $form_state->setRebuild();
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   *
   * @return array
   *   The form structure.
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state): array {
    return $form['dataLayer'];
  }

}
