<?php

namespace Drupal\Tests\mailhandler_d8\Kernel;

use Drupal\inmail\Entity\AnalyzerConfig;
use Drupal\inmail\ProcessorResult;
use Drupal\mailhandler_d8\MailhandlerAnalyzerResult;
use Drupal\node\Entity\NodeType;

/**
 * Tests the Entity Type Analyzer plugin.
 *
 * @group mailhandler_d8
 */
class EntityTypeAnalyzerKernelTest extends AnalyzerTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests features of Entity Type Analyzer plugin.
   */
  public function testEntityTypeAnalyzer() {
    $raw_message = $this->getFileContent('node.eml');
    /** @var \Drupal\inmail\MIME\MessageInterface $node_mail */
    $message = $this->parser->parseMessage($raw_message);

    $result = new ProcessorResult();
    $entity_type_analyzer = AnalyzerConfig::load('entity_type');

    /** @var \Drupal\mailhandler_d8\Plugin\inmail\Analyzer\EntityTypeAnalyzer $analyzer */
    $analyzer = $this->analyzerManager->createInstance($entity_type_analyzer->getPluginId(), $entity_type_analyzer->getConfiguration());
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

}
