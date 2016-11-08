<?php

namespace Drupal\Tests\mailhandler\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Provides the base for analyzer tests.
 */
abstract class AnalyzerTestBase extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'mailhandler',
    'inmail',
    'system',
    'node',
    'user',
    'field',
  ];

  /**
   * The message parser
   *
   * @var \Drupal\inmail\MIME\MimeParser
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
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installConfig(['inmail', 'mailhandler', 'user']);
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
    $path = drupal_get_path('module', 'mailhandler') . '/tests/' . $filename;
    return file_get_contents(DRUPAL_ROOT . '/' . $path);
  }

}
