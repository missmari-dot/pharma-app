<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\NotificationPersonnaliseeService;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationPersonnaliseeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_conseil_sante_notification()
    {
        $user = User::factory()->create();
        $service = new NotificationPersonnaliseeService();
        
        $service->notifierConseilSantePersonnalise($user->id, 'Test conseil');
        
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'conseil_sante',
            'message' => 'Test conseil'
        ]);
    }
}