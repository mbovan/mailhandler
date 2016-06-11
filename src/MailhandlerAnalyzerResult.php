<?php

namespace Drupal\mailhandler_d8;

/**
 * Contains Mailhandler analyzer results.
 *
 * The setter methods only have effect the first time they are called, so values
 * are only writable once.
 *
 * @ingroup analyzer
 */
class MailhandlerAnalyzerResult extends MailhandlerAnalyzerResultBase {

  /**
   * Identifies this class in relation to other analyzer results.
   *
   * Use this as the $topic argument for ProcessorResultInterface methods.
   *
   * @see \Drupal\inmail\ProcessorResultInterface
   */
  const TOPIC = 'mailhandler';

  /**
   * {@inheritdoc}
   */
  public function label() {
    return t('Mailhandler');
  }

}
