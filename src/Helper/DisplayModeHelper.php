<?php

namespace Drupal\wildcat\Helper;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\wildcat\Exception\DisplayModeHelperException;

/**
 * Default entity display helper implementation.
 */
class DisplayModeHelper implements DisplayModeHelperInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The cache tags invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Property displayModeStorage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface[]
   */
  protected $displayModeStorage;

  /**
   * Constructs a DisplayModeModeHelper instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getModeHelp($route_name, RouteMatchInterface $route_match) {
    $help = [];

    // Parse the route name to figure out what display mode we're looking at:
    // 0 is the entire string.
    // 1 is 'view' or 'form'.
    // 2 is the ID of the affected entity type.
    // 3 is 'view_mode' or 'form_mode'.
    // 4 is 'view' or 'form'.
    $pattern = '/^entity\.entity_(view|form)_display\.([a-z_]+)\.((view|form)_mode)$/';
    if (preg_match($pattern, $route_name, $matches)) {
      $type = $matches[1];
      $target_type = $matches[2] . '.' . $route_match->getParameter($matches[3] . '_name');
      $mode = $matches[3];

      /** @var \Drupal\Core\Entity\EntityDisplayModeInterface $display_mode */
      $display_mode = $this->getDisplayModeStorage($type)
        ->load("{$target_type}.{$mode}");
      $display_mode_description = $display_mode
        ->getThirdPartySetting('wildcat', 'description');

      $help = empty($display_mode_description) ? [] : [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#markup' => $display_mode_description,
      ];
    }
    return $help;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setModeAsInternal($type, array $target_types, $mode) {
    if (empty($target_types)) {
      $target_types = array_keys($this->entityTypeManager->getDefinitions());
    }

    /** @var \Drupal\Core\Entity\EntityViewModeInterface[] $view_modes */
    $view_modes = $this->getDisplayModeStorage($type)
      ->loadMultiple(array_map(function ($entity_type_id) use ($mode) {
        return "{$entity_type_id}.{$mode}";
      }, $target_types));

    foreach ($view_modes as $view_mode) {
      $view_mode
        ->setThirdPartySetting('wildcat', 'internal', TRUE)
        ->save();
    }

    return $this;
  }

  /**
   * Returns the (form or view) display mode storage.
   *
   * @param string $type
   *   The type of display mode, must be either 'form' or 'view'.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The display mode storage class.
   *
   * @throws \Drupal\wildcat\Exception\DisplayModeHelperException
   *   When the display mode storage class could not be loaded.
   */
  protected function getDisplayModeStorage($type) {
    if ($type !== 'form' && $type !== 'view') {
      $message = "Only 'form' and 'view' are valid display form types, but '{$type}'' was requested.";
      throw new DisplayModeHelperException($message);
    }

    try {
      if (!$this->displayModeStorage[$type]) {
        $this->displayModeStorage[$type] = $this->entityTypeManager
          ->getStorage("entity_{$type}_mode");
      }

      return $this->displayModeStorage[$type];
    }
    catch (InvalidPluginDefinitionException $e) {
      $message = "The 'entity_{$type}_mode' storage class could not be loaded.";
      throw new DisplayModeHelperException($message, $e->getCode(), $e);
    }
  }

}
