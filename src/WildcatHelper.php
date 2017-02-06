<?php

namespace Drupal\wildcat;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Path\AliasStorageInterface;
use Drupal\path\Plugin\Field\FieldType\PathFieldItemList;

/**
 * Default Wildcat helper implementation.
 */
class WildcatHelper implements WildcatHelperInterface {

  /**
   * The path alias storage service.
   *
   * @var \Drupal\Core\Path\AliasStorageInterface.
   */
  protected $pathAliasStorage;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The UUIDs of entities for which aliases have already been looked up.
   *
   * The alias of an entity should only be looked up once per request, in order
   * to prevent infinite loops, see issue #2831550.
   *
   * @see https://www.drupal.org/node/2831550
   *
   * @var string
   */
  protected $entitiesWithAlias = [];

  /**
   * Constructs a WildcatHelper instance.
   *
   * @param \Drupal\Core\Path\AliasStorageInterface $path_alias_storage
   *   The path alias storage service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(AliasStorageInterface $path_alias_storage, EntityTypeManagerInterface $entity_type_manager) {
    $this->pathAliasStorage = $path_alias_storage;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function ensureEntitiesHavePathAlias(array $entities) {
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    foreach ($entities as $entity) {
      // Only fieldable entities need a path alias.
      $needs_path = $entity instanceof FieldableEntityInterface;
      // Only entities whose aliases have not yet been looked up need an alias.
      $uuid = $entity->uuid();
      $needs_path = $needs_path && empty($this->entitiesWithAlias[$uuid]);
      // Only entities that have a 'path' field need an alias.
      if (isset($entity->path) && $entity->hasField('path')) {
        /** @var \Drupal\path\Plugin\Field\FieldType\PathFieldItemList $path_field */
        $path_field = $entity->path;
        // Only entities with a 'path' field that is a PathField need an alias.
        $needs_path = $needs_path && $path_field instanceof PathFieldItemList;
        // Only entities with an empty 'path' field need an alias.
        $needs_path = $needs_path && $path_field->isEmpty();
      }

      // Store the entity UUID because the alias should only be looked up once
      // per request in order to prevent infinite loops.
      $this->entitiesWithAlias[$uuid] = TRUE;

      // If the entity has an empty path field, try to set its value.
      if ($needs_path) {
        try {
          $alias_source = '/' . $entity->toUrl()->getInternalPath();
          $alias = $this->pathAliasStorage->load(['source' => $alias_source]);
        }
        catch (\Exception $e) {
          $alias = FALSE;
        }

        if ($alias) {
          $entity->path->setValue($alias);
        }
      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function ensureEditorFilter() {
    $format_storage = $this->entityTypeManager->getStorage('filter_format');
    $formats = $format_storage->loadMultiple(['basic_html', 'full_html']);
    /** @var \Drupal\filter\FilterFormatInterface $format */
    foreach ($formats as $format) {
      $format->setFilterConfig('editor_file_reference', [
        'id' => 'editor_file_reference',
        'provider' => 'editor',
        'status' => TRUE,
        'weight' => 11,
        'settings' => [],
      ]);
    }
  }

}
