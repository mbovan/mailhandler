<?php

namespace Drupal\Tests\mailhandler_d8\Kernel;

use Drupal\inmail\DefaultAnalyzerResult;
use Drupal\inmail\Entity\AnalyzerConfig;
use Drupal\inmail\ProcessorResult;
use Drupal\user\Entity\User;

/**
 * Tests the Sender Analyzer plugin.
 *
 * @group mailhandler_d8
 */
class SenderAnalyzerKernelTest extends AnalyzerTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests features of Sender Analyzer plugin.
   */
  public function testSenderAnalyzer() {
    $raw_message = $this->getFileContent('eml/Plain.eml');
    /** @var \Drupal\inmail\MIME\MessageInterface $message */
    $message = $this->parser->parseMessage($raw_message);

    $result = new ProcessorResult();
    $result->ensureAnalyzerResult(DefaultAnalyzerResult::TOPIC, DefaultAnalyzerResult::createFactory());
    $sender_analyzer = AnalyzerConfig::load('sender');

    /** @var \Drupal\mailhandler_d8\Plugin\inmail\Analyzer\SenderAnalyzer $analyzer */
    $analyzer = $this->analyzerManager->createInstance($sender_analyzer->getPluginId(), $sender_analyzer->getConfiguration());
    $analyzer->analyze($message, $result);
    $result = $result->getAnalyzerResult(DefaultAnalyzerResult::TOPIC);

    $this->assertEquals('milos@example.com', $result->getSender());
    $this->assertFalse($result->isUserAuthenticated());
    $this->assertNull($result->getAccount());

    // Add a new user.
    $user = User::create([
      'mail' => 'milos@example.com',
      'name' => 'Milos',
    ]);
    $user->save();

    $result = new ProcessorResult();
    $result->ensureAnalyzerResult(DefaultAnalyzerResult::TOPIC, DefaultAnalyzerResult::createFactory());
    $analyzer->analyze($message, $result);
    $result = $result->getAnalyzerResult(DefaultAnalyzerResult::TOPIC);

    $this->assertEquals('milos@example.com', $result->getSender());
    $this->assertTrue($result->isUserAuthenticated());
    $this->assertEquals($user->id(), $result->getAccount()->id());
  }

}
