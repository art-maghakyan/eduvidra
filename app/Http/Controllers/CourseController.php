<?php

namespace App\Http\Controllers;

use App\Enums\RbacRole;
use App\Models\Conversation;
use App\Models\Course;
use App\Models\Message;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function show(Request $request, Course $course): Response
    {
        $course->load(['videos' => fn ($query) => $query->orderBy('id')]);

        return Inertia::render('Courses/Show', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'description' => $course->description,
                'tags' => $course->tags,
                'videos' => $course->videos->map(fn ($video) => [
                    'id' => $video->id,
                    'title' => $video->title,
                ]),
            ],
            // The page itself is public; the tutor panel only appears for a
            // verified student, and only ever carries that student's own thread.
            'chat' => fn () => $this->chatProps($request, $course),
        ]);
    }

    /**
     * @return array{available: bool, messages: list<array{id: int, role: string, content: string}>}
     */
    private function chatProps(Request $request, Course $course): array
    {
        $user = $request->user();

        $available = $user !== null
            && $user->hasVerifiedEmail()
            && $user->hasRole(RbacRole::STUDENT->value);

        if (! $available) {
            return ['available' => false, 'messages' => []];
        }

        $conversation = Conversation::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereNull('video_id')
            ->first();

        return [
            'available' => true,
            'messages' => $conversation
                ? $conversation->messages()
                    ->orderBy('id')
                    ->get(['id', 'role', 'content'])
                    ->map(fn (Message $message) => [
                        'id' => $message->id,
                        'role' => $message->role,
                        'content' => $message->content,
                    ])
                    ->all()
                : [],
        ];
    }
}
