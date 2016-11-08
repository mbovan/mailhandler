<?php

namespace Drupal\Tests\mailhandler\Kernel;

use Drupal\inmail\DefaultAnalyzerResult;
use Drupal\inmail\Entity\AnalyzerConfig;
use Drupal\inmail\ProcessorResult;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests the PGP Analyzer plugin.
 *
 * @group mailhandler
 */
class PGPAnalyzerKernelTest extends AnalyzerTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests features of PGP Analyzer plugin.
   */
  public function testPGAnalyzer() {
    $raw_signed_message = $this->getFileContent('eml/PGP_Signed_Inline.eml');
    /** @var \Drupal\inmail\MIME\MimeMessageInterface $signed_mail */
    $signed_mail = $this->parser->parseMessage($raw_signed_message);

    // Create a new role.
    $role = Role::create([
      'id' => 'mailhandler',
      'label' => 'Mailhandler',
    ]);
    $role->grantPermission('create blog content');
    $role->grantPermission('create page content');
    $role->save();

    // Create a new user with "Mailhandler" role.
    $user = User::create([
      'mail' => 'milos@example.com',
      'name' => 'Milos',
    ]);
    $user->addRole($role->id());
    $user->save();

    // Add a public key to the user.
    $user->set('mailhandler_gpg_key', ['public_key' => $this->getFileContent('keys/public.key')]);
    $user->save();

    $result = new ProcessorResult();
    $pgp_analyzer = AnalyzerConfig::load('pgp');

    /** @var \Drupal\mailhandler\Plugin\inmail\Analyzer\PGPAnalyzer $analyzer */
    $analyzer = $this->analyzerManager->createInstance($pgp_analyzer->getPluginId(), $pgp_analyzer->getConfiguration());
    $analyzer->analyze($signed_mail, $result);
    $result = $result->getAnalyzerResult();

    // @todo: Remove if condition after enabling GnuGP extension in tests.
    if (extension_loaded('gnupg')) {
      $this->assertEquals($signed_mail->getSubject(), $result->getSubject());
      $this->assertEquals('Hello world!', $result->getBody());
      $this->assertEquals($user, $result->getAccount());
      $this->assertEquals('inline', $result->getContext('pgp')->getContextValue()['pgp_type']);
    }
  }

}
