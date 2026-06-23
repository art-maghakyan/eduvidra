<x-mail::message>
# Welcome to {{ config('app.name') }}, {{ $user->name }}!

We're thrilled to have you on board. Your student account is ready and waiting — everything you need to start learning is just one click away.

---

## What's included in your account

<x-mail::panel>
**Video Courses** — Stream lessons at your own pace, pause and rewatch as many times as you need.

**Progress Tracking** — Your progress is saved automatically so you always pick up exactly where you left off.

**Discussion Boards** — Ask questions, share insights, and connect with fellow learners.
</x-mail::panel>

## Ready to start?

Your dashboard is your home base — find your enrolled courses, track your progress, and discover what's next.

<x-mail::button :url="route('dashboard')" color="primary">
Go to My Dashboard
</x-mail::button>

---

If you didn't create this account, no action is needed — you can safely ignore this email.

Thanks for joining us,
**The {{ config('app.name') }} Team**
</x-mail::message>
