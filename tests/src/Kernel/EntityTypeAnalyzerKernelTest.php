<?php

namespace Drupal\Tests\mailhandler\Kernel;

use Drupal\inmail\DefaultAnalyzerResult;
use Drupal\inmail\Entity\AnalyzerConfig;
use Drupal\inmail\ProcessorResult;
use Drupal\node\Entity\NodeType;

/**
 * Tests the Entity Type Analyzer plugin.
 *
 * @group mailhandler
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
    $raw_message = $this->getFileContent('eml/Plain.eml');
    /** @var \Drupal\inmail\MIME\MimeMessageInterface $message */
    $message = $this->parser->parseMessage($raw_message);

    $result = new ProcessorResult();
    $entity_type_analyzer = AnalyzerConfig::load('entity_type');

    /** @var \Drupal\mailhandler\Plugin\inmail\Analyzer\EntityTypeAnalyzer $analyzer */
    $analyzer = $this->analyzerManager->createInstance($entity_type_analyzer->getPluginId(), $entity_type_analyzer->getConfiguration());
    $analyzer->analyze($message, $result);
    $result = $result->getAnalyzerResult();

    $this->assertEquals('Google Summer of Code 2016', $result->getSubject());
    $this->assertEquals('node', $result->getContext('entity_type')->getContextValue()['entity_type']);
    // The node type "page" is not recognized in the system.
    $this->assertEquals(NULL, $result->getContext('entity_type')->getContextValue()['bundle']);

    // Create "page" node type.
    $page = NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ]);
    $page->save();

    $result = new ProcessorResult();
    $analyzer->analyze($message, $result);
    $result = $result->getAnalyzerResult();

    $this->assertEquals('page', $result->getContext('entity_type')->getContextValue()['bundle']);

    // Assert partial matching (entity type only) is handled properly.
    $raw_message = str_replace('[node][page]', '[user][#id]', $raw_message);
    /** @var \Drupal\inmail\MIME\MimeMessageInterface $message */
    $message = $this->parser->parseMessage($raw_message);
    $result = new ProcessorResult();
    $analyzer->analyze($message, $result);
    $result = $result->getAnalyzerResult();
    $this->assertEquals('user', $result->getContext('entity_type')->getContextValue()['entity_type']);
    $this->assertEquals(NULL, $result->getContext('entity_type')->getContextValue()['bundle']);
    $this->assertEquals('[#id] Google Summer of Code 2016', $result->getSubject());
  }

}
