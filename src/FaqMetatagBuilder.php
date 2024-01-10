<?php

namespace Drupal\iq_faq;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Url;

/**
 * Provides trusted callbacks to add faqs to html.
 *
 * @see iq_faq_element_info_alter()
 */
class FaqMetatagBuilder implements TrustedCallbackInterface {

  /**
   * The collected pagedesigner output.
   *
   * @var string
   */
  protected static $output = '';

  /**
   * The main entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected static $entity = NULL;

  /**
   * The collected cache tags.
   *
   * @var string[]
   */
  protected static $tags = [];

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['postRenderCollect', 'postRenderHtmlTag'];
  }

  /**
   * Set the main entity and add cache tags.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The main entity.
   */
  public static function setEntity(ContentEntityInterface $entity) {
    self::$entity = $entity;
    self::$tags = Cache::mergeTags($entity->getCacheTags(), self::$tags);
  }

  /**
   * Add output to process.
   *
   * @param \Drupal\Component\Render\MarkupInterface|string $markup_object
   *   The markup object.
   * @param array $element
   *   The render element.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   The markup object.
   */
  public static function postRenderCollect(MarkupInterface|string $markup_object, array $element) {
    if (\Drupal::currentUser()->isAuthenticated()) {
      return $markup_object;
    }
    $output = $markup_object->__toString();
    if (str_contains($output, 'iq-faq-item-question')) {
      self::$output .= $output;
      // Add any cache tags to invalidate output cache.
      self::$tags = Cache::mergeTags($element['#cache']['tags'], self::$tags);
    }
    return $markup_object;
  }

  /**
   * Add the faqs to the markup.
   */
  public static function postRenderHtmlTag($markup_object, $element) {
    if (\Drupal::currentUser()->isAuthenticated()) {
      return $markup_object;
    }
    if (
      $element['#tag'] == 'script' &&
      !empty($element['#attributes']) &&
      !empty($element['#attributes']['type']) &&
      $element['#attributes']['type'] == 'application/ld+json'
      ) {
      if (empty(self::$output)) {
        return $markup_object;
      }
      $schema = Json::decode($element['#value']);
      if (empty($schema['@context']) && $schema['@context'] != 'https://schema.org') {
        return $markup_object;
      }
      if (empty($schema)) {
        $schema = [
          '@context' => 'https://schema.org',
          '@graph' => [
            0 => [
              '@type' => 'FAQPage',
              'mainEntity' => [
                0 => [
                  '@type' => 'Question',
                  'name'  => '',
                  'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => '',
                  ],
                ],
              ],
            ],
          ],
        ];
      }
      $dom = new \DOMDocument();
      $dom->loadHTML(self::$output, LIBXML_NOERROR);
      $finder = new \DomXPath($dom);

      // Search content for iq-faq-item elements.
      $questions = iterator_to_array($finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' iq-faq-item-question ')]"));
      $answers = iterator_to_array($finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' iq-faq-item-answer ')]"));
      $faqs = NULL;
      foreach ($schema['@graph'] as $delta => $entry) {
        if ($entry['@type'] == 'FAQPage') {
          $faqs = &$schema['@graph'][$delta]['mainEntity'];
          break;
        }
      }
      if ($faqs == NULL) {
        $faqs = ['@type' => 'FAQPage', 'mainEntity' => []];
        $schema['@graph'][] = &$faqs;
      }
      if (count($questions)) {
        $questions = array_map(fn($question) => trim(preg_replace('/\s\s+/', ' ', strip_tags((string) $question->ownerDocument->saveXML($question)))), $questions);
        $answers = array_map(fn($answer) => trim(preg_replace('/\s\s+/', ' ', strip_tags((string) $answer->ownerDocument->saveXML($answer)))), $answers);
        $url = Url::fromRoute('<current>', [], ["absolute" => TRUE])->toString();
        for ($i = 0; $i < count($questions); $i++) {
          $faqs[] = [
            '@type' => 'Question',
            'name' => $questions[$i],
            'acceptedAnswer' => [
              '@type' => 'Answer',
              'text' => $answers[$i],
              'url' => $url,
            ],
          ];
        }
        $faqs = array_values(
          array_filter(
          $faqs,
          function ($v) {
            return !empty($v['name']);
          }
        )
        );
        if (count($faqs) === 1) {
          $faqs = $faqs[0];
        }
        $result = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
        $markup_object = Markup::create($result);
      }
      else {
        $markup_object = Markup::create('');
      }
    }
    return $markup_object;
  }

}
