<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function show(Course $course): Response
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
        ]);
    }
}
