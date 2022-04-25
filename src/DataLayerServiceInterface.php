<?php

declare(strict_types = 1);

namespace Drupal\koop_piwik_pro;

/**
 * Interface for getting the DataLayer values.
 */
interface DataLayerServiceInterface {

  /**
   * Return the values.
   *
   * @return array
   *   An array containing all the values.
   */
  public function getValues(): array;

}
