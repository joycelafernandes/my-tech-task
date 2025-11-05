<?php
namespace Tests\Unit;

use App\Contracts\LookupProviderInterface;
use App\Services\LookupService;
use Tests\TestCase;

class LookupServiceTest extends TestCase
{
    private LookupService $service;
    private LookupProviderInterface $mockProvider;

    private const MINECRAFT_PROVIDER = 'minecraft';

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockProvider = $this->createMock(LookupProviderInterface::class);

        $this->service = new LookupService([
            self::MINECRAFT_PROVIDER => $this->mockProvider,
        ]);
    }

    public function testInvalidLookupType()
    {
        $result = $this->service->lookup('invalid_type', ['username' => 'testuser']);

        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertEquals('Unsupported lookup type', $result['error']);
    }
    public function testLookupByUsernameSuccess()
    {
        $expectedResult = [
            'id'       => '1234567890abcdef',
            'username' => 'testuser',
            'avatar'   => 'https://crafatar.com/avatars/1234567890abcdef',
        ];

        $this->mockProvider->expects($this->once())
            ->method('findByUsername')
            ->with('testuser')
            ->willReturn($expectedResult);

        $result = $this->service->lookup(self::MINECRAFT_PROVIDER, ['username' => 'testuser']);

        $this->assertEquals($expectedResult, $result);
    }

    public function testLookupByIdSuccess()
    {
        $expectedResult = [
            'username' => 'testuser',
            'id'       => '123',
            'avatar'   => 'avatar.jpg',
        ];

        $this->mockProvider->expects($this->once())
            ->method('findByUserId')
            ->with('123')
            ->willReturn($expectedResult);

        $result = $this->service->lookup(self::MINECRAFT_PROVIDER, ['id' => '123']);

        $this->assertEquals($expectedResult, $result);
    }

    public function testMissingLookupParameters()
    {
        $result = $this->service->lookup(self::MINECRAFT_PROVIDER, []);

        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertEquals('Username or ID required', $result['error']);
    }
}
