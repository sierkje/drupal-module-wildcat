<?php

namespace Drupal\wildcat\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Dynamically generates titles for various Field UI routes.
 */
class FieldUiTitleController{

  use StringTranslationTrait;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new FieldUiTitleController object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation service.
   */
  public function __construct(RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Routing\RouteMatchInterface $route_match */
    $route_match = $container->get('current_route_match');
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');
    /** @var \Drupal\Core\StringTranslation\TranslationInterface $string_translation */
    $string_translation = $container->get('string_translation');

    return new static($route_match, $entity_type_manager, $string_translation);
  }

  /**
   * Title callback for certain Field UI routes.
   *
   * @return string
   *   The title for the route.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Either the label of the bundle affected at the current route, or the
   *   route's default title if the bundle is not known.
   * @throws \InvalidArgumentException
   *   Thrown by \Symfony\Component\HttpFoundation\ParameterBag::get().
   *
   * @see \Drupal\wildcat\Routing\RouteSubscriber::alterRoutes()
   */
  public function getTitle() {
    $route_parameters = $this->routeMatch->getParameters();

    // Determine the route parameter which contains the bundle entity, assuming
    // the entity type is bundle-able.
    $bundle_entity_type = $this->entityTypeManager
      // Field UI routes should always have an entity_type_id parameter. Maybe
      // a naive assumption, but this function should only ever be called for
      // Field UI routes anyway.
      ->getDefinition($route_parameters->get('entity_type_id'))
      ->getBundleEntityType();

    if ($bundle_entity_type) {
      $bundle = $route_parameters->get($bundle_entity_type);
      if ($bundle instanceof EntityInterface) {
        return $bundle->label();
      }
    }

    return $this->routeMatch->getRouteObject()->getDefault('_title');
  }

}
