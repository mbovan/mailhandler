<?php

namespace Drupal\Tests\mailhandler_d8\Kernel;

use Drupal\inmail\Entity\AnalyzerConfig;
use Drupal\inmail\ProcessorResult;
use Drupal\KernelTests\KernelTestBase;
use Drupal\mailhandler_d8\MailhandlerAnalyzerResult;

/**
 * Tests the Footer Analyzer plugin.
 *
 * @group mailhandler_d8
 */
class FooterAnalyzerKernelTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'mailhandler_d8',
    'inmail',
    'system',
    'node',
    'user',
    'field',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installConfig(['inmail', 'mailhandler_d8']);

    $this->parser = \Drupal::service('inmail.mime_parser');
  }

  /**
   * Tests features of Footer Analyzer plugin.
   */
  public function testFooterAnalyzer() {
    $raw_node_mail = $this->getFileContent('node.eml');
    /** @var \Drupal\inmail\MIME\MessageInterface $node_mail */
    $message = $this->parser->parseMessage($raw_node_mail);

    $result = new ProcessorResult();
    $footer_analyzer = AnalyzerConfig::load('footer');
    $analyzer_manager = \Drupal::service('plugin.manager.inmail.analyzer');

    /** @var \Drupal\mailhandler_d8\Plugin\inmail\Analyzer\FooterAnalyzer $analyzer */
    $analyzer = $analyzer_manager->createInstance($footer_analyzer->getPluginId(), $footer_analyzer->getConfiguration());
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
    $analyzer = $analyzer_manager->createInstance($footer_analyzer->getPluginId(), $footer_analyzer->getConfiguration());
    $analyzer->analyze($message, $result);
    /** @var \Drupal\mailhandler_d8\MailhandlerAnalyzerResultInterface $mailhandler_result */
    $mailhandler_result = $result->getAnalyzerResult(MailhandlerAnalyzerResult::TOPIC);

    $this->assertEquals(NULL, $mailhandler_result->getBody());
    $this->assertEquals(NULL, $mailhandler_result->getFooter());
  }

  /**
   * Returns the content of a requested file.
   *
   * See \Drupal\Tests\inmail\Kernel\ModeratorForwardTest.
   *
   * @param string $filename
   *   The name of the file.
   *
   * @return string
   *   The content of the file.
   */
  public function getFileContent($filename) {
    $path = drupal_get_path('module', 'mailhandler_d8') . '/tests/eml/' . $filename;
    return file_get_contents(DRUPAL_ROOT . '/' . $path);
  }

}
