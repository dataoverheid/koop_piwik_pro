<?php

declare(strict_types = 1);

namespace Drupal\koop_piwik_pro_csp\EventSubscriber;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\csp\CspEvents;
use Drupal\csp\Event\PolicyAlterEvent;
use Drupal\koop_piwik_pro\SnippetServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides an event subscriber to add CSP exceptions.
 */
class CspAlterSubscriber implements EventSubscriberInterface {

  /**
   * The Piwik PRO config.
   */
  protected ImmutableConfig $config;

  /**
   * The snippet service.
   */
  protected SnippetServiceInterface $snippetService;

  /**
   * Constructs a CspAlterSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\koop_piwik_pro\SnippetServiceInterface $snippetService
   *   The snippet service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, SnippetServiceInterface $snippetService) {
    $this->config = $configFactory->get('koop_piwik_pro.settings');
    $this->snippetService = $snippetService;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[CspEvents::POLICY_ALTER] = ['onCspPolicyAlter'];
    return $events;
  }

  /**
   * Add required CSP headers.
   *
   * @param \Drupal\csp\Event\PolicyAlterEvent $alterEvent
   *   The Policy Alter event.
   */
  public function onCspPolicyAlter(PolicyAlterEvent $alterEvent): void {
    $response = $alterEvent->getResponse();
    if (!$this->config->get('domain') || !$response instanceof AttachmentsInterface || !$response instanceof CacheableResponseInterface) {
      return;
    }

    $policy = $alterEvent->getPolicy();

    // Add the base url to all required directives.
    $domain = parse_url($this->config->get('domain'));
    $baseDomain = $domain['scheme'] . '://' . $domain['host'];
    $directives = [
      'script-src',
      'connect-src',
      'img-src',
      'font-src',
      'style-src',
    ];
    foreach ($directives as $name) {
      if (!$policy->hasDirective($name)) {
        $policy->appendDirective($name, "'self'");
      }
      $policy->appendDirective($name, $baseDomain);
    }

    // Add the base url to all optional directives.
    $optionalDirectives = [
      'script-src-elem',
      'style-src-elem',
    ];
    foreach ($optionalDirectives as $name) {
      if ($policy->hasDirective($name)) {
        $policy->appendDirective($name, $baseDomain);
      }
    }

    // Add the nonce.
    if ($nonce = $this->getNone($response)) {
      $directives = [
        'script-src',
        'style-src',
      ];
      foreach ($directives as $name) {
        $directive = $policy->hasDirective($name) ? $policy->getDirective($name) : [];
        if (!$directive || !in_array("'unsafe-inline'", $directive)) {
          $policy->appendDirective($name, $nonce);
        }
      }

      // Add the hashes to all optional directives.
      $optionalDirectives = [
        'script-src-elem',
        'style-src-elem',
      ];
      foreach ($optionalDirectives as $name) {
        $directive = $policy->hasDirective($name) ? $policy->getDirective($name) : [];
        if ($directive && !in_array("'unsafe-inline'", $directive)) {
          $policy->appendDirective($name, $nonce);
        }
      }
    }
  }

  /**
   * Get the nonce.
   *
   * We get the nonce from the response code itself because of Drupal's
   * caching. This will lead to a nonce that will stay the same for of a
   * period of time, but is still better than having nothing at all.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The Response the policy is applied to.
   *
   * @return string|null
   *   The nonce if one is found.
   */
  private function getNone(Response $response): ?string {
    $matches = [];
    preg_match_all('/<script type="text\/javascript" data-source="piwik-pro" nonce="(.*?)">/', $response->getContent(), $matches);
    // We only expect a single match.
    if (!empty($matches[1][0]) && empty($matches[1][1])) {
      return "'nonce-" . $matches[1][0] . "'";
    }

    return NULL;
  }

}
