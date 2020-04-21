<?php

namespace Drupal\commerce_option;

use Drupal\commerce_option\Entity\ProductOptionInterface;

use Drupal\commerce_product\Entity\ProductVariationInterface;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * Default implementation of the ProductOptionFieldManagerInterface.
 */
class ProductOptionFieldManager implements ProductOptionFieldManagerInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  public $entityTypeManager;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Local cache for option field definitions.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface[]
   */
  protected $fieldDefinitions = [];

  /**
   * Local cache for the option field map.
   *
   * @var array
   */
  protected $fieldMap;

  /**
   * Constructs a new ProductOptionFieldManager object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityTypeManagerInterface $entity_type_manager, CacheBackendInterface $cache) {
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityTypeManager = $entity_type_manager;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinitions($variation_type_id) {
    if (!isset($this->fieldDefinitions[$variation_type_id])) {
      $definitions = $this->entityFieldManager->getFieldDefinitions('commerce_product_variation', $variation_type_id);
      $definitions = array_filter($definitions, function ($definition) {
        /** @var \Drupal\Core\Field\FieldDefinitionInterface $definition */
        $field_type = $definition->getType();
        $target_type = $definition->getSetting('target_type');
        return $field_type == 'entity_reference' && $target_type == 'commerce_option_option_value';
      });
      $this->fieldDefinitions[$variation_type_id] = $definitions;
    }

    return $this->fieldDefinitions[$variation_type_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldMap($variation_type_id = NULL) {
    if (!isset($this->fieldMap)) {
      if ($cached_map = $this->cache->get('commerce_product.option_field_map')) {
        $this->fieldMap = $cached_map->data;
      }
      else {
        $this->fieldMap = $this->buildFieldMap();
        $this->cache->set('commerce_product.option_field_map', $this->fieldMap);
      }
    }

    if ($variation_type_id) {
      // The map is empty for any variation type that has no options fields.
      return isset($this->fieldMap[$variation_type_id]) ? $this->fieldMap[$variation_type_id] : [];
    }
    else {
      return $this->fieldMap;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clearCaches() {
    $this->fieldDefinitions = [];
    $this->fieldMap = NULL;
    $this->cache->delete('commerce_product.option_field_map');
  }

  /**
   * Builds the field map.
   *
   * @return array
   *   The built field map.
   */
  protected function buildFieldMap() {
    $field_map = [];
    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo('commerce_product_variation');
    foreach (array_keys($bundle_info) as $bundle) {
      /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
      $form_display = commerce_get_entity_display('commerce_product_variation', $bundle, 'form');
      foreach ($this->getFieldDefinitions($bundle) as $field_name => $definition) {
        $handler_settings = $definition->getSetting('handler_settings');
        $component = $form_display->getComponent($field_name);

        $field_map[$bundle][] = [
          'option_id' => reset($handler_settings['target_bundles']),
          'field_name' => $field_name,
          'weight' => $component ? $component['weight'] : 0,
        ];
      }

      if (!empty($field_map[$bundle])) {
        uasort($field_map[$bundle], ['\Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
        // Remove the weight keys to reduce the size of the cached field map.
        $field_map[$bundle] = array_map(function ($map) {
          return array_diff_key($map, ['weight' => '']);
        }, $field_map[$bundle]);
      }
    }

    return $field_map;
  }

  /**
   * {@inheritdoc}
   */
  public function createField(ProductOptionInterface $option, $variation_type_id) {
    $field_name = $this->buildFieldName($option);
    $field_storage = FieldStorageConfig::loadByName('commerce_product_variation', $field_name);
    $field = FieldConfig::loadByName('commerce_product_variation', $variation_type_id, $field_name);
    if (empty($field_storage)) {
      $field_storage = FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'commerce_product_variation',
        'type' => 'entity_reference',
        'cardinality' => 1,
        'settings' => [
          'target_type' => 'commerce_option_option_value',
        ],
        'translatable' => FALSE,
      ]);
      $field_storage->save();
    }
    if (empty($field)) {
      $field = FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => $variation_type_id,
        'label' => $option->label(),
        'required' => FALSE,
        'settings' => [
          'handler' => 'default',
          'handler_settings' => [
            'target_bundles' => [$option->id()],
          ],
        ],
        'translatable' => FALSE,
      ]);
      $field->save();

      /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
      $form_display = commerce_get_entity_display('commerce_product_variation', $variation_type_id, 'form');
      $form_display->setComponent($field_name, [
        'type' => 'options_select',
        'weight' => $this->getHighestWeight($form_display) + 1,
      ]);
      $form_display->save();

      $this->clearCaches();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function canDeleteField(ProductOptionInterface $option, $variation_type_id) {
    $field_name = $this->buildFieldName($option);
    $field = FieldConfig::loadByName('commerce_product_variation', $variation_type_id, $field_name);
    if (!$field) {
      // The matching field was already deleted, or follows a different naming
      // pattern, because it wasn't created by this class.
      return FALSE;
    }
    $query = $this->entityTypeManager->getStorage('commerce_product_variation')->getQuery()
      ->condition('type', $variation_type_id)
      ->exists($field_name)
      ->range(0, 1);
    $result = $query->execute();

    return empty($result);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteField(ProductOptionInterface $option, $variation_type_id) {
    if (!$this->canDeleteField($option, $variation_type_id)) {
      return;
    }

    $field_name = $this->buildFieldName($option);
    $field = FieldConfig::loadByName('commerce_product_variation', $variation_type_id, $field_name);
    if ($field) {
      $field->delete();
      $this->clearCaches();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionValues(ProductVariationInterface $product_variation) {
    $option_values = [];
    foreach ($this->getOptionFieldNames($product_variation) as $field_name) {
      $field = $product_variation->get($field_name);
      if (!$field->isEmpty()) {
        $option_values[$field_name] = $field->entity;
      }
    }

    return $option_values;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionFieldNames(ProductVariationInterface $entity) {
    $field_map = $this->getFieldMap(
      $entity->bundle()
    );
    return array_column($field_map, 'field_name');
  }

  /**
   * Builds the field name for the given option.
   *
   * @param \Drupal\commerce_option\Entity\ProductOptionInterface $option
   *   The product option.
   *
   * @return string
   *   The field name.
   */
  protected function buildFieldName(ProductOptionInterface $option) {
    return 'option_' . $option->id();
  }

  /**
   * Gets the highest weight of the option field components in the display.
   *
   * @param \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display
   *   The form display.
   *
   * @return int
   *   The highest weight of the components in the display.
   */
  protected function getHighestWeight(EntityFormDisplayInterface $form_display) {
    $field_names = array_keys($this->getFieldDefinitions($form_display->getTargetBundle()));
    $weights = [];
    foreach ($field_names as $field_name) {
      if ($component = $form_display->getComponent($field_name)) {
        $weights[] = $component['weight'];
      }
    }

    return $weights ? max($weights) : 0;
  }

}
