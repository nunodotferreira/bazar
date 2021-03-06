<?php

namespace Bazar\Console\Commands;

use Bazar\Models\Cart;
use Bazar\Models\Item;
use Bazar\Models\Shipping;
use Illuminate\Console\Command;

class ClearCartsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bazar:clear-carts {--all : Clear all the carts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the expired carts';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        if ($this->option('all')) {
            Cart::query()->truncate();
            Item::query()->where('itemable_type', Cart::class)->delete();
            Shipping::query()->where('shippable_type', Cart::class)->delete();

            $this->info('All carts have been deleted.');
        } else {
            Item::query()
                ->where('itemable_type', Cart::class)
                ->whereIn('itemable_id', Cart::expired()->select('id'))
                ->delete();

            Shipping::query()
                    ->where('shippable_type', Cart::class)
                    ->whereIn('shippable_id', Cart::expired()->select('id'))
                    ->delete();

            Cart::query()->expired()->delete();

            $this->info('Expired carts have been deleted.');
        }

        return Command::SUCCESS;
    }
}
