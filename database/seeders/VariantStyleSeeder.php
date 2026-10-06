<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsVariantOptions;
use Illuminate\Database\Seeder;

class VariantStyleSeeder extends Seeder
{
    use SeedsVariantOptions;

    public function run(): void
    {
        $this->seedOptions('style', [
            'Lengan Pendek', 'Lengan Panjang', 'Oversize', 'Slim Fit', 'Regular Fit',
        ]);
    }
}
