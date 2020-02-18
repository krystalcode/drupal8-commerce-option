<?php

namespace Drupal\commerce_option\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for product options.
 */
interface ProductOptionInterface extends ConfigEntityInterface {

  /**
   * Gets the option values.
   *
   * @return \Drupal\commerce_option\Entity\ProductOptionValueInterface[]
   *   The option values.
   */
  public function getValues();

  /**
   * Gets the option element type.
   *
   * @return string
   *   The element type name.
   */
  public function getElementType();

}
