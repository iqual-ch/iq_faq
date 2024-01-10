<?php

namespace Drupal\iq_faq;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Language\LanguageInterface;
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
   * Add found faq questions to html output for anonymous users.
   *
   * @param \Drupal\Component\Render\MarkupInterface|string $markup_object
   *   The markup object.
   * @param array $element
   *   The render element.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   The markup object.
   */
  public static function postRenderHtmlTag(MarkupInterface|string $markup_object, array $element) {
    if (\Drupal::currentUser()->isAuthenticated()) {
      return $markup_object;
    }
    // Output is in a script tag with type application/ld+json.
    if (
      $element['#tag'] == 'script' &&
      !empty($element['#attributes']) &&
      !empty($element['#attributes']['type']) &&
      $element['#attributes']['type'] == 'application/ld+json'
      ) {

      // Decode existing schema info, abort if not schema.org.
      $schema = Json::decode($element['#value']);
      if (empty($schema['@context']) || $schema['@context'] != 'https://schema.org') {
        return $markup_object;
      }

      // Return cached result.
      $cid = self::getCid();
      if (empty($cid)) {
        // Missing cid, return an empty result.
        return Markup::create('');
      }
      $cache_bin = \Drupal::cache('render');
      $cache = $cache_bin->get($cid);
      if ($cache) {
        return Markup::create($cache->data);
      }

      // Load existing faq questions in output.
      $faqs = NULL;
      foreach ($schema['@graph'] as $delta => $entry) {
        if ($entry['@type'] == 'FAQPage') {
          $faqs = &$schema['@graph'][$delta]['mainEntity'];
          break;
        }
      }
      // If output is not empty, add the questions.
      if (!empty(self::$output)) {
        self::addQuestions($faqs);
        $result = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
      }

      // No questions in output and no other schemas, set result to empty.
      if ((empty($faqs[0]['name']) || $faqs = NULL) && count($schema['@graph']) <= 1) {
        $result = '';
      }
      $cache_bin->set($cid, $result, Cache::PERMANENT, self::$tags);
      return Markup::create($result);
    }
    return $markup_object;
  }

  /**
   * Add questions from collected output.
   *
   * @param array $faqs
   *   The faq array.
   */
  protected static function addQuestions(array &$faqs = NULL) {
    $dom = new \DOMDocument();
    $dom->loadHTML(self::$output, LIBXML_NOERROR);
    $finder = new \DomXPath($dom);

    // Search content for iq-faq-item elements.
    $questions = iterator_to_array($finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' iq-faq-item-question ')]"));
    $answers = iterator_to_array($finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' iq-faq-item-answer ')]"));
    if ($faqs == NULL) {
      $faqs = ['@type' => 'FAQPage', 'mainEntity' => []];
      $schema['@graph'][] = &$faqs;
    }
    if (count($questions)) {
      $questions = array_map(fn($question) => trim(preg_replace('/\s\s+/', ' ', strip_tags((string) $question->ownerDocument->saveXML($question)))), $questions);
      $answers = array_map(fn($answer) => trim(preg_replace('/\s\s+/', ' ', strip_tags((string) $answer->ownerDocument->saveXML($answer)))), $answers);
      $url = Url::fromRoute('<current>', [], ["absolute" => TRUE])->toString();
      for ($i = 0; $i < count($questions); $i++) {
        $question = $questions[$i];
        $hasQuestion = array_filter($faqs,
        function ($v) use ($question) {
          return !empty($v['name']) && $v['name'] == $question;
        });
        if (count($hasQuestion) == 0) {
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
    }
  }

  /**
   * Generate cache id.
   *
   * @return string
   *   The cache id.
   */
  protected static function getCid() {
    if (empty(self::$entity)) {
      return '';
    }
    $language = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
    return 'iq_faq:' . self::$entity->getEntityTypeId() . ':' . self::$entity->id() . ':' . $language->getId();
  }

}
