# My Locations API

A simple Laravel 12 API to manage “Places” (CRUD).  
This project uses Docker (PHP-FPM, Nginx, PostgreSQL) to provide a reproducible development environment.

---

## Table of Contents

1. [Features](#features)
2. [Prerequisites](#prerequisites)
3. [Getting Started](#getting-started)
    - [Clone the Repository](#clone-the-repository)
    - [Environment Configuration](#environment-configuration)
    - [Build & Run with Docker](#build--run-with-docker)
    - [Install Dependencies & Generate Key](#install-dependencies--generate-key)
    - [Run Database Migrations](#run-database-migrations)
4. [Docker Services](#docker-services)
5. [API Endpoints](#api-endpoints)
    - [List All Places](#list-all-places)
    - [Filter Places by Name](#filter-places-by-name)
    - [Get a Single Place](#get-a-single-place)
    - [Create a Place](#create-a-place)
    - [Update a Place](#update-a-place)
    - [Delete a Place](#delete-a-place)
6. [Database Schema](#database-schema)
7. [Testing](#testing)
8. [Project Structure](#project-structure)
9. [Code Style & Conventions](#code-style--conventions)
10. [Troubleshooting](#troubleshooting)
11. [License](#license)

---

## Features

-   **Laravel 12** (PHP 8.2+)
-   **CRUD** (`Create`, `Read`, `Update`, `Delete`) for “Places”
-   **PostgreSQL 15** (in Docker container)
-   **Docker Compose** for `app` (PHP-FPM), `db` (PostgreSQL), `webserver` (Nginx)
-   **JSON responses** for all API operations
-   **Filtering** by name (`?name=…`)
-   **Automatic slug generation** based on `name`
-   **Cache driver** set to `file` (no DB cache dependencies)
-   **Endpoints fully documented** in this README
-   Optional **PHPUnit tests** (if you create tests/factories)

---

## Prerequisites

-   [Docker](https://docs.docker.com/get-docker/) (Engine & Compose) installed on your machine.
-   (Optional) [Git](https://git-scm.com/) to clone the repository.
-   (Optional) [Postman](https://www.postman.com/) or cURL for testing endpoints.

---

## Getting Started

### Clone the Repository

```bash
git clone https://github.com/your-username/my-locations-api.git
cd my-locations-api
```

### Environment Configuration

1. Copy the example environment file:

    ```bash
    cp .env.example .env
    ```

2. Open `.env` and confirm the database settings match the Docker setup:

    ```dotenv
    DB_CONNECTION=pgsql
    DB_HOST=db
    DB_PORT=5432
    DB_DATABASE=places_db
    DB_USERNAME=postgres
    DB_PASSWORD=secret

    CACHE_STORE=file
    ```

    - `DB_HOST=db` refers to the Docker service name for PostgreSQL.
    - `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` must match the `db` service’s environment (see `docker-compose.yml`).
    - `CACHE_STORE=file` ensures Laravel uses file caching instead of database caching.

### Build & Run with Docker

1. **Stop and remove any existing containers (including volumes)**:

    ```bash
    docker-compose down -v
    ```

    This removes existing containers and deletes the named volume (`pgdata`) for a clean state.

2. **Build images and start containers in detached mode**:

    ```bash
    docker-compose up -d --build
    ```

3. Confirm the three containers are running:
    ```bash
    docker ps
    ```
    You should see:
    ```
    CONTAINER ID   IMAGE                        ...   PORTS                    NAMES
    xxxxxx         postgres:15                  ...   0.0.0.0:5432->5432/tcp   mylocations_db
    yyyyyy         my-locations-api-app         ...   9000/tcp                 mylocations_app
    zzzzzz         nginx:alpine                 ...   0.0.0.0:8082->80/tcp     mylocations_webserver
    ```

### Install Dependencies & Generate Key

1. **Install Composer dependencies**:

    ```bash
    docker-compose exec app composer install
    ```

2. **Generate Laravel application key**:
    ```bash
    docker-compose exec app php artisan key:generate
    ```

### Run Database Migrations

1. **Run migrations** to create the tables in `places_db`:
    ```bash
    docker-compose exec app php artisan migrate
    ```
2. Confirm the `places` table exists:
    - Via `pgAdmin` (connect to `localhost:5432`, database `places_db`) OR
    - Via CLI inside the `db` container:
        ```bash
        docker-compose exec db psql -U postgres -d places_db
        # In psql:
        \dt
        # Should list: cache, cache_locks, failed_jobs, job_batches, jobs, migrations, password_reset_tokens, places
        ```

---

## Docker Services

The project relies on three Docker services defined in `docker-compose.yml`:

```yaml
version: "3.8"
services:
    app:
        build:
            context: .
            dockerfile: Dockerfile
        container_name: mylocations_app
        restart: unless-stopped
        working_dir: /var/www/html
        volumes:
            - ./:/var/www/html
        environment:
            - DB_CONNECTION=pgsql
            - DB_HOST=db
            - DB_PORT=5432
            - DB_DATABASE=places_db
            - DB_USERNAME=postgres
            - DB_PASSWORD=secret
            - CACHE_STORE=file
        depends_on:
            - db

    db:
        image: postgres:15
        container_name: mylocations_db
        restart: unless-stopped
        environment:
            - POSTGRES_DB=places_db
            - POSTGRES_USER=postgres
            - POSTGRES_PASSWORD=secret
        volumes:
            - pgdata:/var/lib/postgresql/data
        ports:
            - "5432:5432"

    webserver:
        image: nginx:alpine
        container_name: mylocations_webserver
        restart: unless-stopped
        ports:
            - "8082:80"
        volumes:
            - ./:/var/www/html
            - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
        depends_on:
            - app

volumes:
    pgdata:
```

-   **app**

    -   Builds from `Dockerfile` (PHP 8.2-FPM with required extensions).
    -   Connects to `db` via environment variables.
    -   Serves application code at `/var/www/html`.

-   **db**

    -   Uses `postgres:15`.
    -   Creates `places_db` with user `postgres` and password `secret`.
    -   Exposes port `5432` on the host for direct DB access (e.g. pgAdmin, psql).

-   **webserver**
    -   Uses `nginx:alpine`.
    -   Serves from `/var/www/html/public`.
    -   Forwards port `8082` on the host to port `80` in the container.
    -   Passes PHP requests to `mylocations_app:9000` (PHP-FPM).

---

## API Endpoints

All endpoints are prefixed with `/api` and return JSON. Base URL (with Docker setup):

```
http://localhost:8082/
```

### List All Places

-   **Endpoint:**
    ```
    GET /api/places
    ```
-   **Query Parameters:**
    -   `name` (optional): Filter by partial, case-insensitive match on `name`.
        -   Example: `/api/places?name=Parque`
-   **Response (200 OK):**
    ```json
    [
      {
        "id": 1,
        "name": "Parque Central",
        "slug": "parque-central",
        "city": "Curitiba",
        "state": "PR",
        "created_at": "2025-06-01T20:00:00.000000Z",
        "updated_at": "2025-06-01T20:00:00.000000Z"
      },
      ...
    ]
    ```
-   **Example cURL:**
    ```bash
    curl -X GET "http://localhost:8082/api/places"
    curl -X GET "http://localhost:8082/api/places?name=Parque"
    ```

---

### Get a Single Place

-   **Endpoint:**
    ```
    GET /api/places/{id}
    ```
-   **Path Parameters:**
    -   `id` (integer, required): The ID of the `Place`.
-   **Response:**
    -   **200 OK** with JSON of the place:
        ```json
        {
            "id": 1,
            "name": "Parque Central",
            "slug": "parque-central",
            "city": "Curitiba",
            "state": "PR",
            "created_at": "2025-06-01T20:00:00.000000Z",
            "updated_at": "2025-06-01T20:00:00.000000Z"
        }
        ```
    -   **404 Not Found** if no place with that ID exists.
-   **Example cURL:**
    ```bash
    curl -X GET "http://localhost:8082/api/places/1"
    ```

---

### Create a Place

-   **Endpoint:**
    ```
    POST /api/places
    ```
-   **Headers:**
    ```
    Content-Type: application/json
    ```
-   **Request Body (JSON):**

    ```json
    {
        "name": "Parque Central",
        "city": "Curitiba",
        "state": "PR"
    }
    ```

    -   `name` (string, required, max: 255)
    -   `city` (string, required, max: 255)
    -   `state` (string, required, max: 255)

-   **Response:**
    -   **201 Created** with JSON of the newly created record:
        ```json
        {
            "id": 1,
            "name": "Parque Central",
            "slug": "parque-central",
            "city": "Curitiba",
            "state": "PR",
            "created_at": "2025-06-01T20:00:00.000000Z",
            "updated_at": "2025-06-01T20:00:00.000000Z"
        }
        ```
    -   **422 Unprocessable Entity** if validation fails.  
        Example error response:
        ```json
        {
            "message": "The name field is required.",
            "errors": {
                "name": ["The name field is required."]
            }
        }
        ```
-   **Example cURL:**
    ```bash
    curl -X POST http://localhost:8082/api/places     -H "Content-Type: application/json"     -d '{"name":"Parque Central","city":"Curitiba","state":"PR"}'
    ```

---

### Update a Place

-   **Endpoint:**
    ```
    PUT /api/places/{id}
    ```
-   **Path Parameter:**
    -   `id` (integer, required): The ID of the place to update.
-   **Headers:**
    ```
    Content-Type: application/json
    ```
-   **Request Body (JSON):**
    ```json
    {
        "name": "Parque Atualizado",
        "city": "Curitiba",
        "state": "PR"
    }
    ```
    -   Same validation rules as **Create**.
-   **Response:**
    -   **200 OK** with JSON of the updated record:
        ```json
        {
            "id": 1,
            "name": "Parque Atualizado",
            "slug": "parque-atualizado",
            "city": "Curitiba",
            "state": "PR",
            "created_at": "2025-06-01T20:00:00.000000Z",
            "updated_at": "2025-06-01T20:05:00.000000Z"
        }
        ```
    -   **404 Not Found** if no place with that ID exists.
    -   **422 Unprocessable Entity** if validation fails.
-   **Example cURL:**
    ```bash
    curl -X PUT http://localhost:8082/api/places/1     -H "Content-Type: application/json"     -d '{"name":"Parque Atualizado","city":"Curitiba","state":"PR"}'
    ```

---

### Delete a Place

-   **Endpoint:**
    ```
    DELETE /api/places/{id}
    ```
-   **Path Parameter:**
    -   `id` (integer, required): The ID of the place to delete.
-   **Response:**
    -   **204 No Content** if deletion succeeds.
    -   **404 Not Found** if no place with that ID exists.
-   **Example cURL:**
    ```bash
    curl -X DELETE http://localhost:8082/api/places/1
    ```

---

## Database Schema

The `places` table is created by the migration `2025_06_01_224142_create_places_table.php`:

```php
Schema::create('places', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->string('city');
    $table->string('state');
    $table->timestamps();
});
```

-   `id`: big serial, primary key
-   `name`: string, not null
-   `slug`: string, not null, unique (generated from `name` using `Str::slug()`)
-   `city`: string, not null
-   `state`: string, not null
-   `created_at`: timestamp, null allowed (automatically handled by Laravel)
-   `updated_at`: timestamp, null allowed

Other tables created by default Laravel migrations:

-   `users`
-   `password_reset_tokens`
-   `failed_jobs`
-   `jobs`
-   `cache` (if using DB cache, but we set `CACHE_STORE=file`)
-   `cache_locks`
-   `migrations`
-   `job_batches`

---

## Testing

### 1. Environment for Tests

If you have defined a separate testing database, ensure `.env.testing` is present:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=places_test_db
DB_USERNAME=postgres
DB_PASSWORD=secret
```

-   You may create a second database `places_test_db` inside the same `db` container (or run migrations with `--database=testing`).

### 2. Example PHPUnit Tests

Inside `tests/Feature/PlaceTest.php` (example):

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Place;

class PlaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_place()
    {
        $payload = [
            'name'  => 'Place A',
            'city'  => 'City A',
            'state' => 'ST',
        ];

        $this->postJson('/api/places', $payload)
             ->assertStatus(201)
             ->assertJsonFragment(['name' => 'Place A']);
    }

    public function test_can_list_places()
    {
        Place::factory()->create(['name' => 'Alpha']);
        Place::factory()->create(['name' => 'Beta']);

        $this->getJson('/api/places')
             ->assertStatus(200)
             ->assertJsonCount(2);
    }

    public function test_can_filter_by_name()
    {
        Place::factory()->create(['name' => 'Parque Azul']);
        Place::factory()->create(['name' => 'Outro Lugar']);

        $this->getJson('/api/places?name=Parque')
             ->assertStatus(200)
             ->assertJsonCount(1);
    }

    public function test_can_show_place()
    {
        $place = Place::factory()->create(['name' => 'Place X']);
        $this->getJson("/api/places/{$place->id}")
             ->assertStatus(200)
             ->assertJsonFragment(['name' => 'Place X']);
    }

    public function test_can_update_place()
    {
        $place = Place::factory()->create(['name' => 'Old Name']);
        $payload = ['name' => 'New Name', 'city' => 'City', 'state' => 'ST'];

        $this->putJson("/api/places/{$place->id}", $payload)
             ->assertStatus(200)
             ->assertJsonFragment(['name' => 'New Name']);
    }

    public function test_can_delete_place()
    {
        $place = Place::factory()->create();
        $this->deleteJson("/api/places/{$place->id}")
             ->assertStatus(204);
    }
}
```

### 3. Run Tests

Once you have tests defined and a testing database, run:

```bash
docker-compose exec app ./vendor/bin/phpunit
```

You should see all tests pass (green output).

---

## Project Structure

```
my-locations-api/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/
│   │           └── PlaceController.php
│   ├── Models/
│   │   └── Place.php
│   └── Providers/
│       ├── AppServiceProvider.php
│       └── RouteServiceProvider.php
├── config/
│   └── app.php
├── database/
│   ├── factories/
│   │   └── PlaceFactory.php    # (optional, for tests)
│   ├── migrations/
│   │   └── 2025_06_01_224142_create_places_table.php
│   └── seeders/                # (optional, if you seed data)
├── docker/
│   └── nginx/
│       └── default.conf
├── public/
│   └── index.php
├── resources/
│   └── views/                  # (unlikely used for API, but present)
├── routes/
│   ├── api.php
│   └── web.php
├── tests/
│   └── Feature/
│       └── PlaceTest.php       # (if tests exist)
├── .env.example
├── .gitignore
├── composer.json
├── composer.lock
├── Dockerfile
├── docker-compose.yml
└── README.md
```

---

## Code Style & Conventions

-   **English-only identifiers**:
    -   Classes, methods, variables in English (`Place`, `PlaceController`, `$fillable`, `index()`, `store()`, etc.).
-   **Validation**:
    -   Use `$request->validate([...])` to enforce `string|max:255` on `name`, `city`, `state`.
-   **Slug generation**:
    -   Use `Str::slug($data['name'])` in `store()` and `update()`.
-   **Error handling**:
    -   `findOrFail($id)` returns 404 if not found.
    -   Validation errors return 422 with JSON error structure.
-   **Routes**:
    -   Use `Route::apiResource('places', PlaceController::class)` in `routes/api.php`.
    -   Ensure `RouteServiceProvider` loads `routes/api.php` under prefix `/api`.
-   **Responses**:
    -   Always return JSON. E.g. `return response()->json($place, 201)` for created resource.
    -   Use appropriate HTTP status codes: 200 (OK), 201 (Created), 204 (No Content), 404 (Not Found), 422 (Validation Error).

---

## Troubleshooting

-   **404 on `/api/places`**

    1. Ensure `RouteServiceProvider` is registered in `config/app.php` under `'providers'`.
    2. Run `php artisan route:list` (inside `app` container) to confirm `/api/places` appears.
    3. Clear caches:
        ```bash
        docker-compose run --rm app php artisan optimize:clear
        docker-compose run --rm app php artisan route:clear
        ```
    4. Confirm `routes/api.php` starts with `<?php` (no BOM or blank spaces above).

-   **Database connection errors**

    1. Confirm in `.env`:
        ```env
        DB_HOST=db
        DB_DATABASE=places_db
        DB_USERNAME=postgres
        DB_PASSWORD=secret
        ```
    2. Confirm `docker-compose.yml` has service:
        ```yaml
        db:
            image: postgres:15
            environment:
                - POSTGRES_DB=places_db
                - POSTGRES_USER=postgres
                - POSTGRES_PASSWORD=secret
            ports:
                - "5432:5432"
        ```
    3. Run `docker-compose down -v && docker-compose up -d` to recreate the database.
    4. Inside the `db` container, run:
        ```bash
        docker-compose exec db psql -U postgres -d places_db
        \dt
        ```
        to verify that the `places` table exists.

-   **Permission denied or file not found in Nginx**
    1. Check `docker/nginx/default.conf`:
        ```nginx
        root /var/www/html/public;
        fastcgi_pass mylocations_app:9000;
        ```
    2. Ensure the volume `- ./:/var/www/html` is mounted and `/var/www/html/public/index.php` exists.
    3. Check container logs:
        ```bash
        docker-compose logs -f webserver
        docker-compose logs -f app
        ```

---

## License

This project is open-source and available under the [MIT License](LICENSE).

Feel free to clone, experiment, and adapt as needed. Good luck!
