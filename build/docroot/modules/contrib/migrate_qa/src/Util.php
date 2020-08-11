<?php

namespace Drupal\migrate_qa;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Class Util.
 *
 * @package Drupal\migrate_qa
 */
class Util {

  use \Drupal\Core\StringTranslation\StringTranslationTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Util constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   EntityTypeManager service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   RouteMatch service.
   */
  public function __construct(
    Connection $connection,
    EntityTypeManagerInterface $entityTypeManager,
    RouteMatchInterface $routeMatch
  ) {
    $this->connection = $connection;
    $this->entityTypeManager = $entityTypeManager;
    $this->routeMatch = $routeMatch;
  }

  /**
   * Get status of an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity to get a UAT status for.
   *
   * @return string
   *   The status, as a string.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getTrackerStatus(ContentEntityInterface $entity) {
    $tracker = $this->getTracker($entity);
    if (empty($tracker)) {
      return '';
    }
    $status_key = $tracker->tracker_status->value;
    if (empty($status_key)) {
      return '';
    }
    $field_settings = $tracker->getFieldDefinition('tracker_status')->getSettings();
    $status_options = $field_settings['allowed_values'];
    $status_value = $status_options[$status_key];

    return $status_value;
  }

  /**
   * Get the tracker related to a given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity whose tracker is being sought.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The tracker entity if found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getTracker(ContentEntityInterface $entity) {
    $type = $entity->getEntityTypeId();
    $id = $entity->id();
    $sql = '
      SELECT t.id
      FROM migrate_qa_tracker t
      INNER JOIN migrate_qa_connector c ON t.id = c.tracker
      WHERE c.content__target_id = :id
      AND c.content__target_type = :type
    ';

    $replacements = [
      ':id' => $id,
      ':type' => $type,
    ];
    $query = $this->connection->query($sql, $replacements);
    $tracker_id = $query->fetchField();
    if (empty($tracker_id)) {
      return NULL;
    }
    $tracker = $this->entityTypeManager->getStorage('migrate_qa_tracker')->load($tracker_id);

    return $tracker;
  }

  /**
   * Get the current content entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The entity.
   */
  public function getCurrentEntity() {
    $params = $this->routeMatch->getParameters()->all();
    // If first param is an content entity, get it.
    $param = array_shift($params);
    if ($param instanceof ContentEntityInterface) {
      $entity = $param;
    }
    if (empty($entity)) {
      return NULL;
    }

    return $entity;
  }

}
