<?php

/**
 * This file is part of contao/package-manager.
 *
 * (c) Christian Schiffler <c.schiffler@cyberspectrum.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    contao/package-manager
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2015 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/contao/package-manager/blob/master/LICENSE MIT
 * @link       https://github.com/contao/package-manager
 * @filesource
 */

namespace AppBundle\Test;

use Symfony\Component\HttpKernel\Client;

/**
 * This class tests that all registered authentication methods on the auth route work and return a valid response.
 */
class AuthenticationTest extends WebTestCase
{
    /**
     * Data provider for authentication tests.
     *
     * @return array
     */
    public static function providerAuthenticationAttempts()
    {
        // Would love to perform this in setUpBeforeClass() but sadly this is called after the providers have been
        // examined. So we have to setup everything here.
        // The cleanup occurs as normally in the tearDownAfterClass() as usual.
        static::setWorkspaceShared();
        static::createFixture(
            'tenside/tenside.json',
            json_encode(
                [
                    'secret' => 's3cret',
                    'auth-password' => [
                        'john.doe' => [
                            'acl' => 15,
                            'salt' => '567079d37f5d99.46937191',
                            'username' => 'john.doe',
                            'password' => '$2y$13$567079d37f5d99.469371uoJtzacc/G5u3iqkB5cDmklbOIQlGbCK'
                        ]
                    ]
                ]
            )
        );

        $client = static::createClient();
        $user   = $client->getContainer()->get('tenside.user_provider')->loadUserByUsername('john.doe');

        return [
            [
                // No authorization data at all
                false
            ],
            [
                // Valid basic auth.
                true,
                ['HTTP_AUTHORIZATION' => 'Basic ' . base64_encode('john.doe:p4ssword')]
            ],
            [
                // Invalid basic auth.
                false,
                ['HTTP_AUTHORIZATION' => 'Basic ' . base64_encode('john.doe:pssword')]
            ],
            [
                // Invalid basic auth.
                false,
                ['HTTP_AUTHORIZATION' => 'Basic xyz']
            ],
            [
                // Valid jwt auth.
                true,
                ['HTTP_AUTHORIZATION' =>
                     'bearer ' . $client->getContainer()->get('tenside.jwt_authenticator')->getTokenForData($user)]
            ],
            [
                // Invalid jwt auth.
                false,
                ['HTTP_AUTHORIZATION' => 'Bearer not-a-token']
            ],
            [
                // Invalid jwt auth.
                false,
                ['HTTP_AUTHORIZATION' => 'Bearer']
            ],
            [
                // Valid post data.
                true,
                ['CONTENT_TYPE' => 'application/json'],
                '{"username":"john.doe", "password": "p4ssword"}'
            ],
            [
                // Invalid post data.
                false,
                ['CONTENT_TYPE' => 'application/json'],
                '{"username":"john.doe", "password": "invalid p4ssword"}'
            ],
            [
                // Invalid post data.
                false,
                ['CONTENT_TYPE' => 'application/json'],
                '{"unknown-field":"value", "another-unknown-field": "nonsense"}'
            ],
        ];
    }

    /**
     * Test an authentication attempt.
     *
     * @param bool        $expected The expected result.
     *
     * @param array       $headers  The request headers to send.
     *
     * @param string|null $content  The content to send.
     *
     * @return void
     *
     * @dataProvider providerAuthenticationAttempts
     */
    public function testAuth($expected, $headers = [], $content = null)
    {
        $client = static::createClient();

        $client->request(
            null === $content ? 'GET' : 'POST',
            '/api/v1/auth',
            [],
            [],
            $headers,
            $content
        );

        $client->getResponse();

        if ($expected) {
            $this->assertAuthenticated($client);
        } else {
            $this->assertNotAuthenticated($client);
        }
    }

    /**
     * Assert that the last request on the client authenticated successfully.
     *
     * @param Client $client The client.
     *
     * @return void
     */
    protected function assertAuthenticated($client)
    {
        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\JsonResponse', $response);
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('OK', $data['status']);
        $this->assertEquals(
            [
                'ROLE_NONE',
                'ROLE_UPGRADE',
                'ROLE_MANIPULATE_REQUIREMENTS',
                'ROLE_EDIT_COMPOSER_JSON',
                'ROLE_EDIT_APP_KERNEL'
            ],
            $data['acl']
        );
    }

    /**
     * Assert that the last request on the client was not authenticated.
     *
     * @param Client $client The client.
     *
     * @return void
     */
    protected function assertNotAuthenticated(Client $client)
    {
        $response = $client->getResponse();

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\JsonResponse', $response);
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('unauthorized', $data['status']);
    }
}
