<?php

namespace App\Console\Commands;

use App\Models\ProductImageAlternative;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-time fix for product_image_alternatives.updated_at.
 *
 * ProductAlternativeImagesSyncJob used to save every alternate on every run (it re-downloaded the
 * file into a new sys_file each time), so updated_at says when the job last ran, not when the
 * image changed. SubmitShopifyProductsJob re-pushes a main image whose selected alternate is newer
 * than the listing image, so those dates would re-push images that did not change.
 *
 * This sets updated_at to the Fonus date_updated of the alternate (created_at when Fonus has no
 * date). The sync job keeps it right from then on: it only saves an alternate when Fonus changed it.
 *
 * Dry run by default; pass --apply to write.
 */
class ResetAlternateImageDatesCommand extends Command
{
    protected $signature = 'alternates:reset-dates {--apply : Write the changes. Without it nothing is updated}';

    protected $description = 'Set product_image_alternatives.updated_at to the date Fonus last changed each alternate.';

    public function handle()
    {
        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'Applying' : 'Dry run');

        $fonusDates = DB::connection('fonus_db')->table('tbl_product_main_images')
            ->get(['product_id', 'image_id', 'date_updated'])
            ->mapWithKeys(fn ($row) => [$row->product_id . '_' . $row->image_id => $row->date_updated]);
        $productCodes = DB::table('products')->pluck('product_code', 'product_id');

        $stats = ['alternates' => 0, 'fonus_date' => 0, 'created_at' => 0, 'already_right' => 0, 'updated' => 0];

        ProductImageAlternative::select('id', 'product_id', 'version_number', 'created_at', 'updated_at')
            ->chunkById(1000, function ($alternates) use ($fonusDates, $productCodes, $apply, &$stats) {
                foreach ($alternates as $alternate) {
                    $stats['alternates']++;
                    $key = ($productCodes[$alternate->product_id] ?? '') . '_' . $alternate->version_number;
                    $fonusDate = $fonusDates[$key] ?? null;

                    if (!empty($fonusDate) && $fonusDate !== '0000-00-00 00:00:00') {
                        $date = $fonusDate;
                        $stats['fonus_date']++;
                    } else {
                        $date = $alternate->getRawOriginal('created_at');
                        $stats['created_at']++;
                    }

                    if (empty($date) || $alternate->getRawOriginal('updated_at') == $date) {
                        $stats['already_right']++;
                        continue;
                    }

                    $stats['updated']++;
                    if ($apply) {
                        // Query builder, so Eloquent does not set updated_at to now.
                        DB::table('product_image_alternatives')->where('id', $alternate->id)->update(['updated_at' => $date]);
                    }
                }
            });

        $this->table(['', 'count'], collect($stats)->map(fn ($count, $key) => [$key, $count])->values()->all());

        if (!$apply) {
            $this->line('"updated" rows were not written. Run again with --apply to write them.');
        }

        return self::SUCCESS;
    }
}
