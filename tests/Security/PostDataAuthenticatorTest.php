<?php

/**
 * This file is part of contao/contao-manager.
 *
 * (c) Christian Schiffler <c.schiffler@cyberspectrum.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    contao/contao-manager
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2016 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/contao/contao-manager/blob/master/LICENSE MIT
 * @link       https://github.com/contao/contao-manager
 * @filesource
 */

namespace AppBundle\Test\Security;

use AppBundle\Security\PostDataAuthenticator;
use AppBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * This class tests the PostDataAuthenticator.
 */
class PostDataAuthenticatorTest extends WebTestCase
{
    /**
     * Test the start() method.
     *
     * @return void
     */
    public function testStart()
    {
        $auth = new PostDataAuthenticator($this->getMockForAbstractClass(UserPasswordEncoderInterface::class));

        $response = $auth->start(new Request(), new AuthenticationException());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertArraySubset(['status' => 'unauthorized'], json_decode($response->getContent(), true));
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * Test the getCredentials() method.
     *
     * @return void
     */
    public function testGetCredentials()
    {
        $auth = new PostDataAuthenticator($this->getMockForAbstractClass(UserPasswordEncoderInterface::class));

        $credentials = $auth->getCredentials(
            new Request([], [], [], [], [], [], json_encode(['username' => 'a.user', 'password' => 's3cret']))
        );

        $this->assertInstanceOf('stdClass', $credentials);
        $this->assertEquals('a.user', $credentials->username);
        $this->assertEquals('s3cret', $credentials->password);
    }

    /**
     * Test the getCredentials() method.
     *
     * @return void
     */
    public function testGetCredentialsWithEmptyRequest()
    {
        $auth = new PostDataAuthenticator($this->getMockForAbstractClass(UserPasswordEncoderInterface::class));

        $credentials = $auth->getCredentials(new Request());

        $this->assertNull($credentials);
    }

    /**
     * Test the getCredentials() method.
     *
     * @return void
     */
    public function testGetCredentialsWithOnlyUsername()
    {
        $auth = new PostDataAuthenticator($this->getMockForAbstractClass(UserPasswordEncoderInterface::class));

        $credentials = $auth->getCredentials(
            new Request([], [], [], [], [], [], json_encode(['username' => 'a.user']))
        );

        $this->assertNull($credentials);
    }

    /**
     * Test the getCredentials() method.
     *
     * @return void
     */
    public function testGetCredentialsWithOnlyPassword()
    {
        $auth = new PostDataAuthenticator($this->getMockForAbstractClass(UserPasswordEncoderInterface::class));

        $credentials = $auth->getCredentials(
            new Request([], [], [], [], [], [], json_encode(['password' => 's3cret']))
        );

        $this->assertNull($credentials);
    }

    /**
     * Test the getUser() method.
     *
     * @return void
     */
    public function testGetUser()
    {
        $auth = new PostDataAuthenticator($this->getMockForAbstractClass(UserPasswordEncoderInterface::class));

        $credentials = (object) [
            'username' => 'a.user',
            'password' => 's3cret',
        ];

        $userProvider = $this->getMockForAbstractClass(UserProviderInterface::class);

        $userProvider
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->with('a.user')
            ->willReturn($this->getMockForAbstractClass(UserInterface::class));

        $user = $auth->getUser($credentials, $userProvider);

        $this->assertInstanceOf(UserInterface::class, $user);
    }

    /**
     * Test the checkCredentials() method.
     *
     * @return void
     */
    public function testCheckCredentials()
    {
        $encoder = $this->getMockForAbstractClass(UserPasswordEncoderInterface::class);
        $auth    = new PostDataAuthenticator($encoder);
        $user    = $this->getMockForAbstractClass(UserInterface::class);

        $encoder->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, 's3cret')
            ->willReturn(true);

        $credentials = (object) [
            'username' => 'a.user',
            'password' => 's3cret',
        ];

        $this->assertTrue($auth->checkCredentials($credentials, $user));
    }

    /**
     * Test the checkCredentials() method.
     *
     * @return void
     */
    public function testCheckCredentialsWithInvalidCredentials()
    {
        $encoder = $this->getMockForAbstractClass(UserPasswordEncoderInterface::class);
        $auth    = new PostDataAuthenticator($encoder);

        $encoder->expects($this->once())
            ->method('isPasswordValid')
            ->willReturn(false);

        $credentials = (object) [
            'username' => 'a.user',
            'password' => 'not-s3cret',
        ];

        $this->assertFalse($auth->checkCredentials($credentials, $this->getMockForAbstractClass(UserInterface::class)));
    }

    /**
     * Test the onAuthenticationFailure() method.
     *
     * @return void
     */
    public function testOnAuthenticationFailure()
    {
        $auth = new PostDataAuthenticator($this->getMockForAbstractClass(UserPasswordEncoderInterface::class));

        $response = $auth->onAuthenticationFailure(new Request(), new AuthenticationException());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertArraySubset(['status' => 'unauthorized'], json_decode($response->getContent(), true));
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * Test the onAuthenticationSuccess() method.
     *
     * @return void
     */
    public function testOnAuthenticationSuccess()
    {
        $auth = new PostDataAuthenticator($this->getMockForAbstractClass(UserPasswordEncoderInterface::class));

        $this->assertNull($auth->onAuthenticationSuccess(
            new Request(),
            $this->getMockForAbstractClass(TokenInterface::class),
            ''
        ));
    }

    /**
     * Test the supportsRememberMe() method.
     *
     * @return void
     */
    public function testSupportsRememberMe()
    {
        $auth = new PostDataAuthenticator($this->getMockForAbstractClass(UserPasswordEncoderInterface::class));

        $this->assertFalse($auth->supportsRememberMe());
    }
}
