<?php

/**
 * @see       https://github.com/laminas/laminas-authentication for the canonical source repository
 * @copyright https://github.com/laminas/laminas-authentication/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-authentication/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Authentication\Validator;

use Laminas\Authentication\Adapter\ValidatableAdapterInterface;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Exception;
use Laminas\Authentication\Result as AuthenticationResult;
use Laminas\Authentication\Validator\Authentication as AuthenticationValidator;
use LaminasTest\Authentication as AuthTest;
use PHPUnit\Framework\TestCase;

class AuthenticationTest extends TestCase
{
    /**
     * @var AuthenticationValidator
     */
    protected $validator;

    /**
     * @var AuthenticationService
     */
    protected $authService;

    /**
     * @var ValidatableAdapterInterface
     */
    protected $authAdapter;

    public function setUp(): void
    {
        $this->validator = new AuthenticationValidator();
        $this->authService = new AuthenticationService();
        $this->authAdapter = new AuthTest\TestAsset\ValidatableAdapter();
    }

    public function testOptions()
    {
        $auth = new AuthenticationValidator([
            'adapter' => $this->authAdapter,
            'service' => $this->authService,
            'identity' => 'username',
            'credential' => 'password',
        ]);
        $this->assertSame($auth->getAdapter(), $this->authAdapter);
        $this->assertSame($auth->getService(), $this->authService);
        $this->assertSame($auth->getIdentity(), 'username');
        $this->assertSame($auth->getCredential(), 'password');
    }

    public function testConstructorOptionCodeMapOverridesDefaultMap()
    {
        $authAdapter = new AuthTest\TestAsset\ValidatableAdapter(AuthenticationResult::FAILURE_UNCATEGORIZED);
        $auth = new AuthenticationValidator([
            'adapter' => $authAdapter,
            'service' => $this->authService,
            'identity' => 'username',
            'credential' => 'password',
            'code_map' => [
                AuthenticationResult::FAILURE_UNCATEGORIZED => AuthenticationValidator::IDENTITY_NOT_FOUND,
            ]
        ]);
        $this->assertFalse($auth->isValid());
        $this->assertArrayHasKey(
            AuthenticationValidator::IDENTITY_NOT_FOUND,
            $auth->getMessages(),
            print_r($auth->getMessages(), true)
        );
    }

    public function testConstructorOptionCodeMapUsesDefaultMapForOmittedCodes()
    {
        $authAdapter = new AuthTest\TestAsset\ValidatableAdapter(AuthenticationResult::FAILURE_IDENTITY_AMBIGUOUS);
        $auth = new AuthenticationValidator([
            'adapter' => $authAdapter,
            'service' => $this->authService,
            'identity' => 'username',
            'credential' => 'password',
            'code_map' => [
                AuthenticationResult::FAILURE_UNCATEGORIZED => AuthenticationValidator::IDENTITY_NOT_FOUND,
            ]
        ]);
        $this->assertFalse($auth->isValid());
        $this->assertArrayHasKey(
            AuthenticationValidator::IDENTITY_AMBIGUOUS,
            $auth->getMessages(),
            print_r($auth->getMessages(), true)
        );
    }

    public function testCodeMapAllowsToSpecifyCustomCodes()
    {
        $authAdapter = new AuthTest\TestAsset\ValidatableAdapter(-999);
        $auth = new AuthenticationValidator([
            'adapter' => $authAdapter,
            'service' => $this->authService,
            'identity' => 'username',
            'credential' => 'password',
            'code_map' => [
                -999 => AuthenticationValidator::IDENTITY_NOT_FOUND,
            ]
        ]);
        $this->assertFalse($auth->isValid());
        $this->assertArrayHasKey(
            AuthenticationValidator::IDENTITY_NOT_FOUND,
            $auth->getMessages(),
            print_r($auth->getMessages(), true)
        );
    }

    public function testCodeMapAllowsToAddCustomMessageTemplates()
    {
        $auth = new AuthenticationValidator([
            'code_map' => [
                -999 => 'custom_error',
            ]
        ]);
        $templates = $auth->getMessageTemplates();
        $this->assertArrayHasKey(
            'custom_error',
            $templates,
            print_r($templates, true)
        );
    }

    /**
     * @depends testCodeMapAllowsToAddCustomMessageTemplates
     */
    public function testCodeMapCustomMessageTemplateValueDefaultsToGeneralMessageTemplate()
    {
        $auth = new AuthenticationValidator([
            'code_map' => [
                -999 => 'custom_error',
            ]
        ]);
        $templates = $auth->getMessageTemplates();
        $this->assertEquals($templates['general'], $templates['custom_error']);
    }

    /**
     * @depends testCodeMapAllowsToAddCustomMessageTemplates
     */
    public function testCustomMessageTemplateValueCanBeProvidedAsOption()
    {
        $auth = new AuthenticationValidator([
            'code_map' => [
                -999 => 'custom_error',
            ],
            'messages' => [
                'custom_error' => 'Custom Error'
            ]

        ]);
        $templates = $auth->getMessageTemplates();
        $this->assertEquals('Custom Error', $templates['custom_error']);
    }

    public function testCodeMapOptionRequiresMessageKeyToBeString()
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Message key in code_map option must be a non-empty string');
        $auth = new AuthenticationValidator([
            'code_map' => [
                -999 => [],
            ]
        ]);
    }

    public function testSetters()
    {
        $this->validator->setAdapter($this->authAdapter);
        $this->validator->setService($this->authService);
        $this->validator->setIdentity('username');
        $this->validator->setCredential('credential');
        $this->assertSame($this->validator->getAdapter(), $this->authAdapter);
        $this->assertSame($this->validator->getService(), $this->authService);
        $this->assertSame($this->validator->getIdentity(), 'username');
        $this->assertSame($this->validator->getCredential(), 'credential');
    }

    public function testNoIdentityThrowsRuntimeException()
    {
        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage('Identity must be set prior to validation');
        $this->validator->isValid('password');
    }

    public function testNoAdapterThrowsRuntimeException()
    {
        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage('Adapter must be set prior to validation');
        $this->validator->setService($this->authService);
        $this->validator->setIdentity('username');
        $this->validator->isValid('password');
    }

    public function testNoServiceThrowsRuntimeException()
    {
        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage('AuthenticationService must be set prior to validation');
        $this->validator->setAdapter($this->authAdapter);
        $this->validator->setIdentity('username');
        $this->validator->isValid('password');
    }

    public function testEqualsMessageTemplates()
    {
        $this->assertGreaterThan(0, count($this->validator->getOption('messageTemplates')));
    }

    public function testWithoutContext()
    {
        $this->validator->setAdapter($this->authAdapter);
        $this->validator->setService($this->authService);
        $this->validator->setIdentity('username');
        $this->validator->setCredential('credential');

        $this->assertEquals('username', $this->validator->getIdentity());
        $this->assertEquals('credential', $this->validator->getCredential());
        $this->assertTrue($this->validator->isValid());
    }

    public function testWithContext()
    {
        $this->validator->setAdapter($this->authAdapter);
        $this->validator->setService($this->authService);
        $this->validator->setIdentity('username');
        $this->validator->isValid('password', [
            'username' => 'myusername',
            'password' => 'mypassword',
        ]);
        $adapter = $this->validator->getAdapter();
        $this->assertEquals('myusername', $adapter->getIdentity());
        $this->assertEquals('mypassword', $adapter->getCredential());
    }

    public function errorMessagesProvider()
    {
        return [
            'failure' => [
                AuthenticationResult::FAILURE,
                false,
                [AuthenticationValidator::GENERAL => 'Authentication failed'],
            ],
            'identity-not-found' => [
                AuthenticationResult::FAILURE_IDENTITY_NOT_FOUND,
                false,
                [AuthenticationValidator::IDENTITY_NOT_FOUND => 'Invalid identity'],
            ],
            'identity-ambiguous' => [
                AuthenticationResult::FAILURE_IDENTITY_AMBIGUOUS,
                false,
                [AuthenticationValidator::IDENTITY_AMBIGUOUS => 'Identity is ambiguous'],
            ],
            'credential-invalid' => [
                AuthenticationResult::FAILURE_CREDENTIAL_INVALID,
                false,
                [AuthenticationValidator::CREDENTIAL_INVALID => 'Invalid password'],
            ],
            'uncategorized' => [
                AuthenticationResult::FAILURE_UNCATEGORIZED,
                false,
                [AuthenticationValidator::UNCATEGORIZED => 'Authentication failed'],
            ],
            'success' => [
                AuthenticationResult::SUCCESS,
                true,
                [],
            ],
        ];
    }

    /**
     * @dataProvider errorMessagesProvider
     * @param int   $code
     * @param bool  $valid
     * @param array $messages
     */
    public function testErrorMessages($code, $valid, $messages)
    {
        $adapter = new AuthTest\TestAsset\ValidatableAdapter($code);

        $this->validator->setAdapter($adapter);
        $this->validator->setService($this->authService);
        $this->validator->setIdentity('username');
        $this->validator->setCredential('credential');

        $this->assertEquals($valid, $this->validator->isValid());
        $this->assertEquals($messages, $this->validator->getMessages());
    }

    /**
     * Test using Authentication Service's adapter
     */
    public function testUsingAdapterFromService()
    {
        $this->authService->setAdapter($this->authAdapter);

        $this->validator->setService($this->authService);
        $this->validator->setIdentity('username');
        $this->validator->isValid('password');

        $this->assertEquals('username', $this->validator->getIdentity());
        $this->assertEquals('password', $this->validator->getCredential());
        $this->assertEquals('username', $this->authAdapter->getIdentity());
        $this->assertEquals('password', $this->authAdapter->getCredential());
        $this->assertNull($this->validator->getAdapter());
        $this->assertTrue($this->validator->isValid());
    }

    /**
     * Ensures that isValid() throws an exception when Authentication Service's
     * adapter is not an instance of ValidatableAdapterInterface
     */
    public function testUsingNonValidatableAdapterFromServiceThrowsRuntimeException()
    {
        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            '%s; %s given',
            ValidatableAdapterInterface::class,
            AuthTest\TestAsset\SuccessAdapter::class
        ));

        $adapter = new AuthTest\TestAsset\SuccessAdapter();
        $this->authService->setAdapter($adapter);

        $this->validator->setService($this->authService);
        $this->validator->setIdentity('username');
        $this->validator->isValid('password');
    }
}
