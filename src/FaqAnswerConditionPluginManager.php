<?php

namespace Drupal\iq_faq;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides FAQ answer condition plugin manager.
 */
class FaqAnswerConditionPluginManager extends DefaultPluginManager {

  /**
   * Seetings for style definition plugin manager.
   *
   * @var array
   */
  protected $settings;

  /**
   * Constructor for StyleDefinitionContainerPluginManager objects.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/FaqAnswerCondition',
      $namespaces,
      $module_handler,
      'Drupal\iq_faq\FaqAnswerConditionPluginManager',
      'Drupal\iq_faq\Annotation\FaqAnswerConditionPlugin'
    );
    $this->alterInfo('iq_faq_faq_answer_condition_plugin_info');
    $this->setCacheBackend($cache_backend, 'iq_faq_faq_answer_condition_plugins');
  }

}
