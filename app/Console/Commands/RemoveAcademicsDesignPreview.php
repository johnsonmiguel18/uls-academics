<?php

namespace App\Console\Commands;

use App\Http\Controllers\ThumbnailController;
use App\Models\AcademicEvent;
use App\Models\College;
use App\Models\Department;
use App\Models\Document;
use App\Models\FacultyMember;
use App\Models\Program;
use Database\Seeders\CollegeDesignPreviewSeeder as Preview;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

/** Undoes CollegeDesignPreviewSeeder: clears sample text and deletes sample records. Real content stays. */
class RemoveAcademicsDesignPreview extends Command
{
    protected $signature = 'academics:remove-design-preview';

    protected $description = 'Remove the [SAMPLE] content added to college, department and program pages for the design preview';

    public function handle(): int
    {
        $fields = College::all()->sum(fn (College $college) => $this->clear($college, Preview::collegeSamples()))
            + Program::all()->sum(fn (Program $program) => $this->clear($program, Preview::programSamples()));

        // Only records still marked as samples (renamed ones were taken over and stay). One by one, so each deletion is logged.
        $records = collect()
            ->concat(Department::where('slug', 'like', Preview::SLUG_PREFIX . '%')->where('name', 'like', Preview::MARK . '%')->get())
            ->concat(AcademicEvent::where('title', 'like', Preview::MARK . '%')->get())
            ->concat(Document::where('title', 'like', Preview::MARK . '%')->where('file', Preview::FILE)->get())
            ->concat(FacultyMember::where('name', 'like', 'Sample Faculty Name%')->where('email', 'like', '%@' . Preview::EMAIL_DOMAIN)->get());
        $records->each->delete();

        File::deleteDirectory(storage_path('app/public/' . dirname(Preview::FILE)));
        foreach (ThumbnailController::WIDTHS as $width) {
            File::deleteDirectory(public_path("thumbs/$width/storage/" . dirname(Preview::FILE)));
        }

        $this->info("Cleared $fields sample fields and removed {$records->count()} sample records.");

        return self::SUCCESS;
    }

    /** Clears fields still holding sample text; in lists, drops only the sample items. Returns the fields changed. */
    private function clear(Model $model, array $samples): int
    {
        foreach ($samples as $field => $sample) {
            $value = $model->{$field};

            if (is_array($value)) {
                $kept = array_values(array_filter($value, fn ($item) => ! str_starts_with(
                    (string) ($item['name'] ?? $item['title'] ?? $item['area'] ?? $item['question'] ?? ''), Preview::MARK)));
                $model->{$field} = $kept ?: null;
            } elseif ($value === $sample || str_contains((string) $value, trim(Preview::MARK))) {
                $model->{$field} = null;
            }
        }

        $changed = count($model->getDirty());
        $changed && $model->save();

        return $changed;
    }
}
