<?php

use App\Models\Token;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('can create user with hashed password', function () {
    $response = $this->postJson('/api/users', [
        'name' => 'Juan Perez',
        'email' => 'juan@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Usuario creado exitosamente',
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'juan@example.com',
        'name' => 'Juan Perez',
    ]);

    $user = User::where('email', 'juan@example.com')->first();
    expect(Hash::check('password123', $user->password))->toBeTrue();
});

test('can get first 10 users', function () {
    User::factory()->count(15)->create();

    $response = $this->getJson('/api/users');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => '10 primeros usuarios obtenidos correctamente',
        ]);

    expect(count($response->json('data')))->toBe(10);
});

test('can login and receive session token', function () {
    $user = User::create([
        'name' => 'Maria Lopez',
        'email' => 'maria@example.com',
        'password' => Hash::make('secret123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'maria@example.com',
        'password' => 'secret123',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
        ]);

    $token = $response->json('data.token');
    expect($token)->not->toBeEmpty();

    $this->assertDatabaseHas('tokens', [
        'user_id' => $user->id,
        'token' => $token,
    ]);
});

test('fails login with invalid password', function () {
    User::create([
        'name' => 'Maria Lopez',
        'email' => 'maria@example.com',
        'password' => Hash::make('secret123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'maria@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => 'Credenciales incorrectas',
            'data' => null,
        ]);
});

test('can update user name passing valid token and new name', function () {
    $user = User::create([
        'name' => 'Carlos Old',
        'email' => 'carlos@example.com',
        'password' => Hash::make('password123'),
    ]);

    $token = 'sampletoken123456';
    Token::create([
        'user_id' => $user->id,
        'token' => $token,
    ]);

    $response = $this->putJson('/api/users/name', [
        'token' => $token,
        'name' => 'Carlos New Name',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Nombre actualizado correctamente',
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Carlos New Name',
    ]);
});

test('fails update user name with invalid token', function () {
    $response = $this->putJson('/api/users/name', [
        'token' => 'invalidtoken',
        'name' => 'New Name',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => 'Token no válido',
            'data' => null,
        ]);
});
