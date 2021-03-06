<?php
namespace Drupal\migrate_qa\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class IssueListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['summary'] = $this->t('Summary');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\migrate_qa\Entity\TrackerInterface */
    $row['id'] = $entity->id();
    $row['summary'] = $entity->toLink()->toString();

    return $row + parent::buildRow($entity);
  }
}
