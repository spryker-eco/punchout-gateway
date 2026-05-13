<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Service\PunchoutGateway;

use Codeception\Test\Unit;
use CXml\Model\CXml;
use CXml\Model\Response\PunchOutSetupResponse;
use CXml\Model\Status;
use CXml\Model\Url;
use DateTimeInterface;
use Exception;
use RuntimeException;
use SprykerEco\Service\PunchoutGateway\Builder\CxmlBuilderInterface;
use SprykerEco\Service\PunchoutGateway\Encoder\CxmlEncoderInterface;
use SprykerEco\Service\PunchoutGateway\PunchoutGatewayService;
use SprykerEco\Service\PunchoutGateway\PunchoutGatewayServiceFactory;

/**
 * @group SprykerEcoTest
 * @group Service
 * @group PunchoutGateway
 * @group PunchoutGatewayService
 * @group PunchoutGatewayServiceTest
 */
class PunchoutGatewayServiceTest extends Unit
{
    protected PunchoutGatewayServiceTester $tester;

    protected const string DEFAULT_DTD_URI = 'http://xml.cxml.org/schemas/cXML/1.2.063/cXML.dtd';

    protected const string RETURN_URL = 'https://example.com/return';

    protected const string CUSTOM_DTD_URI = 'https://custom.example.com/cxml.dtd';

    public function testEncodeCxmlDelegatesToEncoder(): void
    {
        $cxml = $this->createMock(CXml::class);
        $expected = 'encoded-xml-string';

        $encoder = $this->createMock(CxmlEncoderInterface::class);
        $encoder->expects($this->once())->method('encodeCxml')->with($cxml)->willReturn($expected);

        $service = $this->createServiceWithFactory($this->createFactoryMockWithEncoder($encoder));

        $this->assertSame($expected, $service->encodeCxml($cxml));
    }

    public function testEncodeCxmlReturnsValidXmlString(): void
    {
        $cxml = $this->createService()->buildCxmlStatus(new Status());
        $result = $this->createService()->encodeCxml($cxml);

        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $result);
        $this->assertStringContainsString('<!DOCTYPE cXML SYSTEM "', $result);
        $this->assertStringContainsString('<cXML payloadID=', $result);
        $this->assertStringContainsString('</cXML>', $result);
    }

    public function testEncodeCxmlPreservesStatusCodeAndText(): void
    {
        $cxml = $this->createService()->buildCxmlStatus(new Status(404, 'Not Found', 'Resource missing'));
        $result = $this->createService()->encodeCxml($cxml);

        $this->assertStringContainsString('code="404"', $result);
        $this->assertStringContainsString('text="Not Found"', $result);
        $this->assertStringContainsString('Resource missing', $result);
    }

    public function testEncodeCxmlPreservesReturnUrl(): void
    {
        $payload = new PunchOutSetupResponse(new Url(static::RETURN_URL));
        $cxml = $this->createService()->buildCxmlPayload($payload);
        $result = $this->createService()->encodeCxml($cxml);

        $this->assertStringContainsString(static::RETURN_URL, $result);
    }

    public function testEncodeCxmlUsesCustomDtdUri(): void
    {
        $cxml = $this->createService()->buildCxmlStatus(new Status());
        $cxml->setDtdUri(static::CUSTOM_DTD_URI);
        $result = $this->createService()->encodeCxml($cxml);

        $this->assertStringContainsString(static::CUSTOM_DTD_URI, $result);
    }

    // decodeCxml

    public function testDecodeCxmlDelegatesToEncoder(): void
    {
        $xml = '<?xml version="1.0"?><cXML/>';
        $expected = $this->createMock(CXml::class);

        $encoder = $this->createMock(CxmlEncoderInterface::class);
        $encoder->expects($this->once())->method('decodeCxml')->with($xml)->willReturn($expected);

        $service = $this->createServiceWithFactory($this->createFactoryMockWithEncoder($encoder));

        $this->assertSame($expected, $service->decodeCxml($xml));
    }

    public function testDecodeCxmlParsesMinimalResponseXml(): void
    {
        $xml = $this->buildMinimalResponseXml('test.payload.123', '2023-01-23T16:00:00+00:00', 200, 'OK');
        $cxml = $this->createService()->decodeCxml($xml);

        $this->assertSame('test.payload.123', $cxml->payloadId);
        $this->assertNotNull($cxml->response);
        $this->assertSame(200, $cxml->response->status->code);
        $this->assertSame('OK', $cxml->response->status->text);
    }

    public function testDecodeCxmlParsesRequestWithHeader(): void
    {
        $cxml = $this->createService()->decodeCxml($this->buildRequestWithHeaderXml());

        $this->assertNotNull($cxml->header);
        $this->assertSame('inbound@prominate-platform.com', $cxml->header->from->credential->identity);
        $this->assertSame('supplier@supplier.com', $cxml->header->to->credential->identity);
        $this->assertSame('inbound@prominate-platform.com', $cxml->header->sender->credential->identity);
    }

    public function testDecodeCxmlRestoresCustomDtdUri(): void
    {
        // DOCTYPE must be on its own line — the SDK regex /<!doctype.+"(.+)"/ uses greedy .+
        // which overshoots when the entire document is on one line.
        $xml = implode("\n", [
            '<?xml version="1.0" encoding="UTF-8"?>',
            sprintf('<!DOCTYPE cXML SYSTEM "%s">', static::CUSTOM_DTD_URI),
            '<cXML payloadID="p1" timestamp="2023-01-23T16:00:00+00:00">',
            '    <Response><Status code="200" text="OK"/></Response>',
            '</cXML>',
        ]);
        $cxml = $this->createService()->decodeCxml($xml);

        $this->assertSame(static::CUSTOM_DTD_URI, $cxml->dtdUri);
    }

    public function testDecodeCxmlThrowsOnEmptyString(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot deserialize empty string');

        $this->createService()->decodeCxml('');
    }

    public function testDecodeCxmlThrowsOnWhitespaceOnlyString(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot deserialize empty string');

        $this->createService()->decodeCxml('   ');
    }

    public function testDecodeCxmlThrowsOnMalformedXml(): void
    {
        $this->expectException(Exception::class);

        $this->createService()->decodeCxml('<not-valid-cxml>broken<</not-valid-cxml>');
    }

    // buildCxmlPayload

    public function testBuildCxmlPayloadDelegatesToBuilder(): void
    {
        $payload = new PunchOutSetupResponse(new Url(static::RETURN_URL));
        $expected = $this->createMock(CXml::class);

        $builder = $this->createMock(CxmlBuilderInterface::class);
        $builder->expects($this->once())->method('buildCxmlPayload')->with($payload)->willReturn($expected);

        $service = $this->createServiceWithFactory($this->createFactoryMockWithBuilder($builder));

        $this->assertSame($expected, $service->buildCxmlPayload($payload));
    }

    public function testBuildCxmlPayloadReturnsCxmlInstance(): void
    {
        $payload = new PunchOutSetupResponse(new Url(static::RETURN_URL));

        $this->assertInstanceOf(CXml::class, $this->createService()->buildCxmlPayload($payload));
    }

    public function testBuildCxmlPayloadEmbedsPayloadInResponse(): void
    {
        $payload = new PunchOutSetupResponse(new Url(static::RETURN_URL));
        $cxml = $this->createService()->buildCxmlPayload($payload);

        $this->assertNotNull($cxml->response);
        $this->assertSame($payload, $cxml->response->payload);
        $this->assertNull($cxml->request);
        $this->assertNull($cxml->message);
    }

    public function testBuildCxmlPayloadSetsDefaultOkStatus(): void
    {
        $cxml = $this->createService()->buildCxmlPayload(new PunchOutSetupResponse(new Url(static::RETURN_URL)));

        $this->assertSame(200, $cxml->response->status->code);
        $this->assertSame('OK', $cxml->response->status->text);
        $this->assertNull($cxml->response->status->message);
    }

    public function testBuildCxmlPayloadHasNoHeader(): void
    {
        $cxml = $this->createService()->buildCxmlPayload(new PunchOutSetupResponse(new Url(static::RETURN_URL)));

        $this->assertNull($cxml->header);
    }

    public function testBuildCxmlPayloadPopulatesPayloadIdAndTimestamp(): void
    {
        $cxml = $this->createService()->buildCxmlPayload(new PunchOutSetupResponse(new Url(static::RETURN_URL)));

        $this->assertNotEmpty($cxml->payloadId);
        $this->assertInstanceOf(DateTimeInterface::class, $cxml->timestamp);
    }

    public function testBuildCxmlPayloadUsesDefaultDtdUri(): void
    {
        $cxml = $this->createService()->buildCxmlPayload(new PunchOutSetupResponse(new Url(static::RETURN_URL)));

        $this->assertSame(static::DEFAULT_DTD_URI, $cxml->dtdUri);
    }

    public function testBuildCxmlPayloadGetStatusReturnsOkStatus(): void
    {
        $cxml = $this->createService()->buildCxmlPayload(new PunchOutSetupResponse(new Url(static::RETURN_URL)));

        $status = $cxml->getStatus();
        $this->assertNotNull($status);
        $this->assertSame(200, $status->code);
    }

    public function testBuildCxmlPayloadEachCallProducesDistinctPayloadId(): void
    {
        $payload = new PunchOutSetupResponse(new Url(static::RETURN_URL));
        $service = $this->createService();

        $cxml1 = $service->buildCxmlPayload($payload);
        $cxml2 = $service->buildCxmlPayload($payload);

        $this->assertNotSame($cxml1->payloadId, $cxml2->payloadId);
    }

    // buildCxmlStatus

    public function testBuildCxmlStatusDelegatesToBuilder(): void
    {
        $status = new Status();
        $expected = $this->createMock(CXml::class);

        $builder = $this->createMock(CxmlBuilderInterface::class);
        $builder->expects($this->once())->method('buildCxmlStatus')->with($status)->willReturn($expected);

        $service = $this->createServiceWithFactory($this->createFactoryMockWithBuilder($builder));

        $this->assertSame($expected, $service->buildCxmlStatus($status));
    }

    public function testBuildCxmlStatusWrapsStatusInResponse(): void
    {
        $status = new Status();
        $cxml = $this->createService()->buildCxmlStatus($status);

        $this->assertNotNull($cxml->response);
        $this->assertSame($status, $cxml->response->status);
        $this->assertNull($cxml->response->payload);
        $this->assertNull($cxml->request);
        $this->assertNull($cxml->message);
        $this->assertNull($cxml->header);
    }

    public function testBuildCxmlStatusPreservesErrorStatusValues(): void
    {
        $status = new Status(500, 'Internal Server Error', 'boom');
        $cxml = $this->createService()->buildCxmlStatus($status);

        $this->assertSame(500, $cxml->response->status->code);
        $this->assertSame('Internal Server Error', $cxml->response->status->text);
        $this->assertSame('boom', $cxml->response->status->message);
    }

    public function testBuildCxmlStatusPopulatesPayloadIdAndTimestamp(): void
    {
        $cxml = $this->createService()->buildCxmlStatus(new Status());

        $this->assertNotEmpty($cxml->payloadId);
        $this->assertInstanceOf(DateTimeInterface::class, $cxml->timestamp);
    }

    public function testBuildCxmlStatusUsesDefaultDtdUri(): void
    {
        $this->assertSame(static::DEFAULT_DTD_URI, $this->createService()->buildCxmlStatus(new Status())->dtdUri);
    }

    public function testBuildCxmlStatusGetStatusReturnsSameInstance(): void
    {
        $status = new Status(503, 'Service Unavailable');
        $cxml = $this->createService()->buildCxmlStatus($status);

        $this->assertSame($status, $cxml->getStatus());
    }

    public function testBuildCxmlStatusEachCallProducesDistinctPayloadId(): void
    {
        $status = new Status();
        $service = $this->createService();

        $cxml1 = $service->buildCxmlStatus($status);
        $cxml2 = $service->buildCxmlStatus($status);

        $this->assertNotSame($cxml1->payloadId, $cxml2->payloadId);
    }

    // Roundtrip

    public function testEncodeThenDecodePreservesStatusCxml(): void
    {
        $service = $this->createService();
        $original = $service->buildCxmlStatus(new Status(201, 'Created'));
        $decoded = $service->decodeCxml($service->encodeCxml($original));

        $this->assertSame($original->payloadId, $decoded->payloadId);
        $this->assertNotNull($decoded->response);
        $this->assertSame(201, $decoded->response->status->code);
        $this->assertSame('Created', $decoded->response->status->text);
        $this->assertInstanceOf(DateTimeInterface::class, $decoded->timestamp);
    }

    protected function createService(): PunchoutGatewayService
    {
        $service = $this->getMockBuilder(PunchoutGatewayService::class)
            ->onlyMethods(['getFactory'])
            ->getMock();

        $service->method('getFactory')->willReturn(new PunchoutGatewayServiceFactory());

        return $service;
    }

    protected function createServiceWithFactory(PunchoutGatewayServiceFactory $factory): PunchoutGatewayService
    {
        $service = $this->getMockBuilder(PunchoutGatewayService::class)
            ->onlyMethods(['getFactory'])
            ->getMock();

        $service->method('getFactory')->willReturn($factory);

        return $service;
    }

    protected function createFactoryMockWithEncoder(CxmlEncoderInterface $encoder): PunchoutGatewayServiceFactory
    {
        $factory = $this->getMockBuilder(PunchoutGatewayServiceFactory::class)
            ->onlyMethods(['createCxmlEncoder'])
            ->getMock();

        $factory->method('createCxmlEncoder')->willReturn($encoder);

        return $factory;
    }

    protected function createFactoryMockWithBuilder(CxmlBuilderInterface $builder): PunchoutGatewayServiceFactory
    {
        $factory = $this->getMockBuilder(PunchoutGatewayServiceFactory::class)
            ->onlyMethods(['createCxmlBuilder'])
            ->getMock();

        $factory->method('createCxmlBuilder')->willReturn($builder);

        return $factory;
    }

    protected function buildMinimalResponseXml(string $payloadId, string $timestamp, int $code, string $text): string
    {
        return sprintf(
            '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE cXML SYSTEM "%s"><cXML payloadID="%s" timestamp="%s"><Response><Status code="%d" text="%s"/></Response></cXML>',
            static::DEFAULT_DTD_URI,
            $payloadId,
            $timestamp,
            $code,
            $text,
        );
    }

    protected function buildRequestWithHeaderXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE cXML SYSTEM "http://xml.cxml.org/schemas/cXML/1.2.050/cXML.dtd">
<cXML payloadID="933695160890" timestamp="2023-01-23T16:00:06-01:00" xml:lang="en-US">
    <Header>
        <From>
            <Credential domain="NetworkId">
                <Identity>inbound@prominate-platform.com</Identity>
            </Credential>
        </From>
        <To>
            <Credential domain="NetworkId">
                <Identity>supplier@supplier.com</Identity>
            </Credential>
        </To>
        <Sender>
            <Credential domain="NetworkId">
                <Identity>inbound@prominate-platform.com</Identity>
                <SharedSecret>s3cr3t</SharedSecret>
            </Credential>
            <UserAgent>Workchairs cXML Application</UserAgent>
        </Sender>
    </Header>
    <Request deploymentMode="test">
        <PunchOutSetupRequest operation="create">
            <BuyerCookie>550bce3e592023b2e7b015307f965133</BuyerCookie>
            <BrowserFormPost>
                <URL>https://prominate-platform.com/hook-url</URL>
            </BrowserFormPost>
        </PunchOutSetupRequest>
    </Request>
</cXML>
XML;
    }
}
