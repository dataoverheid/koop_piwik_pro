<?php

declare(strict_types = 1);

namespace Drupal\koop_piwik_pro;

/**
 * Interface for creating the Piwik PRO JavaScript snippets.
 */
interface SnippetServiceInterface {

  /**
   * Get the Piwik PRO body script.
   *
   * @return string
   *   The JavaScript without script tag.
   */
  public function getBodyScript(): string;

  /**
   * Get the Piwik PRO dataLayer push script.
   *
   * @return string
   *   The JavaScript without script tag.
   */
  public function getDataLayerScript(): string;

  /**
   * Get the nonce.
   *
   * @return string
   *   The nonce.
   */
  public function getNonce(): string;

  /**
   * Should the Piwik PRO code be added to this page.
   *
   * @return bool
   *   Whether the code should be added or not.
   */
  public function getVisibilityForPage(): bool;

}
