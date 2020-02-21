<?php

namespace Drupal\commerce_option\Resolver;

use Drupal\commerce_option\Entity\ProductOptionInterface;
use Drupal\commerce\PurchasableEntityInterface;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class OptionResolver.
 */
class OptionResolver implements OptionResolverInterface {

  /**
   * The entity type manager to load the revision.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new OptionResolver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveOptions(PurchasableEntityInterface $purchasable_entity) {
    $productOptions = [];

    $productOptionIds = $this->entityTypeManager
      ->getStorage('commerce_option_product_option')
      ->getQuery()
      ->condition('productTypes.*.target_id', $purchasable_entity->getProduct()->bundle())
      ->execute();

    if ($productOptionIds) {
      $productOptions = $this->entityTypeManager
        ->getStorage('commerce_option_product_option')
        ->loadMultiple($productOptionIds);
    }

    return $productOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveOptionValues(PurchasableEntityInterface $purchasable_entity, ProductOptionInterface $option) {
    $productOptionValues = [];

    if (!in_array($purchasable_entity->getProduct()->bundle(), array_keys($option->getProductTypes()))) {
      return $productOptionValues;
    }

    return $option->getValues();
  }

}
