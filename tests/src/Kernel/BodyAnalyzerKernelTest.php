<?php

namespace Drupal\Tests\mailhandler_d8\Kernel;

use Drupal\inmail\Entity\AnalyzerConfig;
use Drupal\inmail\ProcessorResult;
use Drupal\KernelTests\KernelTestBase;
use Drupal\mailhandler_d8\MailhandlerAnalyzerResult;

/**
 * Tests the Body Analyzer plugin.
 *
 * @group mailhandler_d8
 */
class BodyAnalyzerKernelTest extends KernelTestBase {

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
   * Tests features of Body Analyzer plugin.
   */
  public function testBodyAnalyzer() {
    $raw_node_mail = $this->getFileContent('node.eml');
    /** @var \Drupal\inmail\MIME\MessageInterface $node_mail */
    $message = $this->parser->parseMessage($raw_node_mail);

    $result = new ProcessorResult();
    $body_analyzer = AnalyzerConfig::load('body');
    $analyzer_manager = \Drupal::service('plugin.manager.inmail.analyzer');

    /** @var \Drupal\mailhandler_d8\Plugin\inmail\Analyzer\BodyAnalyzer $analyzer */
    $analyzer = $analyzer_manager->createInstance($body_analyzer->getPluginId(), $body_analyzer->getConfiguration());
    $analyzer->analyze($message, $result);
    
    // Mailhandler analyzer result.
    /** @var \Drupal\mailhandler_d8\MailhandlerAnalyzerResultInterface $mailhandler_result */
    $mailhandler_result = $result->getAnalyzerResult(MailhandlerAnalyzerResult::TOPIC);

    $expected_processed_body = <<<EOF
Hello, Drupal!<br />
<br />
--<br />
Milos Bovan<br />
milos@example.com
EOF;

    // New lines are replaced with <br /> HTML tag.
    $this->assertEquals($expected_processed_body, $mailhandler_result->getBody());
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
