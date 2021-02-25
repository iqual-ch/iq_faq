<?php

namespace Drupal\iq_faq\Element;

use Drupal\ui_patterns\Element\Pattern;
use Drupal\node\NodeInterface;

/**
 * Renders a faq pattern element.
 */
class FaqPattern extends Pattern {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    $info = parent::getInfo();
    $info['#pre_render'][] = [$class, 'processFaqData'];
    return $info;
  }

  /**
   * Process faq data: Add Questions & Answers to page's meta data.
   *
   * @param array $element
   *   Render array.
   *
   * @return array
   *   Render array.
   */
  public static function processFaqData(array $element) {
    if ($element['#id'] == 'iq-faq-item') {

      $node = \Drupal::routeMatch()->getParameter('node');
      if ($node instanceof NodeInterface && $element['#question'] && $element['#answer']) {

        if (gettype($element['#question']) == 'array') {
          $question = strip_tags($element['#question']['#markup']);
        }
        else {
          $question = strip_tags($element['#question']->__toString());
        }

        if (gettype($element['#answer']) == 'array') {
          $answer = strip_tags($element['#answer']['#text']);
        }
        else {
          $answer = strip_tags($element['#answer']->__toString());
        }

        $metatags = \unserialize($node->field_meta_tags->value);
        // $metatags = \unserialize(metatag_get_tags_from_route($node));
        // $metatags = metatag_get_tags_from_route($node);
        if ($metatags['schema_qa_page_main_entity']) {
          $metatagsSchemaQA = unserialize($metatags['schema_qa_page_main_entity']);
        }
        else {
          $metatagsSchemaQA = [
            "@type" => "Question",
            "name" => "",
            "acceptedAnswer" => [
              "@type" => "Answer",
              "text" => "",
            ],
          ];
        }

        $metatagsQuestions = explode(':', $metatagsSchemaQA['name']);
        if (!\in_array($question, $metatagsQuestions)) {
          array_push($metatagsQuestions, $question);
        }
        $metatagsSchemaQA['name'] = rtrim(ltrim(implode(':', $metatagsQuestions), ':'), ':');

        $metatagsAnswers = explode(':', $metatagsSchemaQA['acceptedAnswer']['text']);
        if (!\in_array($answer, $metatagsAnswers)) {
          array_push($metatagsAnswers, $answer);
        }
        $metatagsSchemaQA['acceptedAnswer']['text'] = rtrim(ltrim(implode(':', $metatagsAnswers), ':'), ':');

        $metatags['schema_qa_page_main_entity'] = serialize($metatagsSchemaQA);
        $node->field_meta_tags = serialize($metatags);
        $node->save();
      }
    }
    return $element;
  }

}
