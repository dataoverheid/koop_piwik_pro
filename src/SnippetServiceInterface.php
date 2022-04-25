<?php

declare(strict_types = 1);

namespace Drupal\koop_piwik_pro;

/**
 * Interface for creating the Piwik PRO JavaScript snippets.
 */
interface SnippetServiceInterface {

  /**
   * Get the Piwik Pro body script.
   *
   * @return string
   *   The JavaScript withouth script tag.
   */
  public function getBodyScript(): string;

  /**
   * Get the Piwik Pro dataLayer push script.
   *
   * @return string
   *   The JavaScript withouth script tag.
   */
  public function getDataLayerScript(): string;

}
