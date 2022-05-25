<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;
use App\Models\Wallet;
use Tests\TestCase;


class AuthTest extends TestCase
{
    use WithFaker;
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_example()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function testCanRegisterUserSuccesfully()
    { 
        $payload = [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => $this->faker->numerify('##########'),
            "phone" =>  $this->faker->phoneNumber,
        ];
        
       $this->json('POST', '/api/register', $payload)
        ->assertJson([
            "status_code" => 201,
            "message" => "Success"
        ]);
    }

    public function testCanLoginUserSuccesfully()
    { 
        $payload = [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => $this->faker->numerify('##########'),
            "phone" =>  $this->faker->phoneNumber,
        ];
        
        $this->json('POST', '/api/register', $payload);
        
        $this->json('POST', '/api/login', $payload)
            ->assertJsonStructure([
                "data" => [ 
                    "access_token" 
                ]
            ])
            ->assertJson([
                "status_code" => 200,
                "message" => "Success",
                "data" => [
                    "token_type" => "bearer",
                ]
            ]);
    }

    public function testReturnsAFormattedErrorResponseForWrongPassword()
    { 
        $payload = [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => $this->faker->numerify('##########'),
            "phone" =>  $this->faker->phoneNumber
        ];
        
        $this->json('POST', '/api/register', $payload);

        $data = [
            'email' => $payload['email'],
            'password' => $this->faker->word
        ];

        $this->json('POST', '/api/login', $data)
        ->assertStatus(401)
        ->assertJson([
            "message" => "Error"
        ]);  
    }

    public function testReturnsAFormattedErrorResponseForWrongEmail()
    { 
        $payload = [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => $this->faker->numerify('##########'),
            "phone" =>  $this->faker->phoneNumber
        ];
        
        $this->json('POST', '/api/register', $payload);

        $data = [
            'email' =>  $this->faker->unique()->email(),
            'password' => $payload['password']
        ];

        $this->json('POST', '/api/login', $data)
        ->assertStatus(400)
        ->assertJson([
            "message" => "Error"
        ]);  
    }

    public function testWalletIsCreatedForTheUserOnRegistration()
    { 
        $payload = [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => $this->faker->numerify('##########'),
            "phone" =>  $this->faker->phoneNumber
        ];
        
        $response = $this->json('POST', '/api/register', $payload);
        $response->assertJson([
            "status_code" => 201,
            "message" => "Success",
            "data" => [
                "wallets" => [
                    [ "user_id" => $response['data']["id"]  ]
                ]
            ]
        ]);
    }
}
