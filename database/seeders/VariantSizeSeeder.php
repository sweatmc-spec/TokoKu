<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsVariantOptions;
use Illuminate\Database\Seeder;

class VariantSizeSeeder extends Seeder
{
    use SeedsVariantOptions;

    public function run(): void
    {
        $this->seedOptions('size', ['S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'All Size']);
    }
}
