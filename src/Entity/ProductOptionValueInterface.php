<?php

namespace Drupal\commerce_option\Entity;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the interface for product option values.
 */
interface ProductOptionValueInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the product option.
   *
   * @return \Drupal\commerce_option\Entity\ProductOptionValueInterface
   *   The product option.
   */
  public function getProductOption();

  /**
   * Gets the product option ID.
   *
   * The product option id is also the bundle of the product option value.
   *
   * @return string
   *   The product option ID.
   */
  public function getProductOptionId();

  /**
   * Gets the product option value name.
   *
   * @return string
   *   The product option value name.
   */
  public function getName();

  /**
   * Sets the product option value name.
   *
   * @param string $name
   *   The product option value name.
   *
   * @return $this
   */
  public function setName($name);

  /**
   * Gets the product option value weight.
   *
   * @return int
   *   The product option value weight.
   */
  public function getWeight();

  /**
   * Sets the product option value weight.
   *
   * @param int $weight
   *   The product option value weight.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Gets the product option value creation timestamp.
   *
   * @return int
   *   The product option value creation timestamp.
   */
  public function getCreatedTime();

  /**
   * Sets the product option value creation timestamp.
   *
   * @param int $timestamp
   *   The product option value creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

}
