<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IssueWarehouseTokenCommand extends Command
{
    protected $signature = 'erp:warehouse-token {name=gudang}';

    protected $description = 'Buat token API gudang. Token plaintext hanya dicetak sekali.';

    public function handle(): int
    {
        $plain = Str::random(48);
        DB::table('warehouse_api_tokens')->insert([
            'name' => $this->argument('name'),
            'token_hash' => hash('sha256', $plain),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->line($plain);

        return self::SUCCESS;
    }
}
