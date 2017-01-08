<?php

namespace Drupal\wildcat\Helper;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Path\AliasStorageInterface;
use Drupal\path\Plugin\Field\FieldType\PathFieldItemList;

/**
 * Default path alias helper implementation.
 */
class PathAliasHelper implements PathAliasHelperInterface {

  /**
   * The path alias storage.
   *
   * @var \Drupal\Core\Path\AliasStorageInterface.
   */
  protected $pathAliasStorage;

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
   * Constructs a PathAliasHelper instance.
   *
   * @param \Drupal\Core\Path\AliasStorageInterface $path_alias_storage
   *   The path alias storage.
   */
  public function __construct(AliasStorageInterface $path_alias_storage) {
    $this->pathAliasStorage = $path_alias_storage;
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

}
