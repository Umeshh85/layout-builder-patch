<?php

namespace Drupal\migrate_qa\Controller;

use Drupal\diff\Controller\PluginRevisionController;

/**
 * Returns responses for Tracker Revision routes.
 */
class TrackerRevisionController extends PluginRevisionController {

  /**
   * Returns a display which shows the differences between two revisions.
   *
   * @param int $left_revision
   *   Vid of the tracker revision from the left.
   * @param int $right_revision
   *   Vid of the tracker revision from the right.
   * @param string $filter
   *   If $filter == 'raw' raw text is compared (including html tags)
   *   If $filter == 'raw-plain' markdown function is applied to the text before
   *   comparison.
   *
   * @return array
   *   Table showing the diff between the two revisions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function compareTrackerRevisions($left_revision, $right_revision, $filter) {
    $storage = $this->entityTypeManager()->getStorage('migrate_qa_tracker');
    // This routeMatch requires a paramconverter service to upcast the tracker
    // id in the URL to a tracker object.
    $route_match = \Drupal::routeMatch();
    $left_revision = $storage->loadRevision($left_revision);
    $right_revision = $storage->loadRevision($right_revision);
    $build = $this->compareEntityRevisions($route_match, $left_revision, $right_revision, $filter);
    return $build;
  }

}
