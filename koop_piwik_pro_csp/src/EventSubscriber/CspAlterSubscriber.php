<?php

declare(strict_types = 1);

namespace Drupal\koop_piwik_pro_csp\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\csp\CspEvents;
use Drupal\csp\Event\PolicyAlterEvent;
use Drupal\koop_piwik_pro\SnippetServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
   * Add GTranslate sha.
   *
   * @param \Drupal\csp\Event\PolicyAlterEvent $alterEvent
   *   The Policy Alter event.
   */
  public function onCspPolicyAlter(PolicyAlterEvent $alterEvent): void {
    $response = $alterEvent->getResponse();
    if (!$this->config->get('domain') || !$response instanceof AttachmentsInterface) {
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

    // Add the hashes.
    $hashes[] = "'sha256-" . base64_encode(hash('sha256', $this->snippetService->getBodyScript(), TRUE)) . "'";
    $hashes[] = "'sha256-" . base64_encode(hash('sha256', $this->snippetService->getDataLayerScript(), TRUE)) . "'";
    $hash = implode(' ', $hashes);

    $directives = [
      'script-src',
      'style-src',
    ];
    foreach ($directives as $name) {
      $directive = $policy->hasDirective($name) ? $policy->getDirective($name) : [];
      if (!$directive || !in_array("'unsafe-inline'", $directive)) {
        $policy->appendDirective($name, $hash);
      }
    }
  }

}
