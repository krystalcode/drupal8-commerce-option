<?php

namespace Drupal\commerce_option;

use Drupal\commerce\CommerceContentEntityStorage;

/**
 * Defines the product option value storage.
 */
class ProductOptionValueStorage extends CommerceContentEntityStorage implements ProductOptionValueStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadMultipleByOption($option_id) {
    $entity_query = $this->getQuery();
    $entity_query->condition('product_option', $option_id);
    $entity_query->sort('weight');
    $entity_query->sort('name');
    $result = $entity_query->execute();
    return $result ? $this->loadMultiple($result) : [];
  }

}
