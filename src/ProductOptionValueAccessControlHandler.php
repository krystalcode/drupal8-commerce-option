<?php

namespace Drupal\commerce_option;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler as CoreEntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an access control handler for product option values.
 *
 * Product option values are always managed in the scope of their parent
 * (the product option), so the parent access is used when possible:
 * - A product option value can be created, updated or deleted if the
 *   parent can be updated.
 * - A product option value can be viewed by any user with the
 *   "access content" permission, to allow rendering on any product.
 *   This matches the logic used by taxonomy terms.
 */
class ProductOptionValueAccessControlHandler extends CoreEntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ProductAttributeValueAccessControlHandler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    if ($operation == 'view') {
      $result = AccessResult::allowedIfHasPermission($account, 'access content');
    }
    else {
      /** @var \Drupal\commerce_option\Entity\ProductOptionValueInterface $entity */
      $result = $entity->getProductOption()->access('update', $account, TRUE);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $product_option_storage = $this->entityTypeManager->getStorage('commerce_option_product_option');
    $product_attribute = $product_option_storage->create([
      'id' => $entity_bundle,
    ]);

    return $product_attribute->access('update', $account, TRUE);
  }

}
