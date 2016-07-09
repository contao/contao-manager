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

namespace AppBundle\Test;

use Symfony\Component\HttpFoundation\Response;

/**
 * This class tests the Uicontroller
 */
class UiControllerTest extends WebTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();
        TestHomePathDeterminator::setHomePath($this->getTempDir());
    }

    /**
     * Provides the datasets for the index action.
     *
     * @return array
     */
    public function indexActionRedirect()
    {
        return [
            ['de,en-US;q=0.8,en;q=0.6', '/de'],
            ['', '/en'],
            ['en-US;q=0.8,en;q=0.6', '/en'],
            ['jp', '/en'],
        ];
    }

    /**
     * Test the index action.
     *
     * @param string $acceptHeader The accept header to use.
     *
     * @param string $url          The expected URL to redirect to.
     *
     * @return void
     *
     * @dataProvider indexActionRedirect
     */
    public function testIndexActionRedirects($acceptHeader, $url)
    {
        $client = $this->createClient();
        $client->request('GET', '/', [], [], ['HTTP_Accept-Language' => $acceptHeader]);

        $response = $client->getResponse();

        $this->assertTrue($response->isRedirect($url));
        $this->assertEquals(302, $response->getStatusCode());
    }
}
