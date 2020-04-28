<?php

namespace Drupal\commerce_option\Resolver;

use Drupal\commerce_option\ProductOptionFieldManager;
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
   * The commerce options field manager.
   *
   * @var \Drupal\commerce_option\ProductOptionFieldManager
   */
  protected $fieldManager;

  /**
   * Constructs a new OptionResolver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\commerce_option\ProductOptionFieldManager $field_manager
   *   The option field manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ProductOptionFieldManager $field_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldManager = $field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveOptions(
    PurchasableEntityInterface $purchasable_entity
  ) {
    $productOptions = [];

    $product_option_values = $this->fieldManager->getOptionValues($purchasable_entity);
    foreach ($product_option_values as $product_option_value) {
      $productOptions[] = $product_option_value->getProductOption();
    }

    return $productOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveOptionValues(
    PurchasableEntityInterface $purchasable_entity,
    ProductOptionInterface $option
  ) {
    $productOptionValues = [];

    if (!in_array(
        $purchasable_entity->getProduct()->bundle(),
        array_keys($option->getProductTypes())
      )
    ) {
      return $productOptionValues;
    }

    return $option->getValues();
  }

}
