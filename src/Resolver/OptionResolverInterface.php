<?php

namespace Drupal\commerce_option\Resolver;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_option\Entity\ProductOptionInterface;

/**
 * Interface OptionResolverInterface.
 */
interface OptionResolverInterface {

  /**
   * Resolves all product options for the given purchasable entity.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchasable_entity
   *   The purchasable entity.
   *
   * @return \Drupal\commerce_option\Entity\ProductOptionInterface[]
   *   Array of product options for the purchasable entity.
   */
  public function resolveOptions(PurchasableEntityInterface $purchasable_entity);

  /**
   * Returns all option values for the given purchasable entity and option.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchasable_entity
   *   The purchasable entity.
   * @param \Drupal\commerce_option\Entity\ProductOptionInterface $option
   *   The product option config entity.
   *
   * @return \Drupal\commerce_option\Entity\ProductOptionValueInterface[]
   *   Array of product option values for the option and purchasable entity.
   */
  public function resolveOptionValues(PurchasableEntityInterface $purchasable_entity, ProductOptionInterface $option);

}
