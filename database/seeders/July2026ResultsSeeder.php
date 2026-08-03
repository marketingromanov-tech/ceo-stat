<?php

namespace Database\Seeders;

use App\Models\Robot;
use App\Models\TradingAccount;
use App\Models\TradingResult;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class July2026ResultsSeeder extends Seeder
{
    /**
     * Import the ordered receipts from "поступления за Июль.xlsx".
     *
     * Running the seeder again replaces only the manual July 2026 results for
     * this account, so the import is deterministic and cannot create duplicates.
     */
    public function run(): void
    {
        $resultsByDay = [
            20 => ['-15.36', '11.06', '80.97'],
            21 => ['-2.09', '35.39', '-0.97', '34.70', '12.50', '11.85', '12.93', '11.98', '8.81', '10.87', '10.16'],
            22 => ['-8.33', '6.92', '65.48', '10.33', '7.30', '-24.67', '-16.36', '22.18', '142.83', '2.92', '22.98', '9.23'],
            23 => ['1.86', '21.47', '9.25', '2.14', '20.95', '8.24', '8.16', '8.48'],
            24 => ['-5.84', '29.02', '-7.14', '30.76', '8.45', '8.50', '8.10', '8.72', '8.11', '7.19', '6.66', '8.64', '8.06', '-1.64'],
            25 => ['1.12', '21.85'],
            27 => ['-59.40', '-44.41', '-44.87', '24.75', '316.17', '-0.83', '23.77', '8.79', '9.75'],
            28 => ['-6.18', '-69.31', '-26.10', '-29.52', '-20.78', '33.25', '258.85', '8.06', '8.67', '8.02', '-6.29', '8.60', '45.44', '8.19', '9.22', '7.97'],
            29 => ['-69.70', '-106.29', '-157.94', '-131.89', '648.35', '8.22', '8.08', '-6.56', '8.71', '46.55', '-35.57', '-36.15', '17.93', '150.57', '-7.72', '31.31', '-5.81', '29.86', '8.48'],
            30 => ['9.48', '-2.83', '25.48', '9.41', '8.15', '9.92', '-2.57', '25.74'],
            31 => ['2.93', '20.42', '8.13', '8.32', '-9.80', '33.60', '8.54', '1.33', '21.04', '8.98', '7.93', '7.96'],
        ];

        DB::transaction(function () use ($resultsByDay): void {
            $robot = Robot::query()->updateOrCreate(
                ['name' => '№1 - 20000$'],
                ['description' => 'Ручной учёт показателей', 'tracking_started_at' => '2026-07-20', 'is_active' => true],
            );

            $account = TradingAccount::query()->updateOrCreate(
                ['robot_id' => $robot->id],
                [
                    'name' => 'Счёт №1',
                    'platform' => 'manual',
                    'currency' => 'USD',
                    'initial_deposit' => '20000.00',
                    'current_balance' => '21759.12',
                    'is_active' => true,
                ],
            );

            $account->results()
                ->where('source', 'manual')
                ->whereBetween('traded_at', ['2026-07-01', '2026-07-31'])
                ->delete();

            $authorId = User::query()->where('email', 'admin@ceostat.local')->value('id');

            foreach (range(20, 31) as $day) {
                $amounts = $resultsByDay[$day] ?? ['0.00'];

                foreach ($amounts as $index => $amount) {
                    TradingResult::query()->create([
                        'trading_account_id' => $account->id,
                        'created_by' => $authorId,
                        'traded_at' => sprintf('2026-07-%02d', $day),
                        'sequence' => $index + 1,
                        'amount' => $amount,
                        'source' => 'manual',
                        'comment' => 'Импорт из файла «поступления за Июль.xlsx»',
                        'metadata' => ['import' => 'july-2026'],
                    ]);
                }
            }
        });
    }
}
