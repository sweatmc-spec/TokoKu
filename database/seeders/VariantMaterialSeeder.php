<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsVariantOptions;
use Illuminate\Database\Seeder;

class VariantMaterialSeeder extends Seeder
{
    use SeedsVariantOptions;

    public function run(): void
    {
        $this->seedOptions('material', [
            'Katun', 'Polyester', 'Denim', 'Linen', 'Rayon', 'Fleece', 'Jersey', 'Wol',
        ]);
    }
}
