<?php

namespace Drupal\wildcat\Helper;

use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides an interface for entity display helpers.
 */
interface DisplayModeHelperInterface {

  /**
   * Returns help information for a given display mode.
   *
   * @param string $route_name
   *   The route name as identified in the modules's routing.yml file. (An empty
   *   array is always returned for route names that do not start with either
   *   'entity.entity_form_display.' or 'entity.entity_view_display.'.)
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match. This can be used to generate different help
   *   output for different pages that share the same route.
   *
   * @return array
   *   A renderable array containing the display mode description, or an empty
   *   array if no description is available.
   *
   * @throws \Drupal\wildcat\Exception\DisplayModeHelperException
   *   When the type requested is not either 'form' or 'view', or the display
   *   modes could not be loaded.
   *
   * @see wildcat_help()
   */
  public function getModeHelp($route_name, RouteMatchInterface $route_match);

  /**
   * Sets a given display mode (for given entity types) as internal.
   *
   * For example, to set the example to set the 'node.token' and 'comment.token'
   * entity view modes as internal, this method would get called with the
   * following parameters:
   *  - $type = 'view'
   *  - $entity_type_ids = ['node', 'comment']
   *  - $mode = 'token'
   *
   * @param string $type
   *   The display type, either 'form' or 'view'.
   * @param string[] $target_types
   *   An array containing the identifiers of applicable entity types. If an
   *   empty array is passed, as is default, applicable display modes for all
   *   entity types will be marked as internal.
   * @param string $mode
   *   The display mode (i.e. 'full, 'token', 'teaser', etc.).
   *
   * @return $this
   *   The called object, for a fluent interface.
   *
   * @throws \Drupal\wildcat\Exception\DisplayModeHelperException
   *   When the type requested is not either 'form' or 'view', or the display
   *   modes could not be loaded.
   *
   * @see wildcat_set_node_view_modes_as_internal()
   * @see wildcat_set_token_view_modes_as_internal()
   */
  public function setModeAsInternal($type, array $target_types, $mode);

}
