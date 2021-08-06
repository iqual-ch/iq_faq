<?php

namespace Drupal\iq_faq;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\node\Entity\Node;

/**
 * Defines an interface for StyleDefinitionContainer plugin plugins.
 */
interface FaqAnswerConditionPluginInterface extends PluginInspectionInterface {

  /**
   * Return the container's label.
   *
   * @param Drupal\node\Entity\Node $answer
   *   Answer to verify.
   *
   * @return bool
   *   returns the label as a string.
   */
  public function verify(Node $answer);

}
