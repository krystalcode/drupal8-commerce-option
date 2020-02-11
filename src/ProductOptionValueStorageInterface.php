<?php

namespace Drupal\commerce_option;

use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines the interface for product option value storage.
 */
interface ProductOptionValueStorageInterface extends ContentEntityStorageInterface {

  /**
   * Loads product option values for the given product option.
   *
   * @param string $option_id
   *   The product option ID.
   *
   * @return \Drupal\commerce_option\Entity\ProductOptionValueInterface[]
   *   The product option values, indexed by id, ordered by weight.
   */
  public function loadMultipleByOption($option_id);

}
