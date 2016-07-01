<?php

namespace Drupal\Tests\mailhandler_d8\Kernel;

use Drupal\inmail\Entity\AnalyzerConfig;
use Drupal\inmail\ProcessorResult;
use Drupal\KernelTests\KernelTestBase;
use Drupal\mailhandler_d8\MailhandlerAnalyzerResult;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests the PGP Analyzer plugin.
 *
 * @group mailhandler_d8
 */
class PGPAnalyzerKernelTest extends KernelTestBase {

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
    $this->installEntitySchema('user');
    $this->installConfig(['inmail', 'mailhandler_d8', 'user']);
    $this->installSchema('system', ['sequences']);

    $this->parser = \Drupal::service('inmail.mime_parser');
  }

  /**
   * Tests features of PGP Analyzer plugin.
   */
  public function testPGAnalyzer() {
    $raw_signed_mail = $this->getFileContent('signed/inline.eml');
    /** @var \Drupal\inmail\MIME\MessageInterface $signed_mail */
    $signed_mail = $this->parser->parseMessage($raw_signed_mail);

    // Create a new role.
    $role = Role::create([
      'id' => 'mailhandler',
      'label' => 'Mailhandler',
    ]);
    $role->grantPermission('create blog content');
    $role->grantPermission('create page content');
    $role->save();

    // Create a new user with "Mailhandler" role.
    /** @var \Drupal\user\Entity\User $user */
    $user = User::create([
      'mail' => 'milos@example.com',
      'name' => 'Milos',
    ]);
    $user->addRole($role->id());
    $user->save();
    
    // Add a public key to the user.
    $user->set('mailhandler_gpg_key', ['public_key' => $this->getFileContent('keys/example.key')]);
    $user->save();

    $result = new ProcessorResult();
    $pgp_analyzer = AnalyzerConfig::load('pgp');
    $analyzer_manager = \Drupal::service('plugin.manager.inmail.analyzer');

    /** @var \Drupal\mailhandler_d8\Plugin\inmail\Analyzer\PGPAnalyzer $analyzer */
    $analyzer = $analyzer_manager->createInstance($pgp_analyzer->getPluginId(), $pgp_analyzer->getConfiguration());
    $analyzer->analyze($signed_mail, $result);

    // Mailhandler analyzer result.
    /** @var \Drupal\mailhandler_d8\MailhandlerAnalyzerResultInterface $mailhandler_result */
    $mailhandler_result = $result->getAnalyzerResult(MailhandlerAnalyzerResult::TOPIC);

    // @todo: Remove if condition after enabling GnuGP extension in tests.
    if (extension_loaded('gnupg')) {
      $this->assertEquals($signed_mail->getSubject(), $mailhandler_result->getSubject());
      $this->assertEquals('Hello world!', $mailhandler_result->getBody());
      $this->assertEquals($user, $mailhandler_result->getUser());
      $this->assertEquals('inline', $mailhandler_result->getPgpType());
    }
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
