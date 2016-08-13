<?php

/*
 * This file is part of the Ivory Google Map package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\Tests\GoogleMap\Service;

use Http\Client\HttpClient;
use Http\Message\MessageFactory;
use Ivory\GoogleMap\Base\Coordinate;
use Ivory\GoogleMap\Service\BusinessAccount;
use Ivory\GoogleMap\Service\TimeZone\TimeZone;
use Ivory\GoogleMap\Service\TimeZone\TimeZoneRequest;
use Ivory\GoogleMap\Service\TimeZone\TimeZoneStatus;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
class TimeZoneTest extends AbstractServiceTest
{
    /**
     * @var TimeZone
     */
    private $timeZone;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->timeZone = new TimeZone($this->getClient(), $this->getMessageFactory());
    }

    public function testProcess()
    {
        $response = $this->timeZone->process(new TimeZoneRequest(
            new Coordinate(39.6034810, -119.6822510),
            new \DateTime('@1331161200')
        ));

        $this->assertSame(TimeZoneStatus::OK, $response->getStatus());
        $this->assertSame(0, $response->getDstOffset());
        $this->assertSame(-28800, $response->getRawOffset());
        $this->assertSame('America/Los_Angeles', $response->getTimeZoneId());
        $this->assertSame('Pacific Standard Time', $response->getTimeZoneName());
    }

    public function testProcessWithLanguage()
    {
        $request = new TimeZoneRequest(
            new Coordinate(39.6034810, -119.6822510),
            new \DateTime('@1331161200')
        );

        $request->setLanguage('fr');

        $response = $this->timeZone->process($request);

        $this->assertSame(TimeZoneStatus::OK, $response->getStatus());
        $this->assertSame(0, $response->getDstOffset());
        $this->assertSame(-28800, $response->getRawOffset());
        $this->assertSame('America/Los_Angeles', $response->getTimeZoneId());
        $this->assertSame('heure normale du Pacifique nord-américain', $response->getTimeZoneName());
    }

    public function testProcessWithXmlFormat()
    {
        $this->timeZone->setFormat(TimeZone::FORMAT_XML);

        $response = $this->timeZone->process(new TimeZoneRequest(
            new Coordinate(39.6034810, -119.6822510),
            new \DateTime('@1331161200')
        ));

        $this->assertSame(TimeZoneStatus::OK, $response->getStatus());
        $this->assertSame(0, $response->getDstOffset());
        $this->assertSame(-28800, $response->getRawOffset());
        $this->assertSame('America/Los_Angeles', $response->getTimeZoneId());
        $this->assertSame('Pacific Standard Time', $response->getTimeZoneName());
    }

    public function testProcessWithApiKey()
    {
        $this->timeZone = new TimeZone(
            $client = $this->createHttpClientMock(),
            $messageFactory = $this->createMessageFactoryMock()
        );

        $this->timeZone->setKey('api-key');

        $request = $this->createTimeZoneRequestMock();
        $request
            ->expects($this->once())
            ->method('buildQuery')
            ->will($this->returnValue($query = ['foo' => 'bar']));

        $messageFactory
            ->expects($this->once())
            ->method('createRequest')
            ->with(
                $this->identicalTo('GET'),
                $this->identicalTo(
                    $url = 'https://maps.googleapis.com/maps/api/timezone/json?foo=bar&key=api-key'
                )
            )
            ->will($this->returnValue($httpRequest = $this->createHttpRequestMock()));

        $client
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->identicalTo($httpRequest))
            ->will($this->returnValue($httpResponse = $this->createHttpResponseMock()));

        $httpResponse
            ->expects($this->once())
            ->method('getBody')
            ->will($this->returnValue($httpStream = $this->createHttpStreamMock()));

        $httpStream
            ->expects($this->once())
            ->method('__toString')
            ->will($this->returnValue('{"status":"OK","dstOffset":0,"rawOffset":-28800,"timeZoneId":"America/Los_Angeles","timeZoneName":"Pacific Standard Time"}'));

        $response = $this->timeZone->process($request);

        $this->assertSame(TimeZoneStatus::OK, $response->getStatus());
        $this->assertSame(0, $response->getDstOffset());
        $this->assertSame(-28800, $response->getRawOffset());
        $this->assertSame('America/Los_Angeles', $response->getTimeZoneId());
        $this->assertSame('Pacific Standard Time', $response->getTimeZoneName());
    }

    public function testProcessWithBusinessAccount()
    {
        $this->timeZone = new TimeZone(
            $client = $this->createHttpClientMock(),
            $messageFactory = $this->createMessageFactoryMock()
        );

        $request = $this->createTimeZoneRequestMock();
        $request
            ->expects($this->once())
            ->method('buildQuery')
            ->will($this->returnValue($query = ['foo' => 'bar']));

        $messageFactory
            ->expects($this->once())
            ->method('createRequest')
            ->with(
                $this->identicalTo('GET'),
                $this->identicalTo(
                    $url = 'https://maps.googleapis.com/maps/api/timezone/json?foo=bar&signature=signature'
                )
            )
            ->will($this->returnValue($httpRequest = $this->createHttpRequestMock()));

        $client
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->identicalTo($httpRequest))
            ->will($this->returnValue($httpResponse = $this->createHttpResponseMock()));

        $httpResponse
            ->expects($this->once())
            ->method('getBody')
            ->will($this->returnValue($httpStream = $this->createHttpStreamMock()));

        $httpStream
            ->expects($this->once())
            ->method('__toString')
            ->will($this->returnValue('{"status":"OK","dstOffset":0,"rawOffset":-28800,"timeZoneId":"America/Los_Angeles","timeZoneName":"Pacific Standard Time"}'));

        $businessAccount = $this->createBusinessAccountMock();
        $businessAccount
            ->expects($this->once())
            ->method('signUrl')
            ->with($this->equalTo('https://maps.googleapis.com/maps/api/timezone/json?foo=bar'))
            ->will($this->returnValue($url));

        $this->timeZone->setBusinessAccount($businessAccount);

        $response = $this->timeZone->process($request);

        $this->assertSame(TimeZoneStatus::OK, $response->getStatus());
        $this->assertSame(0, $response->getDstOffset());
        $this->assertSame(-28800, $response->getRawOffset());
        $this->assertSame('America/Los_Angeles', $response->getTimeZoneId());
        $this->assertSame('Pacific Standard Time', $response->getTimeZoneName());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|HttpClient
     */
    private function createHttpClientMock()
    {
        return $this->createMock(HttpClient::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageFactory
     */
    private function createMessageFactoryMock()
    {
        return $this->createMock(MessageFactory::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RequestInterface
     */
    private function createHttpRequestMock()
    {
        return $this->createMock(RequestInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ResponseInterface
     */
    private function createHttpResponseMock()
    {
        return $this->createMock(ResponseInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|StreamInterface
     */
    private function createHttpStreamMock()
    {
        return $this->createMock(StreamInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|BusinessAccount
     */
    private function createBusinessAccountMock()
    {
        return $this->createMock(BusinessAccount::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TimeZoneRequest
     */
    private function createTimeZoneRequestMock()
    {
        return $this->createMock(TimeZoneRequest::class);
    }
}
