<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests\Unit\Database;

use GuzzleHttp\Psr7\Uri;
use Kreait\Firebase\Database\ApiClient;
use Kreait\Firebase\Database\Query;
use Kreait\Firebase\Database\Reference;
use Kreait\Firebase\Exception\Database\DatabaseError;
use Kreait\Firebase\Exception\Database\DatabaseNotFound;
use Kreait\Firebase\Exception\Database\UnsupportedQuery;
use Kreait\Firebase\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Throwable;

/**
 * @internal
 */
final class QueryTest extends UnitTestCase
{
    protected Uri $uri;
    protected Reference&MockObject $reference;
    protected ApiClient&MockObject $apiClient;
    protected Query $query;

    protected function setUp(): void
    {
        $this->uri = new Uri('http://domain.tld/some/path');

        $reference = $this->createMock(Reference::class);
        $reference->method('getURI')->willReturn($this->uri);

        $this->reference = $reference;

        $this->apiClient = $this->createMock(ApiClient::class);

        $this->query = new Query($this->reference, $this->apiClient);
    }

    /**
     * @test
     */
    public function getReference(): void
    {
        $this->assertSame($this->reference, $this->query->getReference());
    }

    /**
     * @test
     */
    public function getSnapshot(): void
    {
        $this->apiClient->method('get')->with($this->anything())->willReturn('value');

        $this->query->orderByKey()->equalTo(2)->getSnapshot();

        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function getValue(): void
    {
        $this->apiClient->method('get')->with($this->anything())->willReturn('value');

        $this->assertSame('value', $this->query->getValue());
    }

    /**
     * @test
     */
    public function getUri(): void
    {
        $uri = $this->query->getUri();

        $this->assertSame((string) $uri, (string) $this->query);
    }

    /**
     * @test
     */
    public function onlyOneSorterIsAllowed(): void
    {
        try {
            $this->query->orderByKey()->orderByValue();
        } catch (Throwable $e) {
            $this->assertInstanceOf(UnsupportedQuery::class, $e);
        }
    }

    /**
     * @test
     */
    public function wrapsApiExceptions(): void
    {
        $exception = new DatabaseError();

        $this->apiClient
            ->method('get')->with($this->anything())
            ->willThrowException($exception)
        ;

        $this->expectException(UnsupportedQuery::class);

        $this->query->getSnapshot();
    }

    /**
     * @test
     */
    public function indexNotDefined(): void
    {
        $this->apiClient
            ->method('get')->with($this->anything())
            ->willThrowException(new DatabaseError('foo index not defined bar'))
        ;

        $this->expectException(UnsupportedQuery::class);

        $this->query->getSnapshot();
    }

    /**
     * @test
     */
    public function withNonExistingDatabase(): void
    {
        $this->apiClient
            ->method('get')->with($this->anything())
            ->willThrowException(DatabaseNotFound::fromUri(new Uri('https://database-name.firebaseio.com')))
        ;

        $this->expectException(DatabaseNotFound::class);

        $this->query->getSnapshot();
    }
}
