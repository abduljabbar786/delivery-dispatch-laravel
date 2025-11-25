<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Rider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $riders = Rider::all();
        $branches = Branch::all();

        if ($branches->isEmpty()) {
            $this->command->warn('No branches found. Please run BranchSeeder first.');
            return;
        }

        $mainBranch = $branches->first();
        $secondBranch = $branches->count() > 1 ? $branches->get(1) : $mainBranch;

        $orders = [
            // UNASSIGNED Orders
            [
                'code' => 'ORD-001',
                'customer_name' => 'Alice Johnson',
                'customer_phone' => '+923211234501',
                'address' => 'House 123, Main Boulevard Gulberg III, Lahore',
                'lat' => 31.5089,
                'lng' => 74.3512,
                'status' => 'UNASSIGNED',
                'notes' => 'Ring doorbell twice',
            ],
            [
                'code' => 'ORD-002',
                'customer_name' => 'Bob Smith',
                'customer_phone' => '+923211234502',
                'address' => 'Block A, Model Town, Lahore',
                'lat' => 31.4825,
                'lng' => 74.3155,
                'status' => 'UNASSIGNED',
            ],
            [
                'code' => 'ORD-003',
                'customer_name' => 'Carol White',
                'customer_phone' => '+923211234503',
                'address' => 'Phase 6, DHA, Lahore',
                'lat' => 31.4705,
                'lng' => 74.4025,
                'status' => 'UNASSIGNED',
            ],
            [
                'code' => 'ORD-004',
                'customer_name' => 'David Lee',
                'customer_phone' => '+923211234504',
                'address' => 'Block H, Johar Town, Lahore',
                'lat' => 31.4680,
                'lng' => 74.2695,
                'status' => 'UNASSIGNED',
            ],
            [
                'code' => 'ORD-005',
                'customer_name' => 'Emma Davis',
                'customer_phone' => '+923211234505',
                'address' => 'MM Alam Road, Gulberg, Lahore',
                'lat' => 31.5095,
                'lng' => 74.3485,
                'status' => 'UNASSIGNED',
            ],
            // ASSIGNED Orders
            [
                'code' => 'ORD-006',
                'customer_name' => 'Frank Miller',
                'customer_phone' => '+923211234506',
                'address' => 'Mall Road, Cantt, Lahore',
                'lat' => 31.5530,
                'lng' => 74.3380,
                'status' => 'ASSIGNED',
                'assigned_rider_id' => $riders[0]->id ?? null,
            ],
            [
                'code' => 'ORD-007',
                'customer_name' => 'Grace Taylor',
                'customer_phone' => '+923211234507',
                'address' => 'Sector C2, Township, Lahore',
                'lat' => 31.4340,
                'lng' => 74.3420,
                'status' => 'ASSIGNED',
                'assigned_rider_id' => $riders[1]->id ?? null,
            ],
            [
                'code' => 'ORD-008',
                'customer_name' => 'Henry Brown',
                'customer_phone' => '+923211234508',
                'address' => 'Block B, Allama Iqbal Town, Lahore',
                'lat' => 31.5110,
                'lng' => 74.3040,
                'status' => 'ASSIGNED',
                'assigned_rider_id' => $riders[2]->id ?? null,
            ],
            [
                'code' => 'ORD-009',
                'customer_name' => 'Iris Wilson',
                'customer_phone' => '+923211234509',
                'address' => 'Liberty Market, Gulberg, Lahore',
                'lat' => 31.5185,
                'lng' => 74.3505,
                'status' => 'ASSIGNED',
                'assigned_rider_id' => $riders[3]->id ?? null,
            ],
            [
                'code' => 'ORD-010',
                'customer_name' => 'Jack Anderson',
                'customer_phone' => '+923211234510',
                'address' => 'Block D, Faisal Town, Lahore',
                'lat' => 31.4425,
                'lng' => 74.2875,
                'status' => 'ASSIGNED',
                'assigned_rider_id' => $riders[4]->id ?? null,
            ],
            // PICKED_UP Orders
            [
                'code' => 'ORD-011',
                'customer_name' => 'Kate Martinez',
                'customer_phone' => '+923211234511',
                'address' => 'Wahdat Road, Lahore',
                'lat' => 31.5290,
                'lng' => 74.3435,
                'status' => 'PICKED_UP',
                'assigned_rider_id' => $riders[5]->id ?? null,
            ],
            [
                'code' => 'ORD-012',
                'customer_name' => 'Leo Garcia',
                'customer_phone' => '+923211234512',
                'address' => 'Phase 2, DHA, Lahore',
                'lat' => 31.4750,
                'lng' => 74.4050,
                'status' => 'PICKED_UP',
                'assigned_rider_id' => $riders[6]->id ?? null,
            ],
            [
                'code' => 'ORD-013',
                'customer_name' => 'Mia Rodriguez',
                'customer_phone' => '+923211234513',
                'address' => 'Garden Town, Lahore',
                'lat' => 31.4965,
                'lng' => 74.3252,
                'status' => 'PICKED_UP',
                'assigned_rider_id' => $riders[7]->id ?? null,
            ],
            // OUT_FOR_DELIVERY Orders
            [
                'code' => 'ORD-014',
                'customer_name' => 'Noah Lopez',
                'customer_phone' => '+923211234514',
                'address' => 'Jail Road, Lahore',
                'lat' => 31.5145,
                'lng' => 74.3262,
                'status' => 'OUT_FOR_DELIVERY',
                'assigned_rider_id' => $riders[8]->id ?? null,
            ],
            [
                'code' => 'ORD-015',
                'customer_name' => 'Olivia Gonzalez',
                'customer_phone' => '+923211234515',
                'address' => 'Fortress Stadium, Cantt, Lahore',
                'lat' => 31.5580,
                'lng' => 74.3395,
                'status' => 'OUT_FOR_DELIVERY',
                'assigned_rider_id' => $riders[9]->id ?? null,
            ],
            [
                'code' => 'ORD-016',
                'customer_name' => 'Paul Hernandez',
                'customer_phone' => '+923211234516',
                'address' => 'Thokar Niaz Baig, Lahore',
                'lat' => 31.4295,
                'lng' => 74.3835,
                'status' => 'OUT_FOR_DELIVERY',
                'assigned_rider_id' => $riders[0]->id ?? null,
            ],
            // DELIVERED Orders
            [
                'code' => 'ORD-017',
                'customer_name' => 'Quinn Perez',
                'customer_phone' => '+923211234517',
                'address' => 'Barkat Market, Garden Town, Lahore',
                'lat' => 31.4935,
                'lng' => 74.3210,
                'status' => 'DELIVERED',
                'assigned_rider_id' => $riders[1]->id ?? null,
            ],
            [
                'code' => 'ORD-018',
                'customer_name' => 'Rachel Turner',
                'customer_phone' => '+923211234518',
                'address' => 'Valencia Town, Lahore',
                'lat' => 31.4135,
                'lng' => 74.2655,
                'status' => 'DELIVERED',
                'assigned_rider_id' => $riders[2]->id ?? null,
            ],
            // FAILED Orders
            [
                'code' => 'ORD-019',
                'customer_name' => 'Sam Phillips',
                'customer_phone' => '+923211234519',
                'address' => 'Bahria Town, Lahore',
                'lat' => 31.3420,
                'lng' => 74.2155,
                'status' => 'FAILED',
                'assigned_rider_id' => $riders[3]->id ?? null,
                'notes' => 'Customer not available',
            ],
            [
                'code' => 'ORD-020',
                'customer_name' => 'Tina Campbell',
                'customer_phone' => '+923211234520',
                'address' => 'Wapda Town, Lahore',
                'lat' => 31.4205,
                'lng' => 74.2845,
                'status' => 'FAILED',
                'assigned_rider_id' => $riders[4]->id ?? null,
                'notes' => 'Wrong address',
            ],
        ];

        foreach ($orders as $index => $orderData) {
            if (isset($orderData['lat']) && isset($orderData['lng'])) {
                $orderData['dest_pos'] = DB::raw("ST_GeomFromText('POINT({$orderData['lng']} {$orderData['lat']})', 4326)");
            }

            // Assign branch_id based on the assigned rider's branch, or alternate for unassigned orders
            if (isset($orderData['assigned_rider_id']) && $orderData['assigned_rider_id']) {
                $rider = $riders->firstWhere('id', $orderData['assigned_rider_id']);
                $orderData['branch_id'] = $rider ? $rider->branch_id : $mainBranch->id;
            } else {
                // Alternate unassigned orders between branches
                $orderData['branch_id'] = ($index % 2 === 0) ? $mainBranch->id : $secondBranch->id;
            }

            Order::create($orderData);
        }

        $this->command->info('Orders created with branch assignments!');

        // Update rider statuses based on assigned orders
        foreach ($riders as $rider) {
            $hasActiveOrder = Order::where('assigned_rider_id', $rider->id)
                ->whereIn('status', ['ASSIGNED', 'PICKED_UP', 'OUT_FOR_DELIVERY'])
                ->exists();

            if ($hasActiveOrder) {
                $rider->update(['status' => 'BUSY']);
            }
        }
    }
}
