<?php

namespace Drupal\wildcat\Helper;

/**
 * Provides an interface for path alias helpers.
 */
interface PathAliasHelperInterface {

  /**
   * Ensures that all fieldable entities have a path alias.
   *
   * This method is called by wildcat_entity_load().
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   The entities keyed by entity ID.
   *
   * @return $this
   *   The called object, for a fluent interface.
   *
   * @see wildcat_entity_load()
   */
  public function ensureEntitiesHavePathAlias(array $entities);

}
