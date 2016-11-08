<?php

namespace Drupal\mailhandler\Plugin\inmail\Analyzer;

use Drupal\inmail\DefaultAnalyzerResult;
use Drupal\inmail\MIME\MimeMessageInterface;
use Drupal\inmail\Plugin\inmail\Analyzer\AnalyzerBase;
use Drupal\inmail\ProcessorResultInterface;

/**
 * A message footer analyzer.
 *
 * @ingroup analyzer
 *
 * Footer analyzer splits the message body on widely used footer patterns:
 *    - Per https://tools.ietf.org/html/rfc3676#section-4.3, message footer is
 *      separated by "-- \n".
 *    - "On {day}, {month} {date}, {year} at {hour}:{minute} {AM|PM}" is
 *      de-facto standard with Gmail.
 * If there is a footer match on one of these patterns, footer and body
 * properties are updated with new data.
 *
 * @Analyzer(
 *   id = "footer",
 *   label = @Translation("Footer Analyzer")
 * )
 */
class FooterAnalyzer extends AnalyzerBase {

  /**
   * {@inheritdoc}
   */
  public function analyze(MimeMessageInterface $message, ProcessorResultInterface $processor_result) {
    $result = $processor_result->getAnalyzerResult();

    $this->findFooter($message, $result);
  }

  /**
   * Finds and returns the message footer.
   *
   * @param \Drupal\inmail\MIME\MimeMessageInterface $message
   *   A mail message to be analyzed.
   * @param \Drupal\inmail\DefaultAnalyzerResult $result
   *   The analyzer result.
   */
  protected function findFooter(MimeMessageInterface $message, DefaultAnalyzerResult $result) {
    // Get a message body.
    $body = $result->getBody() ?: $message->getBody();
    $footer = NULL;

    // Per https://tools.ietf.org/html/rfc3676#section-4.3, footer/signature is
    // separated from the message with "-- \n".
    $body_match = preg_split('/\s*[\r\n]--\s+/', $body);

    if (count($body_match) > 1) {
      // Footer represents a string after the last occurrence of "-- \n" regex.
      $footer = end($body_match);
      $footer = trim($footer);

      // Update the analyzed body without footer.
      $footer_key = count($body_match) - 1;
      unset($body_match[$footer_key]);
      $body = implode("\n-- \n", $body_match);
      $result->setBody($body);
    }
    // Match "On {day}, {month} {date}, {year} at {hour}:{minute} {AM|PM}".
    elseif (preg_match('/On [A-Za-z]{3}, [A-Za-z]{3} [0-9]{1,2}, 20[0-9]{2} at [0-9]{1,2}:[0-9]{2} (AM|PM).+/', $body, $matches)) {
      $footer_line = reset($matches);
      $footer = strstr($body, $footer_line);
      $result->setBody(strstr($body, $footer_line, TRUE));
    }

    $result->setFooter($footer);
  }

}
