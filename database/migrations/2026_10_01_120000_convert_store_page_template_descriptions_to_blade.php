<?php

use App\Utils\Navigationutility;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * page_type was lost when these templates were copied from Oydera, so the
     * store / default template lookup by page type could not find them.
     */
    private array $pageTypes = [
        'bb9d0c8e-ead8-f660-0d4f-94f18cb0dec5' => 50, // Brand (default brand devices template)
        'a28d2727-050b-61e2-fde6-ad46d1bb93ae' => 50, // Brand Devices
        'e5348b7d-3157-3cca-35cb-632b260a5925' => 40, // All Brands
    ];

    /**
     * Legacy spintax template columns converted to Blade (see Navigationutility::convertLegacyTemplate()).
     */
    private array $templateColumns = [
        'description_template',
        'title_template',
        'meta_title_template',
        'meta_description_template',
        'meta_keywords_template',
    ];

    public function up(): void
    {
        foreach ($this->pageTypes as $id => $pageType) {
            DB::table('store_page_template')->where('id', $id)->whereNull('page_type')->update(['page_type' => $pageType]);
        }

        DB::table('store_page_template')->get(array_merge(['id'], $this->templateColumns))
            ->each(function ($template) {
                $changes = [];
                foreach ($this->templateColumns as $column) {
                    $converted = Navigationutility::convertLegacyTemplate($template->$column);
                    if ($converted !== $template->$column) {
                        $changes[$column] = $converted;
                    }
                }

                if ($changes) {
                    DB::table('store_page_template')->where('id', $template->id)->update($changes + ['updated_at' => now()]);
                }
            });
    }

    public function down(): void
    {
        // The template conversion is not reversed; the legacy text is still in Oydera's web_store_page_template.
    }
};
