<?php
namespace Tests\Unit;

use App\Http\Controllers\LookupController;
use App\Services\LookupService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Tests\TestCase;

class LookupControllerTest extends TestCase
{
    private LookupController $controller;
    private LookupService $mockService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockService = $this->createMock(LookupService::class);
        $this->controller  = new LookupController($this->mockService);
    }

    public function testMissingTypeReturns422(): void
    {
        $request = Request::create('/lookup', 'GET', [
            'username' => 'testuser',
        ]);

        $response = $this->controller->lookup($request);

        $this->assertEquals(422, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Type is required', $data['error']);
    }

    public function testMissingParametersReturns422(): void
    {
        $request = Request::create('/lookup', 'GET', [
            'type' => 'minecraft',
        ]);

        $response = $this->controller->lookup($request);

        $this->assertEquals(422, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Username or ID required', $data['error']);
    }

    public function testServiceThrowsInvalidArgumentExceptionIsHandled(): void
    {
        $this->mockService->expects($this->once())
            ->method('lookup')
            ->with('minecraft', ['username' => 'x', 'id' => false])
            ->willThrowException(new InvalidArgumentException('invalid type'));

        $request = Request::create('/lookup', 'GET', [
            'type'     => 'minecraft',
            'username' => 'x',
        ]);

        $response = $this->controller->lookup($request);

        $this->assertEquals(422, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('invalid type', $data['error']);
    }

    public function testLookupSuccessReturns200AndPayload(): void
    {
        $this->mockService->expects($this->once())
            ->method('lookup')
            ->with('minecraft', ['username' => 'testuser', 'id' => false])
            ->willReturn([
                'success'  => true,
                'username' => 'testuser',
                'id'       => '123',
                'avatar'   => 'avatar.jpg',
            ]);

        $request = Request::create('/lookup', 'GET', [
            'type'     => 'minecraft',
            'username' => 'testuser',
        ]);

        $response = $this->controller->lookup($request);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('testuser', $data['username']);
        $this->assertEquals('123', $data['id']);
        $this->assertEquals('avatar.jpg', $data['avatar']);
    }

    public function testServiceReturnsErrorArrayResultsIn422(): void
    {
        $this->mockService->expects($this->once())
            ->method('lookup')
            ->with('minecraft', ['username' => 'testuser', 'id' => false])
            ->willReturn([
                'success' => false,
                'error'   => 'Unsupported lookup type',
            ]);

        $request = Request::create('/lookup', 'GET', [
            'type'     => 'minecraft',
            'username' => 'testuser',
        ]);

        $response = $this->controller->lookup($request);

        $this->assertEquals(422, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Unsupported lookup type', $data['error']);
        $this->assertArrayHasKey('timestamp', $data);
    }

}
