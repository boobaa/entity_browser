<?php

/**
 * @file
 * Contains \Drupal\entity_browser\Plugin\EntityBrowser\Display\DisplayRouterInterface.
 */

namespace Drupal\entity_browser\Plugin\EntityBrowser\Display;

/**
 * Defines an interface for displays that operate on routes.
 *
 * In addition to implementing the interface, specify 'uses_routes' in the
 * plugin definition.
 */
interface DisplayRouterInterface {

  /**
   * Gets page path.
   *
   * @return string
   *   Path where display operates.
   */
  public function path();

}