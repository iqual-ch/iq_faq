<?php

namespace Drupal\iq_faq;

use Drupal\Component\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\iq_faq\FaqAnswerConditionPluginInterface;

/**
 * Base class for Task plugin plugins.
 *
 * @see \Drupal\iq_faq\Annotation\FaqAnswerConditionPlugin
 * @see \Drupal\iq_faq\FaqAnswerConditionPluginInterface
 */
abstract class FaqAnswerConditionPluginBase extends PluginBase implements FaqAnswerConditionPluginInterface, ContainerFactoryPluginInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TranslationInterface $string_translation) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation'),
    );
  }

}
