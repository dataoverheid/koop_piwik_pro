<?php

declare(strict_types = 1);

namespace Drupal\koop_piwik_pro;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Service for getting the DataLayer values.
 */
class DataLayerService implements DataLayerServiceInterface {

  /**
   * The Piwik PRO config.
   */
  private ImmutableConfig $config;

  /**
   * The database connection.
   */
  private Connection $connection;

  /**
   * The current language.
   */
  private LanguageInterface $currentLanguage;

  /**
   * The current user.
   */
  private ?UserInterface $currentUser;

  /**
   * The session.
   */
  private SessionInterface $session;

  /**
   * The route match.
   */
  private RouteMatchInterface $routeMatch;

  /**
   * The page title.
   */
  private string $pageTitle;

  /**
   * Constructs a DataLayerService object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $accountProxy
   *   The account proxy.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   * @param \Drupal\Core\Controller\TitleResolverInterface $titleResolver
   *   The title resolver.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(AccountProxyInterface $accountProxy, ConfigFactoryInterface $configFactory, Connection $connection, EntityTypeManagerInterface $entityTypeManager, LanguageManagerInterface $languageManager, RequestStack $requestStack, RouteMatchInterface $routeMatch, TitleResolverInterface $titleResolver, RendererInterface $renderer) {
    $this->session = $requestStack->getCurrentRequest()->getSession();
    try {
      $this->currentUser = $entityTypeManager->getStorage('user')->load($accountProxy->id());
    }
    catch (\Exception $e) {
      $this->currentUser = NULL;
    }

    $this->connection = $connection;
    $this->config = $configFactory->get('koop_piwik_pro.settings');
    $this->currentLanguage = $languageManager->getCurrentLanguage();
    $this->routeMatch = $routeMatch;

    $this->pageTitle = '';
    if (($request = $requestStack->getCurrentRequest()) && ($route = $routeMatch->getRouteObject())) {
      $title = $titleResolver->getTitle($request, $route);
      $this->pageTitle = (string) (is_array($title) ? $renderer->renderPlain($title) : $title);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValues(): array {
    $settings = $this->getPageType();

    $values = [
      'site_name' => $this->config->get('site_name'),
      'site_env' => $this->config->get('site_environment'),
      'page_title' => $this->pageTitle,
      'page_type' => $settings['page_type'],
      'page_language' => $this->currentLanguage->getId(),
      'user_type' => $this->getUserType(),
    ];

    if ($settings['handler'] === 'search' && $searchValues = $this->session->get('koop_piwik_pro.search')) {
      $values['search_term'] = $searchValues['search_term'] ?? '';
      $values['search_page'] = $searchValues['search_page'] ?? 1;
      $values['search_results'] = $searchValues['search_results'] ?? 0;
      $values['search_filters'] = $searchValues['search_filters'] ?? '';
    }

    return $values;
  }

  /**
   * Get the user type.
   *
   * @return string
   *   The user type.
   */
  private function getUserType(): string {
    if ($this->currentUser && $this->currentUser->id() > 0) {
      if ($this->currentUser->id() === 1 || $this->currentUser->hasRole('administrator')) {
        return 'admin';
      }
      return 'user';
    }
    return 'anonymous';
  }

  /**
   * Get the page type.
   *
   * @return array
   *   Array containig the page type and handler.
   */
  private function getPageType(): array {
    $routeName = explode('.', $this->routeMatch->getRouteName());

    $query = $this->connection->select('koop_piwik_pro_datalayer', 'd');
    $query->fields('d', ['page_type', 'handler']);

    $route = '';
    $max = count($routeName) - 1;
    $or = $query->orConditionGroup();
    for ($i = 0; $i <= $max; $i++) {
      if ($i === 0) {
        $route = $routeName[0];
      }
      else {
        $route .= '.' . $routeName[$i];
      }

      if ($i < $max) {
        $or->condition('route', $route . '.*', '=');
      }
      else {
        $or->condition('route', $route, '=');
      }
    }

    $query->condition($or);
    $query->orderBy('route', 'DESC');
    $query->range(0, 1);
    if ($result = $query->execute()->fetchAssoc()) {
      return $result;
    }
    return [
      'page_type' => 'undefined',
      'handler' => 'default',
    ];
  }

}
