<?php

namespace Database\Factories;

use App\Models\Place;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PlaceFactory extends Factory
{
    /**
     * O model correspondente a esta factory.
     *
     * @var string
     */
    protected $model = Place::class;

    /**
     * Define o estado padrão do modelo.
     *
     * @return array
     */
    public function definition(): array
    {
        $name = $this->faker->company();
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
        ];
    }
}
