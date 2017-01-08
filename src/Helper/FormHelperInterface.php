<?php

namespace Drupal\wildcat\Helper;

/**
 * Provides an interface for form helpers.
 */
interface FormHelperInterface {

  /**
   * Adds additional processing after the standard processing.
   *
   * @param array &$element
   *   The form element that needs additional processing.
   * @param array $additional
   *   Array of additional processing callbacks to apply after the standard
   *   processing.
   *
   * @return $this
   *   The called object, for a fluent interface.
   *
   * @see wildcat_form_user_form_alter()
   */
  public function addAfterStandard(array &$element, array $additional);

}
