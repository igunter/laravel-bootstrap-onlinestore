<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::create([
            'role'              => 'admin',
            'name'              => 'Admin Account',
            'email'             => 'admin@autoparel.co.uk',
            'email_verified_at' => now(),
            'password'          => bcrypt('P4$$w0rd'),
        ]);

        User::factory(10)->create();

        $this->call(CategorySeeder::class);
        $this->call(BrandSeeder::class);
        $this->call(ProductSeeder::class);
        $this->call(ProductImageSeeder::class);
    }
}
