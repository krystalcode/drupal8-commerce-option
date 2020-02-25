<?php

namespace Drupal\commerce_option_pricing\Event;

use Drupal\commerce\PurchasableEntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the option price resolver event.
 */
class OptionPriceResolverEvent extends Event {

  /**
   * The added entity.
   *
   * @var \Drupal\commerce\PurchasableEntityInterface
   */
  protected $entity;

  /**
   * The purchased quantity.
   *
   * @var string
   */
  protected $quantity;

  /**
   * The array of option values.
   *
   * @var \Drupal\commerce_option\Entity\ProductOptionValueInterface[]
   */
  protected $options;

  /**
   * The total calculated price.
   *
   * @var string
   */
  protected $totalPrice;

  /**
   * OptionPriceResolverEvent constructor.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param string $quantity
   *   The quantity.
   * @param \Drupal\commerce_option\Entity\ProductOptionValueInterface[] $options
   *   The selected options.
   * @param string $totalPrice
   *   The total calculated price.
   */
  public function __construct(PurchasableEntityInterface $entity, $quantity, array $options, $totalPrice) {
    $this->entity = $entity;
    $this->quantity = $quantity;
    $this->options = $options;
    $this->totalPrice = $totalPrice;
  }

  /**
   * Gets the entity.
   *
   * @return \Drupal\commerce\PurchasableEntityInterface
   *   The purchasable entity.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Gets the quantity.
   *
   * @return float
   *   The quantity.
   */
  public function getQuantity() {
    return $this->quantity;
  }

  /**
   * Gets the selected options.
   *
   * @return \Drupal\commerce_option\Entity\ProductOptionValueInterface[]
   *   The selected options.
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * Gets the total resolved price.
   *
   * @return string
   *   The total resolved price.
   */
  public function getTotalPrice() {
    return $this->totalPrice;
  }

  /**
   * Sets the total price.
   *
   * @param string $price
   *   The new total price to be set.
   */
  public function setTotalPrice($price) {
    $this->totalPrice = $price;
  }

}
