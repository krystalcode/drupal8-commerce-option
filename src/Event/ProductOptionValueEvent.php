<?php

namespace Drupal\commerce_option\Event;

use Drupal\commerce_option\Entity\ProductOptionValueInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the product option value event.
 *
 * @see \Drupal\commerce_option\Event\ProductEvents
 */
class ProductOptionValueEvent extends Event {

  /**
   * The product option value.
   *
   * @var \Drupal\commerce_option\Entity\ProductOptionValueInterface
   */
  protected $optionValue;

  /**
   * Constructs a new ProductOptionValueEvent.
   *
   * @param \Drupal\commerce_option\Entity\ProductOptionValueInterface $option_value
   *   The product option value.
   */
  public function __construct(ProductOptionValueInterface $option_value) {
    $this->optionValue = $option_value;
  }

  /**
   * Gets the product option value.
   *
   * @return \Drupal\commerce_option\Entity\ProductOptionValueInterface
   *   The product option value.
   */
  public function getOptionValue() {
    return $this->optionValue;
  }

}
