<?php

namespace Drupal\devel\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DrupalKernelInterface;
use Drupal\Core\Url;
use Drupal\devel\DevelDumperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for the plugin info pages.
 */
class PluginInfoController extends ControllerBase implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * The drupal kernel.
   *
   * @var \Drupal\Core\DrupalKernelInterface
   */
  protected $kernel;

  /**
   * The dumper service.
   *
   * @var \Drupal\devel\DevelDumperManagerInterface
   */
  protected $dumper;

  /**
   * PluginInfoController constructor.
   *
   * @param \Drupal\Core\DrupalKernelInterface $drupalKernel
   *   The drupal kernel.
   * @param \Drupal\devel\DevelDumperManagerInterface $dumper
   *   The dumper service.
   */
  public function __construct(DrupalKernelInterface $drupalKernel, DevelDumperManagerInterface $dumper) {
    $this->kernel = $drupalKernel;
    $this->dumper = $dumper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('kernel'),
      $container->get('devel.dumper')
    );
  }

  /**
   * Builds the plugin types overview page.
   *
   * @return array
   *   A render array as expected by the renderer.
   */
  public function pluginTypeList() {
    $headers = [
      $this->t('Plugin Type Service'),
      $this->t('Class'),
      $this->t('Operations'),
    ];

    $rows = [];

    $container = $this->kernel->getCachedContainerDefinition();
    if (!empty($container)) {
      foreach ($container['services'] as $service_id => $definition) {
        if (strpos($service_id, 'plugin.manager.') !== 0) {
          continue;
        }
        $service = unserialize($definition);

        $row['id'] = [
          'data' => $service_id,
          'class' => 'table-filter-text-source',
        ];
        $row['class'] = [
          'data' => isset($service['class']) ? $service['class'] : '',
          'class' => 'table-filter-text-source',
        ];
        $row['operations']['data'] = [
          '#type' => 'operations',
          '#links' => [
            'devel' => [
              'title' => $this->t('Plugins'),
              'url' => Url::fromRoute('devel.plugin_info', ['service_id' => $service_id]),
            ],
          ],
        ];

        $rows[$service_id] = $row;
      }

      ksort($rows);
    }

    $output['#attached']['library'][] = 'system/drupal.system.modules';

    $output['filters'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['table-filter', 'js-show'],
      ],
    ];
    $output['filters']['text'] = [
      '#type' => 'search',
      '#title' => $this->t('Search'),
      '#size' => 30,
      '#placeholder' => $this->t('Enter plugin type or class'),
      '#attributes' => [
        'class' => ['table-filter-text'],
        'data-table' => '.devel-filter-text',
        'autocomplete' => 'off',
        'title' => $this->t('Enter a part of the plugin type or class to filter by.'),
      ],
    ];
    $output['services'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No plugin types found.'),
      '#sticky' => TRUE,
      '#attributes' => [
        'class' => ['devel-service-list', 'devel-filter-text'],
      ],
    ];

    return $output;
  }

  /**
   * Returns a table listing of plugins.
   *
   * @param string $service_id
   *   The ID of the service to retrieve.
   *
   * @return array
   *   A render array as expected by the renderer.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   If the requested service is not defined.
   */
  public function pluginList($service_id) {
    $headers = [
      $this->t('Plugin'),
      $this->t('Class'),
      $this->t('Operations'),
    ];

    $rows = [];

    $plugins = $this->container->get($service_id)->getDefinitions();

    foreach ($plugins as $plugin_id => $plugin) {
      $row['id'] = [
        'data' => $plugin_id,
        'class' => 'table-filter-text-source',
      ];
      $row['class'] = [
        'data' => isset($plugin['class']) ? $plugin['class'] : '',
        'class' => 'table-filter-text-source',
      ];
      $row['operations']['data'] = [
        '#type' => 'operations',
        '#links' => [
          'devel' => [
            'title' => $this->t('Devel'),
            'url' => Url::fromRoute('devel.plugin_info.item', ['plugin_id' => $plugin_id, 'service_id' => $service_id]),
          ],
        ],
      ];

      $rows[$plugin_id] = $row;
    }

    ksort($rows);

    $output['#attached']['library'][] = 'system/drupal.system.modules';

    $output['filters'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['table-filter', 'js-show'],
      ],
    ];
    $output['filters']['text'] = [
      '#type' => 'search',
      '#title' => $this->t('Search'),
      '#size' => 30,
      '#placeholder' => $this->t('Enter plugin id or class'),
      '#attributes' => [
        'class' => ['table-filter-text'],
        'data-table' => '.devel-filter-text',
        'autocomplete' => 'off',
        'title' => $this->t('Enter a part of the plugin id or class to filter by.'),
      ],
    ];
    $output['services'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No services found.'),
      '#sticky' => TRUE,
      '#attributes' => [
        'class' => ['devel-service-list', 'devel-filter-text'],
      ],
    ];

    return $output;
  }

  /**
   * Returns a render array representation of a plugin.
   *
   * @param string $service_id
   *   The ID of the service to use for retrieving the plugin.
   * @param string $plugin_id
   *   The ID of the plugin to retrieve.
   *
   * @return array
   *   A render array containing the plugin listing.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   If the requested service is not defined.
   */
  public function pluginDetail($service_id, $plugin_id) {
    $service = $this->container->get($service_id);
    $definition = $service->getDefinition($plugin_id);

    return $this->dumper->exportAsRenderable($definition);
  }

}
