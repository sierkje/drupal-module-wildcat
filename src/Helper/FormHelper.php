<?php

namespace Drupal\wildcat\Helper;

use Drupal\Core\Render\ElementInfoManagerInterface;

/**
 * Default form helper implementation.
 */
class FormHelper implements FormHelperInterface {

  /**
   * The element info plugin manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

  /**
   * Constructs a FormHelper instance.
   *
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info
   *   The element info plugin manager.
   */
  public function __construct(ElementInfoManagerInterface $element_info) {
    $this->elementInfo = $element_info;
  }

  /**
   * {@inheritdoc}
   */
  public function addAfterStandard(array &$element, array $additional) {
    if (empty($element['#process'])) {
      // Only add the standard processing if this method is called when Drupal
      // is actually installed.
      $element_info = $this->elementInfo->getInfo($element['#type']) ?? [];
      $element['#process'] = [];
      if (isset($element_info['#process'])) {
        $element['#process'] = $element_info['#process'];
      }
    }

    foreach ($additional as $callback) {
      $element['#process'][] = $callback;
    }

    return $this;
  }

}
