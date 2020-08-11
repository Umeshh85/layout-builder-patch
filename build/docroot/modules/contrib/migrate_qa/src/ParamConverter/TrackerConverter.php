<?php

namespace Drupal\migrate_qa\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\migrate_qa\Entity\Tracker;
use Symfony\Component\Routing\Route;

class TrackerConverter implements ParamConverterInterface {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    return Tracker::load($value);
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'migrate_qa_tracker');
  }

}
