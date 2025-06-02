<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Place;

class PlaceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa se é possível criar um lugar com dados válidos.
     */
    public function test_can_create_place()
    {
        $payload = [
            'name'  => 'Place A',
            'city'  => 'City A',
            'state' => 'ST',
        ];

        $response = $this->postJson('/api/places', $payload);
        $response->assertStatus(201)
                 ->assertJsonFragment([
                     'name' => 'Place A',
                     'city' => 'City A',
                     'state' => 'ST'
                 ]);

        // Verifica se o banco contém o registro
        $this->assertDatabaseHas('places', [
            'name' => 'Place A',
            'city' => 'City A',
            'state' => 'ST'
        ]);
    }

    /**
     * Testa se é possível listar lugares existentes.
     */
    public function test_can_list_places()
    {
        // Cria dois lugares no banco via factory
        Place::factory()->create(['name' => 'Alpha']);
        Place::factory()->create(['name' => 'Beta']);

        $response = $this->getJson('/api/places');
        $response->assertStatus(200)
                 ->assertJsonCount(2)
                 ->assertJsonFragment(['name' => 'Alpha'])
                 ->assertJsonFragment(['name' => 'Beta']);
    }

    /**
     * Testa o filtro por nome (query string).
     */
    public function test_can_filter_by_name()
    {
        Place::factory()->create(['name' => 'Parque Azul']);
        Place::factory()->create(['name' => 'Outro Lugar']);

        $response = $this->getJson('/api/places?name=Parque');
        $response->assertStatus(200)
                 ->assertJsonCount(1)
                 ->assertJsonFragment(['name' => 'Parque Azul']);
    }

    /**
     * Testa obter um lugar específico pelo ID.
     */
    public function test_can_show_place()
    {
        $place = Place::factory()->create(['name' => 'Place X']);
        $response = $this->getJson("/api/places/{$place->id}");
        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Place X']);
    }

    /**
     * Testa atualização de um lugar.
     */
    public function test_can_update_place()
    {
        $place = Place::factory()->create(['name' => 'Old Name', 'city' => 'Old City', 'state' => 'OC']);
        $payload = [
            'name'  => 'New Name',
            'city'  => 'New City',
            'state' => 'NC',
        ];

        $response = $this->putJson("/api/places/{$place->id}", $payload);
        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'name' => 'New Name',
                     'city' => 'New City',
                     'state' => 'NC'
                 ]);

        $this->assertDatabaseHas('places', [
            'id'    => $place->id,
            'name'  => 'New Name',
            'city'  => 'New City',
            'state' => 'NC'
        ]);
    }

    /**
     * Testa remoção de um lugar.
     */
    public function test_can_delete_place()
    {
        $place = Place::factory()->create();
        $response = $this->deleteJson("/api/places/{$place->id}");
        $response->assertStatus(204);

        $this->assertDatabaseMissing('places', [
            'id' => $place->id
        ]);
    }

    /**
     * Testa validação de campos obrigatórios ao criar.
     */
    public function test_validation_errors_on_create()
    {
        $response = $this->postJson('/api/places', []);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'city', 'state']);
    }

    /**
     * Testa validação de campos obrigatórios ao atualizar.
     */
    public function test_validation_errors_on_update()
    {
        $place = Place::factory()->create();
        $response = $this->putJson("/api/places/{$place->id}", ['name' => '']);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'city', 'state']);
    }
}
