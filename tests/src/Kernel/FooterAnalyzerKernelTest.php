<?php

namespace Drupal\Tests\mailhandler\Kernel;

use Drupal\inmail\DefaultAnalyzerResult;
use Drupal\inmail\Entity\AnalyzerConfig;
use Drupal\inmail\ProcessorResult;

/**
 * Tests the Footer Analyzer plugin.
 *
 * @group mailhandler
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
    $raw_message = $this->getFileContent('eml/Plain.eml');
    /** @var \Drupal\inmail\MIME\MimeMessageInterface $node_mail */
    $message = $this->parser->parseMessage($raw_message);

    $result = new ProcessorResult();
    $footer_analyzer = AnalyzerConfig::load('footer');

    /** @var \Drupal\mailhandler\Plugin\inmail\Analyzer\FooterAnalyzer $analyzer */
    $analyzer = $this->analyzerManager->createInstance($footer_analyzer->getPluginId(), $footer_analyzer->getConfiguration());
    $analyzer->analyze($message, $result);
    $result = $result->getAnalyzerResult();

    $expected_processed_body = 'Hello, Drupal!';
    $expected_footer = <<<EOF
Milos Bovan
milos@example.com
EOF;

    $this->assertEquals($expected_processed_body, $result->getBody());
    $this->assertEquals($expected_footer, $result->getFooter());

    // Assert footer is not processed for signed messages.
    $signed_mail = $this->getFileContent('eml/PGP_Signed_Inline.eml');
    $message = $this->parser->parseMessage($signed_mail);
    $result = new ProcessorResult();
    $analyzer = $this->analyzerManager->createInstance($footer_analyzer->getPluginId(), $footer_analyzer->getConfiguration());
    $analyzer->analyze($message, $result);
    $result = $result->getAnalyzerResult();

    $this->assertEquals(NULL, $result->getBody());
    $this->assertEquals(NULL, $result->getFooter());
  }

}
