<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\GeolocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_geolocation_calculate_distance()
    {
        $service = new GeolocationService();
        $distance = $service->calculateDistance(14.6937, -17.4441, 14.7167, -17.4677);
        
        $this->assertIsFloat($distance);
        $this->assertGreaterThan(0, $distance);
    }
}
