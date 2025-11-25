<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get branches (assuming BranchSeeder has already run)
        $branches = Branch::all();

        if ($branches->isEmpty()) {
            $this->command->warn('No branches found. Please run BranchSeeder first.');
            return;
        }

        $mainBranch = $branches->first();
        $secondBranch = $branches->count() > 1 ? $branches->get(1) : $mainBranch;

        // Create a supervisor user for Main Branch
        User::query()->create([
            'name' => 'Supervisor Admin',
            'email' => 'supervisor@example.com',
            'password' => bcrypt('password'),
            'role' => 'supervisor',
            'branch_id' => $mainBranch->id,
        ]);

        // Create a supervisor user for second branch (if exists)
        if ($secondBranch->id !== $mainBranch->id) {
            User::query()->create([
                'name' => 'Supervisor Downtown',
                'email' => 'supervisor.downtown@example.com',
                'password' => bcrypt('password'),
                'role' => 'supervisor',
                'branch_id' => $secondBranch->id,
            ]);
        }

        // Create rider users matching the rider phones (Pakistan mobile numbers)
        $riderPhones = [
            '+923001234501', '+923002234502', '+923003234503', '+923004234504', '+923005234505',
            '+923006234506', '+923007234507', '+923008234508', '+923009234509', '+923010234510',
        ];

        foreach ($riderPhones as $index => $phone) {
            // Alternate riders between branches
            $branchId = ($index % 2 === 0) ? $mainBranch->id : $secondBranch->id;

            User::query()->create([
                'name' => 'Rider ' . ($index + 1),
                'email' => $phone,
                'password' => bcrypt('password'),
                'role' => 'rider',
                'branch_id' => $branchId,
            ]);
        }

        $this->command->info('Users created with branch assignments!');
    }
}
