<?php

namespace Drupal\Tests\mailhandler_d8\Kernel;

use Drupal\inmail\Entity\AnalyzerConfig;
use Drupal\inmail\ProcessorResult;
use Drupal\mailhandler_d8\MailhandlerAnalyzerResult;

/**
 * Tests the Footer Analyzer plugin.
 *
 * @group mailhandler_d8
 */
class FooterAnalyzerKernelTest extends AnalyzerTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests features of Footer Analyzer plugin.
   */
  public function testFooterAnalyzer() {
    $raw_message = $this->getFileContent('node.eml');
    /** @var \Drupal\inmail\MIME\MessageInterface $node_mail */
    $message = $this->parser->parseMessage($raw_message);

    $result = new ProcessorResult();
    $footer_analyzer = AnalyzerConfig::load('footer');

    /** @var \Drupal\mailhandler_d8\Plugin\inmail\Analyzer\FooterAnalyzer $analyzer */
    $analyzer = $this->analyzerManager->createInstance($footer_analyzer->getPluginId(), $footer_analyzer->getConfiguration());
    $analyzer->analyze($message, $result);

    // Mailhandler analyzer result.
    /** @var \Drupal\mailhandler_d8\MailhandlerAnalyzerResultInterface $mailhandler_result */
    $mailhandler_result = $result->getAnalyzerResult(MailhandlerAnalyzerResult::TOPIC);

    $expected_processed_body = 'Hello, Drupal!';
    $expected_footer = 'Milos Bovan
milos@example.com';

    $this->assertEquals($expected_processed_body, $mailhandler_result->getBody());
    $this->assertEquals($expected_footer, $mailhandler_result->getFooter());

    // Assert footer is not processed for signed messages.
    $signed_mail = $this->getFileContent('signed/inline.eml');
    $message = $this->parser->parseMessage($signed_mail);
    $result = new ProcessorResult();
    $analyzer = $this->analyzerManager->createInstance($footer_analyzer->getPluginId(), $footer_analyzer->getConfiguration());
    $analyzer->analyze($message, $result);
    /** @var \Drupal\mailhandler_d8\MailhandlerAnalyzerResultInterface $mailhandler_result */
    $mailhandler_result = $result->getAnalyzerResult(MailhandlerAnalyzerResult::TOPIC);

    $this->assertEquals(NULL, $mailhandler_result->getBody());
    $this->assertEquals(NULL, $mailhandler_result->getFooter());
  }

}
