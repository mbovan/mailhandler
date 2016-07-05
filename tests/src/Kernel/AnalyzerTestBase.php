<?php

namespace Drupal\Tests\mailhandler_d8\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Provides the base for analyzer tests.
 *
 * @group mailhandler_d8
 */
abstract class AnalyzerTestBase extends KernelTestBase {

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
   * The message parser
   *
   * @var \Drupal\inmail\MIME\Parser
   */
  protected $parser;

  /**
   * The analyzer manager
   *
   * @var \Drupal\inmail\AnalyzerManager
   */
  protected $analyzerManager;
  

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installConfig(['inmail', 'mailhandler_d8', 'user']);
    $this->installSchema('system', ['sequences']);

    $this->parser = \Drupal::service('inmail.mime_parser');
    $this->analyzerManager = \Drupal::service('plugin.manager.inmail.analyzer');
  }

  /**
   * Returns the content of a requested file.
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
