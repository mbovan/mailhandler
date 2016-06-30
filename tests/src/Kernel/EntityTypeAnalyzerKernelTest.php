<?php

namespace Drupal\Tests\mailhandler_d8\Kernel;

use Drupal\inmail\Entity\AnalyzerConfig;
use Drupal\inmail\ProcessorResult;
use Drupal\KernelTests\KernelTestBase;
use Drupal\mailhandler_d8\MailhandlerAnalyzerResult;
use Drupal\node\Entity\NodeType;

/**
 * Tests the Entity Type Analyzer plugin.
 *
 * @group mailhandler_d8
 */
class EntityTypeAnalyzerKernelTest extends KernelTestBase {

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
   * Tests features of Entity Type Analyzer plugin.
   */
  public function testEntityTypeAnalyzer() {
    $raw_node_mail = $this->getFileContent('node.eml');
    /** @var \Drupal\inmail\MIME\MessageInterface $node_mail */
    $message = $this->parser->parseMessage($raw_node_mail);

    $result = new ProcessorResult();
    $entity_type_analyzer = AnalyzerConfig::load('entity_type');
    $analyzer_manager = \Drupal::service('plugin.manager.inmail.analyzer');

    /** @var \Drupal\mailhandler_d8\Plugin\inmail\Analyzer\EntityTypeAnalyzer $analyzer */
    $analyzer = $analyzer_manager->createInstance($entity_type_analyzer->getPluginId(), $entity_type_analyzer->getConfiguration());
    $analyzer->analyze($message, $result);
    
    // Mailhandler analyzer result.
    /** @var \Drupal\mailhandler_d8\MailhandlerAnalyzerResultInterface $mailhandler_result */
    $mailhandler_result = $result->getAnalyzerResult(MailhandlerAnalyzerResult::TOPIC);

    $this->assertEquals('Google Summer of Code 2016', $mailhandler_result->getSubject());
    $this->assertEquals('node', $mailhandler_result->getEntityType());
    // The node type "page" is not recognized in the system.
    $this->assertEquals(NULL, $mailhandler_result->getBundle());

    // Create "page" node type.
    $page = NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ]);
    $page->save();

    $result = new ProcessorResult();
    $analyzer->analyze($message, $result);

    // Mailhandler analyzer result.
    /** @var \Drupal\mailhandler_d8\MailhandlerAnalyzerResultInterface $mailhandler_result */
    $mailhandler_result = $result->getAnalyzerResult(MailhandlerAnalyzerResult::TOPIC);
    $this->assertEquals('page', $mailhandler_result->getBundle());
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
