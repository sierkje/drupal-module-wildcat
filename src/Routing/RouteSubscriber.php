<?php

namespace Drupal\wildcat\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\wildcat\Controller\FieldUiTitleController;
use Symfony\Component\Routing\RouteCollection;

/**
 * Dynamically alters various routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The affected entity type IDs.
   *
   * @var string[]
   */
  protected $entityTypes;

  /**
   * RouteSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $definitions = $entity_type_manager->getDefinitions();

    $filter = function (EntityTypeInterface $entity_type) {
      return (bool) $entity_type->get('field_ui_base_route');
    };
    $definitions = array_filter($definitions, $filter);

    $this->entityTypes = array_keys($definitions);
  }

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    $title_callback = FieldUiTitleController::class . '::getTitle';
    foreach ($this->entityTypes as $entity_type) {
      foreach ($this->getRouteNames($entity_type) as $route_name) {
        if ($route = $collection->get($route_name)) {
          $route->setDefault('_title_callback', $title_callback);
        }
      }
    }
  }

  /**
   * Returns the route names that should be altered for a given entity type.
   *
   * @param string $entity_type
   *   The affected entity type ID.
   *
   * @return array
   *   An array with route names.
   */
  protected function getRouteNames($entity_type) {
    $route_names = [];

    // The 'Manage fields' page.
    $route_names[] = "entity.{$entity_type}.field_ui_fields";
    // The default view display under 'Manage display'.
    $route_names[] = "entity.entity_view_display.{$entity_type}.default";
    // A customized view display under 'Manage display'.
    $route_names[] = "entity.entity_view_display.{$entity_type}.view_mode";
    // The default form display under 'Manage display'.
    $route_names[] = "entity.entity_form_display.{$entity_type}.default";
    // A customized form display under 'Manage display'.
    $route_names[] = "entity.entity_form_display.{$entity_type}.view_mode";

    return $route_names;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();

    // We need to run after Field UI.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -110];

    return $events;
  }

}
