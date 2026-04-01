<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class DownloadMediaCommand extends Command
{
    protected $signature = 'app:download-media';
    protected $description = 'Download test images and videos from external URLs to local storage';

    public function handle()
    {
        $questions = DB::table('test_questions')->get();
        $this->info("Processing " . $questions->count() . " questions...");

        if (!Storage::disk('public')->exists('tests/images changed')) {
            Storage::disk('public')->makeDirectory('tests/images changed');
        }
        if (!Storage::disk('public')->exists('tests/videos changed')) {
            Storage::disk('public')->makeDirectory('tests/videos changed');
        }

        $bar = $this->output->createProgressBar($questions->count());
        $bar->start();

        foreach ($questions as $q) {
            // Handle Question Image
            if ($q->question_file && str_starts_with($q->question_file, 'http')) {
                $ext = pathinfo($q->question_file, PATHINFO_EXTENSION) ?: 'jpg';
                $filename = "tests/images changed/tests/{$q->new_question_id}.{$ext}";
                
                try {
                    $response = Http::get($q->question_file);
                    if ($response->successful()) {
                        Storage::disk('public')->put($filename, $response->body());
                        DB::table('test_questions')->where('id', $q->id)->update([
                            'question_file' => Storage::url($filename)
                        ]);
                    }
                } catch (\Exception $e) {
                    $this->error("\nFailed to download image for Q#{$q->new_question_id}: " . $e->getMessage());
                }
            }

            // Handle Answer Details (Video)
            $answer = DB::table('answers')->where('test_question_id', $q->id)->first();
            if ($answer && $answer->answer_resource && str_starts_with($answer->answer_resource, 'http')) {
                $ext = pathinfo($answer->answer_resource, PATHINFO_EXTENSION) ?: 'mp4';
                $filename = "tests/videos changed/{$q->new_question_id}.{$ext}";

                try {
                    $response = Http::get($answer->answer_resource);
                    if ($response->successful()) {
                        Storage::disk('public')->put($filename, $response->body());
                        DB::table('answers')->where('id', $answer->id)->update([
                            'answer_resource' => Storage::url($filename)
                        ]);
                    }
                } catch (\Exception $e) {
                    $this->error("\nFailed to download video for Q#{$q->new_question_id}: " . $e->getMessage());
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->info("\nAll media processed.");
    }
}
