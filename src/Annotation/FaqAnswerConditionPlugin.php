<?php

namespace Drupal\iq_faq\FaqAnswerConditionPlugin;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a StyleDefinitionPlugin item annotation object.
 *
 * @see \Drupal\iq_faq\StyleDefinitionPluginBase
 * @see plugin_api
 *
 * @Annotation
 */
class FaqAnswerConditionPlugin extends Plugin {

  /**
   * Style definition machine name.
   *
   * @var string
   */
  public $id;

  /**
   * Style definition label.
   *
   * @var string
   */
  public $label;

}
