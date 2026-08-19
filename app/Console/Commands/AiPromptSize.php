<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Services\Ai\ChatClient;
use App\Services\Ai\TutorPrompt;
use Illuminate\Console\Command;

/**
 * Prints the assembled system prompt and what it costs in input tokens.
 *
 * Anthropic's count_tokens endpoint is free, so running this against
 * ANTHROPIC_DRIVER=real measures the prompt exactly without paying for a
 * completion. Under the fake driver the count is a chars/4 estimate and is
 * labelled as such.
 */
class AiPromptSize extends Command
{
    protected $signature = 'dev:ai-prompt-size
        {course? : Course slug (defaults to the course with the most lessons)}
        {--show : Print the full prompt text}';

    protected $description = 'Measure the tutor system prompt for a course using the free count_tokens endpoint';

    public function handle(ChatClient $chat): int
    {
        $slug = $this->argument('course');

        $course = $slug !== null
            ? Course::where('slug', $slug)->first()
            : Course::withCount('videos')->orderByDesc('videos_count')->first();

        if ($course === null) {
            $this->error($slug !== null
                ? "No course with slug \"{$slug}\"."
                : 'No courses exist. Run php artisan dev:seed-search first.');

            return self::FAILURE;
        }

        $system = TutorPrompt::forCourse($course);

        if ($this->option('show')) {
            $this->line($system);
            $this->newLine();
        }

        // One representative student turn, so the number reflects a real request
        // rather than the system prompt alone.
        $messages = [['role' => 'user', 'content' => 'How do while loops differ from for loops?']];

        $driver = config('anthropic.driver');
        $tokens = $chat->countTokens($system, $messages);

        $this->table(['', ''], [
            ['Course', $course->title],
            ['Lessons', $course->videos()->count()],
            ['Prompt characters', number_format(mb_strlen($system))],
            ['Input tokens', number_format($tokens).($driver === 'real' ? '' : ' (estimated — driver is '.$driver.')')],
            ['Cost per turn at Haiku input rate', '$'.number_format($tokens / 1_000_000, 6)],
        ]);

        if ($driver !== 'real') {
            $this->comment('Re-run with -e ANTHROPIC_DRIVER=real for the exact count. count_tokens is free.');
        }

        return self::SUCCESS;
    }
}
