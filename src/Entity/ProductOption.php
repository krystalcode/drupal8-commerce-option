<?php

namespace Drupal\commerce_option\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the product option entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_option_product_option",
 *   label = @Translation("Product option"),
 *   label_collection = @Translation("Product options"),
 *   label_singular = @Translation("product option"),
 *   label_plural = @Translation("product options"),
 *   label_count = @PluralTranslation(
 *     singular = "@count product option",
 *     plural = "@count product options",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\entity\EntityAccessControlHandler",
 *     "permission_provider" = "Drupal\entity\EntityPermissionProvider",
 *     "list_builder" = "Drupal\commerce_option\ProductOptionListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_option\Form\ProductOptionForm",
 *       "edit" = "Drupal\commerce_option\Form\ProductOptionForm",
 *       "delete" = "Drupal\commerce_option\Form\ProductOptionDeleteForm",
 *     },
 *     "local_task_provider" = {
 *       "default" = "Drupal\entity\Menu\DefaultEntityLocalTaskProvider",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "commerce_product_option",
 *   admin_permission = "administer commerce_product_option",
 *   bundle_of = "commerce_option_option_value",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "elementType",
 *     "productTypes"
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/product-options/add",
 *     "edit-form" = "/admin/commerce/product-options/manage/{commerce_option_product_option}",
 *     "delete-form" = "/admin/commerce/product-options/manage/{commerce_option_product_option}/delete",
 *     "collection" =  "/admin/commerce/product-options",
 *   }
 * )
 */
class ProductOption extends ConfigEntityBundleBase implements ProductOptionInterface {

  /**
   * The option ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The option label.
   *
   * @var string
   */
  protected $label;

  /**
   * The option element type.
   *
   * @var string
   */
  protected $elementType = 'select';

  /**
   * The list of product types.
   *
   * @var array
   */
  protected $productTypes;

  /**
   * {@inheritdoc}
   */
  public function getValues() {
    /** @var \Drupal\commerce_option\ProductOptionValueStorageInterface $storage */
    $storage = $this->entityTypeManager()
      ->getStorage('commerce_option_option_value');
    $values = $storage->loadMultipleByOption($this->id());
    // Make sure that the values are returned in the attribute language.
    $langcode = $this->language()->getId();
    foreach ($values as $index => $value) {
      if ($value->hasTranslation($langcode)) {
        $values[$index] = $value->getTranslation($langcode);
      }
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementType() {
    return $this->elementType;
  }

  /**
   * {@inheritdoc}
   */
  public function getProductTypes() {
    if ($this->productTypes) {
      $values = [];
      array_walk_recursive($this->productTypes, function ($value, $key) use (&$values) {
        $values[] = $value;
      }, $values);

      return $this->entityTypeManager()
        ->getStorage('commerce_product_type')
        ->loadMultiple($values);
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    /** @var \Drupal\commerce_product\Entity\ProductAttributeInterface[] $entities */
    parent::postDelete($storage, $entities);

    // Delete all associated values.
    $values = [];
    foreach ($entities as $entity) {
      foreach ($entity->getValues() as $value) {
        $values[$value->id()] = $value;
      }
    }
    /** @var \Drupal\Core\Entity\EntityStorageInterface $value_storage */
    $value_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_option_option_value');
    $value_storage->delete($values);
  }

}
