<?php

/*
 * This file is part of the Europeana API package.
 *
 * (c) Matthias Vandermaesen <matthias@colada.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Europeana\Tests\Transport;

use Europeana\Exception\EuropeanaException;
use Europeana\Tests\AbstractTestCase;
use Europeana\Tests\Test\Payload\MockPayload;
use Europeana\Transport\ApiClient;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\History;
use GuzzleHttp\Subscriber\Mock;

/**
 * @author Matthias Vandermaesen <matthias@colada.be>
 */
class ApiClientTest extends AbstractTestCase
{
    const API_KEY = 'fake';

    public function testSend()
    {
        $history          = new History();
        $mock             = new Mock();

        $mockResponseData = [
            'ok'  => true,
            'foo' => 'bar',
        ];

        $mockPayload = new MockPayload();
        $mockPayload->setFoo('bar');

        $mockResponseBody = json_encode($mockResponseData);
        $mock->addResponse(sprintf(
            "HTTP/1.1 200 OK\r\nContent-Length: %d\r\n\r\n%s",
            strlen($mockResponseBody),
            $mockResponseBody
        ));

        $client = new Client();
        $client->getEmitter()->attach($history);
        $client->getEmitter()->attach($mock);

        $apiClient = new ApiClient(self::API_KEY, $client);
        $apiClient->send($mockPayload);

        $lastRequest = $history->getLastRequest();

        $expectedUrl = ApiClient::API_BASE_URL . '/' . ApiClient::API_VERSION . '/mock.json?foo=bar&wskey=' . self::API_KEY;

        $lastResponseContent = json_decode($history->getLastResponse()->getBody(), true);
        $this->assertEquals($mockResponseData, $lastResponseContent);
        $this->assertEquals($expectedUrl, $history->getLastRequest()->getUrl());
    }

    public function testSendWithoutKey()
    {
        /** @var PayloadInterface|\PHPUnit_Framework_MockObject_MockObject $mockPayload */
        $mockPayload = $this->getMock('Europeana\Payload\PayloadInterface');
        $apiClient   = new ApiClient();

        try {
            $apiClient->send($mockPayload);
        } catch (EuropeanaException $e) {
            $previous = $e->getPrevious();
            $this->assertInstanceOf('\InvalidArgumentException', $previous);
            $this->assertEquals(
                'You must supply an API key to send a payload, since you did not provide one during construction',
                $previous->getMessage()
            );

            return;
        }

        $this->markTestIncomplete('This test should have thrown an exception');
    }
}
