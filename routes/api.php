<?php

use App\Models\AppNotification;
use App\Models\CampusEvent;
use App\Models\Confession;
use App\Models\Conversation;
use App\Models\Flashcard;
use App\Models\LiveClass;
use App\Models\Message;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostLike;
use App\Models\Reel;
use App\Models\Story;
use App\Models\StudyMaterial;
use App\Models\TutorMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

function publicUser(User $user, ?User $viewer = null): array
{
    $viewerFollowingIds = $viewer ? $viewer->following()->pluck('users.id')->all() : [];
    $userFollowingIds = $user->following()->pluck('users.id')->all();
    $userFriendIds = $user->friends()->pluck('users.id')->all();
    $referralsCount = $user->referredUsers()->count();

    return [
        'id' => $user->id,
        'name' => $user->name,
        'matricNo' => $user->matric_no,
        'email' => $user->email,
        'faculty' => $user->faculty,
        'department' => $user->department,
        'course' => $user->course,
        'bio' => $user->bio ?? '',
        'verified' => (bool) $user->verified,
        'profilePhoto' => $user->profile_photo,
        'verificationRequestStatus' => $user->verification_request_status ?? 'none',
        'followers' => $user->followers()->count(),
        'followingCount' => count($userFollowingIds),
        'friendsCount' => count($userFriendIds),
        'followingIds' => $viewer && $viewer->is($user) ? $userFollowingIds : [],
        'friendIds' => $viewer && $viewer->is($user) ? $userFriendIds : [],
        'mutuals' => $viewer ? count(array_intersect($viewerFollowingIds, $userFollowingIds)) : 0,
        'postsCount' => $user->posts()->where('status', 'approved')->count(),
        'referralsCount' => $referralsCount,
        'referralCode' => $viewer && $viewer->is($user) ? $user->referral_code : null,
        'createdAt' => optional($user->created_at)->toIso8601String(),
        'role' => $user->role,
    ];
}

function publicPost(Post $post): array
{
    $post->loadMissing('user', 'postLikes', 'postComments.user');
    $viewer = authUser(request());

    return [
        'id' => $post->public_id,
        'author' => $post->user->name,
        'authorId' => $post->user->id,
        'faculty' => $post->user->faculty,
        'content' => $post->content,
        'tags' => $post->tags ?? [],
        'likes' => $post->postLikes->count(),
        'comments' => $post->postComments->count(),
        'shares' => $post->shares,
        'status' => $post->status,
        'likedByViewer' => $viewer ? $post->postLikes->contains('user_id', $viewer->id) : false,
        'createdAt' => optional($post->created_at)->toIso8601String(),
        'verifiedAuthor' => (bool) $post->user->verified,
        'profilePhoto' => $post->user->profile_photo,
    ];
}

function publicPostComment(PostComment $comment): array
{
    $comment->loadMissing('user');

    return [
        'id' => $comment->id,
        'author' => $comment->user->name,
        'authorId' => $comment->user_id,
        'content' => $comment->content,
        'createdAt' => optional($comment->created_at)->toIso8601String(),
    ];
}

function publicStory(Story $story): array
{
    return [
        'id' => $story->public_id,
        'title' => $story->title,
        'context' => $story->content,
        'author' => $story->user->name,
        'authorId' => $story->user->id,
        'profilePhoto' => $story->user->profile_photo,
        'verifiedAuthor' => (bool) $story->user->verified,
        'status' => $story->status,
        'expiresAt' => optional($story->expires_at)->toIso8601String(),
        'createdAt' => optional($story->created_at)->toIso8601String(),
    ];
}

function publicReel(Reel $reel): array
{
    return [
        'id' => $reel->public_id,
        'title' => $reel->title,
        'caption' => $reel->caption ?? '',
        'creator' => $reel->user->name,
        'creatorId' => $reel->user->id,
        'views' => number_format($reel->views),
        'videoUrl' => $reel->video_url,
        'verifiedAuthor' => (bool) $reel->user->verified,
        'status' => $reel->status,
        'createdAt' => optional($reel->created_at)->toIso8601String(),
    ];
}

function publicEvent(CampusEvent $event): array
{
    return [
        'id' => $event->public_id,
        'name' => $event->title,
        'description' => $event->description,
        'venue' => $event->venue,
        'time' => optional($event->starts_at)->format('D, M j · g:i A'),
        'host' => $event->user->name,
        'hostId' => $event->user->id,
        'verifiedAuthor' => (bool) $event->user->verified,
        'status' => $event->status,
        'startsAt' => optional($event->starts_at)->toIso8601String(),
        'endsAt' => optional($event->ends_at)->toIso8601String(),
        'createdAt' => optional($event->created_at)->toIso8601String(),
    ];
}

function publicConfession(Confession $confession): array
{
    return [
        'id' => $confession->public_id,
        'content' => $confession->content,
        'status' => $confession->status,
        'createdAt' => optional($confession->created_at)->toIso8601String(),
    ];
}

function publicStudyMaterial(StudyMaterial $material): array
{
    return [
        'id' => $material->public_id,
        'courseCode' => $material->course_code,
        'title' => $material->title,
        'type' => $material->type,
        'description' => $material->description ?? '',
        'author' => $material->user->name,
        'status' => $material->status,
        'createdAt' => optional($material->created_at)->toIso8601String(),
    ];
}

function publicFlashcard(Flashcard $flashcard): array
{
    return [
        'id' => $flashcard->public_id,
        'courseCode' => $flashcard->course_code,
        'front' => $flashcard->front,
        'back' => $flashcard->back,
        'status' => $flashcard->status,
        'createdAt' => optional($flashcard->created_at)->toIso8601String(),
    ];
}

function publicLiveClass(LiveClass $liveClass): array
{
    return [
        'id' => $liveClass->public_id,
        'courseCode' => $liveClass->course_code,
        'title' => $liveClass->title,
        'description' => $liveClass->description ?? '',
        'access' => ucfirst($liveClass->access_type),
        'price' => (float) $liveClass->price,
        'time' => optional($liveClass->starts_at)->format('D, M j · g:i A'),
        'startsAt' => optional($liveClass->starts_at)->toIso8601String(),
        'author' => $liveClass->user->name,
        'status' => $liveClass->status,
    ];
}

function publicTutorMessage(TutorMessage $message): array
{
    return [
        'id' => $message->id,
        'role' => $message->role,
        'text' => $message->content,
        'createdAt' => optional($message->created_at)->toIso8601String(),
    ];
}

function publicNotification(AppNotification $notification): array
{
    return [
        'id' => $notification->id,
        'title' => $notification->title,
        'message' => $notification->message,
        'tone' => $notification->tone,
        'createdAt' => optional($notification->created_at)->toIso8601String(),
        'readAt' => optional($notification->read_at)->toIso8601String(),
    ];
}

function tutorReply(string $prompt): string
{
    $normalized = Str::lower(trim($prompt));

    if (str_contains($normalized, 'quiz')) {
        return 'I can turn that topic into a quiz. Start with core definitions, follow with application questions, then end with one worked example and one short recap.';
    }

    if (str_contains($normalized, 'flashcard')) {
        return 'A good flashcard set should keep one concept per card: term on the front, plain explanation on the back, plus one quick example you can recall during revision.';
    }

    if (str_contains($normalized, 'explain')) {
        return 'Let us break it down step by step: define the idea, identify the formula or principle behind it, connect it to a familiar school example, then summarize the key exam point.';
    }

    if (str_contains($normalized, 'assignment')) {
        return 'For assignment help, first restate the question in simple terms, list the given information, outline the method you should apply, and then write your final answer clearly with working.';
    }

    return 'I can help you study this topic. Tell me the course, what exactly is confusing, and whether you want an explanation, summary, flashcards, or quiz questions.';
}

function publicMessage(Message $message, User $viewer): array
{
    return [
        'id' => $message->id,
        'sender' => $message->user_id === $viewer->id ? 'You' : $message->user->name,
        'senderId' => $message->user_id,
        'text' => $message->body,
        'createdAt' => optional($message->created_at)->toIso8601String(),
        'own' => $message->user_id === $viewer->id,
    ];
}

function publicConversation(Conversation $conversation, User $viewer): array
{
    $conversation->loadMissing(['users', 'messages.user']);
    $otherUsers = $conversation->users->where('id', '!=', $viewer->id)->values();
    $latestMessage = $conversation->messages->sortByDesc('created_at')->first();

    return [
        'id' => $conversation->public_id,
        'type' => $conversation->type,
        'name' => $conversation->type === 'group'
            ? ($conversation->name ?: 'Study Group')
            : ($otherUsers->first()->name ?? 'Private chat'),
        'memberCount' => $conversation->users->count(),
        'members' => $conversation->users->map(fn ($user) => [
            'id' => $user->id,
            'name' => $user->name,
        ])->values()->all(),
        'lastMessage' => $latestMessage?->body ?? 'No messages yet.',
        'updatedAt' => optional($latestMessage?->created_at ?? $conversation->updated_at)->toIso8601String(),
    ];
}

function authUser(Request $request): ?User
{
    $token = $request->bearerToken();

    if (! $token) {
        return null;
    }

    $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

    return $accessToken?->tokenable;
}

function requireAuth(Request $request): User
{
    $user = authUser($request);
    abort_if(! $user, 401, 'You need to log in first.');

    return $user;
}

function requireStudent(Request $request): User
{
    $user = requireAuth($request);
    abort_if($user->role !== 'user', 403, 'Only student accounts can perform this action.');

    return $user;
}

function requireAdmin(Request $request): User
{
    $user = requireAuth($request);
    abort_if($user->role !== 'admin', 403, 'Admin access only.');

    return $user;
}

function approveContent(Model $item): void
{
    $item->update([
        'status' => 'approved',
        'approved_at' => now(),
    ]);
}

function rejectContent(Model $item): void
{
    $item->update([
        'status' => 'rejected',
        'approved_at' => null,
    ]);
}

function notifyUser(int $userId, string $title, string $message, string $tone = 'info'): void
{
    AppNotification::create([
        'user_id' => $userId,
        'title' => $title,
        'message' => $message,
        'tone' => $tone,
    ]);
}

Route::get('/health', fn () => response()->json([
    'ok' => true,
    'status' => 'healthy',
]));

Route::get('/bootstrap', function () {
    return response()->json([
        'ok' => true,
        'data' => array_merge(config('universe'), [
            'feedPosts' => Post::with('user')->where('status', 'approved')->latest()->get()->map(fn ($post) => publicPost($post))->all(),
            'stories' => Story::with('user')->where('status', 'approved')->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->latest()->get()->map(fn ($story) => publicStory($story))->all(),
            'reels' => Reel::with('user')->where('status', 'approved')->latest()->get()->map(fn ($reel) => publicReel($reel))->all(),
            'events' => CampusEvent::with('user')->where('status', 'approved')->latest('starts_at')->get()->map(fn ($event) => publicEvent($event))->all(),
            'confessions' => Confession::where('status', 'approved')->latest()->get()->map(fn ($confession) => publicConfession($confession))->all(),
            'studyMaterials' => StudyMaterial::with('user')->where('status', 'approved')->latest()->get()->map(fn ($material) => publicStudyMaterial($material))->all(),
            'flashcards' => Flashcard::where('status', 'approved')->latest()->get()->map(fn ($flashcard) => publicFlashcard($flashcard))->all(),
            'liveClasses' => LiveClass::with('user')->where('status', 'approved')->orderBy('starts_at')->get()->map(fn ($liveClass) => publicLiveClass($liveClass))->all(),
            'suggestedUsers' => User::where('role', 'user')->latest()->get()->map(fn ($user) => publicUser($user))->all(),
        ]),
    ]);
});

Route::post('/v1/auth/signup', function (Request $request) {
    $validated = $request->validate([
        'name' => ['required', 'string', 'min:3'],
        'matricNo' => ['required', 'string', 'min:6'],
        'referralCode' => ['nullable', 'string'],
        'faculty' => ['required', 'string'],
        'department' => ['required', 'string'],
        'course' => ['required', 'string'],
        'email' => ['required', 'email', 'unique:users,email'],
        'password' => ['required', 'string', 'min:8'],
    ]);

    $referrer = null;
    if (! empty($validated['referralCode'])) {
        $referrer = User::where('referral_code', $validated['referralCode'])->first();
        abort_if(! $referrer, 422, 'Referral code is invalid.');
    }

    $user = User::create([
        'name' => $validated['name'],
        'matric_no' => $validated['matricNo'],
        'referral_code' => 'UNI-' . Str::upper(Str::random(8)),
        'referred_by_id' => $referrer?->id,
        'faculty' => $validated['faculty'],
        'department' => $validated['department'],
        'course' => $validated['course'],
        'email' => strtolower($validated['email']),
        'password' => $validated['password'],
        'bio' => '',
        'profile_photo' => null,
        'verified' => false,
        'verification_request_status' => 'none',
        'role' => 'user',
    ]);

    $token = $user->createToken('universe-web')->plainTextToken;

    if ($referrer) {
        notifyUser($referrer->id, 'New referral signup', "{$user->name} just signed up with your referral code.", 'success');
    }

    return response()->json([
        'ok' => true,
        'message' => 'Signup successful.',
        'user' => publicUser($user, $user),
        'token' => $token,
    ], 201);
});

Route::post('/v1/auth/login', function (Request $request) {
    $validated = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string', 'min:8'],
    ]);

    $user = User::where('email', strtolower($validated['email']))->first();
    abort_if(! $user || ! Hash::check($validated['password'], $user->password), 422, 'Invalid email or password.');

    $token = $user->createToken('universe-web')->plainTextToken;

    return response()->json([
        'ok' => true,
        'message' => $user->role === 'admin' ? 'Admin login successful.' : 'Login successful.',
        'user' => publicUser($user, $user),
        'token' => $token,
    ]);
});

Route::get('/v1/auth/me', function (Request $request) {
    $user = requireAuth($request);

    return response()->json([
        'ok' => true,
        'user' => publicUser($user, $user),
    ]);
});

Route::get('/v1/referrals', function (Request $request) {
    $user = requireAuth($request);

    $referredUsers = $user->referredUsers()->latest()->get();
    $verifiedCount = $referredUsers->where('verified', true)->count();
    $pendingCount = $referredUsers->where('verified', false)->count();

    return response()->json([
        'ok' => true,
        'data' => [
            'summary' => [
                'referralCode' => $user->referral_code,
                'totalReferrals' => $referredUsers->count(),
                'verifiedReferrals' => $verifiedCount,
                'pendingReferrals' => $pendingCount,
                'coinsEarned' => ($verifiedCount * 300) + ($pendingCount * 150),
            ],
            'users' => $referredUsers->map(fn ($referredUser) => [
                'id' => $referredUser->id,
                'name' => $referredUser->name,
                'status' => $referredUser->verified ? 'Verified' : 'Pending verification',
                'reward' => $referredUser->verified ? '300 coins' : '150 coins pending',
                'createdAt' => optional($referredUser->created_at)->toIso8601String(),
            ])->all(),
        ],
    ]);
});

Route::post('/v1/auth/logout', function (Request $request) {
    $token = $request->bearerToken();
    $accessToken = $token ? \Laravel\Sanctum\PersonalAccessToken::findToken($token) : null;
    $accessToken?->delete();

    return response()->json([
        'ok' => true,
        'message' => 'Logged out successfully.',
    ]);
});

Route::put('/v1/profile', function (Request $request) {
    $user = requireStudent($request);

    $validated = $request->validate([
        'name' => ['required', 'string', 'min:3'],
        'department' => ['required', 'string'],
        'course' => ['required', 'string'],
        'bio' => ['nullable', 'string'],
    ]);

    $user->update([
        'name' => $validated['name'],
        'department' => $validated['department'],
        'course' => $validated['course'],
        'bio' => $validated['bio'] ?? '',
    ]);

    return response()->json([
        'ok' => true,
        'message' => 'Profile updated successfully.',
        'user' => publicUser($user->fresh(), $user->fresh()),
    ]);
});

Route::post('/v1/profile/photo', function (Request $request) {
    $user = requireStudent($request);

    $validated = $request->validate([
        'imageData' => ['required', 'string'],
    ]);

    $user->update([
        'profile_photo' => $validated['imageData'],
    ]);

    return response()->json([
        'ok' => true,
        'message' => 'Profile photo updated successfully.',
        'user' => publicUser($user->fresh(), $user->fresh()),
    ]);
});

Route::post('/v1/verification/request', function (Request $request) {
    $user = requireStudent($request);
    abort_if($user->verified, 422, 'This account is already verified.');

    $user->update([
        'verification_request_status' => 'requested',
        'verification_requested_at' => now(),
    ]);

    $admin = User::where('role', 'admin')->first();
    if ($admin) {
        notifyUser($admin->id, 'Verification request', "{$user->name} requested account verification.", 'warning');
    }

    return response()->json([
        'ok' => true,
        'message' => 'Verification request submitted for admin review.',
        'user' => publicUser($user->fresh(), $user->fresh()),
    ]);
});

Route::get('/v1/posts', function () {
    return response()->json([
        'ok' => true,
        'data' => Post::with('user')->where('status', 'approved')->latest()->get()->map(fn ($post) => publicPost($post))->all(),
    ]);
});

Route::get('/v1/posts/{publicId}', function (string $publicId) {
    $post = Post::with(['user', 'postLikes', 'postComments.user'])
        ->where('public_id', $publicId)
        ->where('status', 'approved')
        ->firstOrFail();

    return response()->json([
        'ok' => true,
        'data' => publicPost($post),
    ]);
});

Route::post('/v1/posts', function (Request $request) {
    $user = requireStudent($request);
    $validated = $request->validate([
        'content' => ['required', 'string', 'min:2'],
        'tags' => ['array'],
    ]);

    Post::create([
        'user_id' => $user->id,
        'public_id' => 'post-' . Str::lower(Str::random(8)),
        'content' => $validated['content'],
        'tags' => $validated['tags'] ?? [],
        'status' => 'pending',
    ]);

    $admin = User::where('role', 'admin')->first();
    if ($admin) {
        notifyUser($admin->id, 'Post submitted', "{$user->name} submitted a post for approval.", 'info');
    }

    return response()->json([
        'ok' => true,
        'message' => 'Post submitted for admin approval.',
    ], 201);
});

Route::post('/v1/posts/{publicId}/like', function (Request $request, string $publicId) {
    $user = requireStudent($request);
    $post = Post::with('user')->where('public_id', $publicId)->where('status', 'approved')->firstOrFail();
    $existingLike = PostLike::where('post_id', $post->id)->where('user_id', $user->id)->first();

    if ($existingLike) {
        $existingLike->delete();
        $liked = false;
        $message = 'Post unliked successfully.';
    } else {
        PostLike::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);
        $liked = true;
        $message = 'Post liked successfully.';

        if ($post->user_id !== $user->id) {
            notifyUser($post->user_id, 'New post like', "{$user->name} liked your post.", 'success');
        }
    }

    return response()->json([
        'ok' => true,
        'message' => $message,
        'data' => [
            'liked' => $liked,
            'post' => publicPost($post->fresh(['user', 'postLikes', 'postComments.user'])),
        ],
    ]);
});

Route::get('/v1/posts/{publicId}/comments', function (string $publicId) {
    $post = Post::where('public_id', $publicId)->where('status', 'approved')->firstOrFail();
    $comments = $post->postComments()->with('user')->get()->map(fn ($comment) => publicPostComment($comment))->all();

    return response()->json([
        'ok' => true,
        'data' => $comments,
    ]);
});

Route::post('/v1/posts/{publicId}/comments', function (Request $request, string $publicId) {
    $user = requireStudent($request);
    $validated = $request->validate([
        'content' => ['required', 'string', 'min:1', 'max:1500'],
    ]);

    $post = Post::with('user')->where('public_id', $publicId)->where('status', 'approved')->firstOrFail();
    $comment = PostComment::create([
        'post_id' => $post->id,
        'user_id' => $user->id,
        'content' => $validated['content'],
    ]);

    if ($post->user_id !== $user->id) {
        notifyUser($post->user_id, 'New post comment', "{$user->name} commented on your post.", 'info');
    }

    return response()->json([
        'ok' => true,
        'message' => 'Comment posted successfully.',
        'data' => publicPostComment($comment->fresh('user')),
        'post' => publicPost($post->fresh(['user', 'postLikes', 'postComments.user'])),
    ], 201);
});

Route::get('/v1/stories', function () {
    return response()->json([
        'ok' => true,
        'data' => Story::with('user')->where('status', 'approved')->where(function ($query) {
            $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
        })->latest()->get()->map(fn ($story) => publicStory($story))->all(),
    ]);
});

Route::post('/v1/stories', function (Request $request) {
    $user = requireStudent($request);
    $validated = $request->validate([
        'title' => ['required', 'string', 'min:2'],
        'content' => ['required', 'string', 'min:2'],
    ]);

    Story::create([
        'user_id' => $user->id,
        'public_id' => 'story-' . Str::lower(Str::random(8)),
        'title' => $validated['title'],
        'content' => $validated['content'],
        'status' => 'pending',
        'expires_at' => now()->addDay(),
    ]);

    $admin = User::where('role', 'admin')->first();
    if ($admin) {
        notifyUser($admin->id, 'Story submitted', "{$user->name} submitted a story for approval.", 'info');
    }

    return response()->json([
        'ok' => true,
        'message' => 'Story submitted for admin approval.',
    ], 201);
});

Route::get('/v1/reels', function () {
    return response()->json([
        'ok' => true,
        'data' => Reel::with('user')->where('status', 'approved')->latest()->get()->map(fn ($reel) => publicReel($reel))->all(),
    ]);
});

Route::post('/v1/reels', function (Request $request) {
    $user = requireStudent($request);
    $validated = $request->validate([
        'title' => ['required', 'string', 'min:2'],
        'caption' => ['nullable', 'string'],
        'videoUrl' => ['nullable', 'url'],
    ]);

    Reel::create([
        'user_id' => $user->id,
        'public_id' => 'reel-' . Str::lower(Str::random(8)),
        'title' => $validated['title'],
        'caption' => $validated['caption'] ?? '',
        'video_url' => $validated['videoUrl'] ?? null,
        'status' => 'pending',
    ]);

    $admin = User::where('role', 'admin')->first();
    if ($admin) {
        notifyUser($admin->id, 'Reel submitted', "{$user->name} submitted a reel for approval.", 'info');
    }

    return response()->json([
        'ok' => true,
        'message' => 'Reel submitted for admin approval.',
    ], 201);
});

Route::get('/v1/events', function () {
    return response()->json([
        'ok' => true,
        'data' => CampusEvent::with('user')->where('status', 'approved')->latest('starts_at')->get()->map(fn ($event) => publicEvent($event))->all(),
    ]);
});

Route::post('/v1/events', function (Request $request) {
    $user = requireStudent($request);
    $validated = $request->validate([
        'title' => ['required', 'string', 'min:2'],
        'description' => ['required', 'string', 'min:2'],
        'venue' => ['required', 'string', 'min:2'],
        'startsAt' => ['required', 'date'],
        'endsAt' => ['nullable', 'date', 'after_or_equal:startsAt'],
    ]);

    CampusEvent::create([
        'user_id' => $user->id,
        'public_id' => 'event-' . Str::lower(Str::random(8)),
        'title' => $validated['title'],
        'description' => $validated['description'],
        'venue' => $validated['venue'],
        'starts_at' => $validated['startsAt'],
        'ends_at' => $validated['endsAt'] ?? null,
        'status' => 'pending',
    ]);

    $admin = User::where('role', 'admin')->first();
    if ($admin) {
        notifyUser($admin->id, 'Event submitted', "{$user->name} submitted an event for approval.", 'info');
    }

    return response()->json([
        'ok' => true,
        'message' => 'Event submitted for admin approval.',
    ], 201);
});

Route::get('/v1/confessions', function () {
    return response()->json([
        'ok' => true,
        'data' => Confession::where('status', 'approved')->latest()->get()->map(fn ($confession) => publicConfession($confession))->all(),
    ]);
});

Route::post('/v1/confessions', function (Request $request) {
    $user = requireStudent($request);
    $validated = $request->validate([
        'content' => ['required', 'string', 'min:2'],
    ]);

    Confession::create([
        'user_id' => $user->id,
        'public_id' => 'confession-' . Str::lower(Str::random(8)),
        'content' => $validated['content'],
        'status' => 'pending',
    ]);

    $admin = User::where('role', 'admin')->first();
    if ($admin) {
        notifyUser($admin->id, 'Confession submitted', "A new anonymous confession is waiting for approval.", 'warning');
    }

    return response()->json([
        'ok' => true,
        'message' => 'Confession submitted for admin approval.',
    ], 201);
});

Route::get('/v1/study-hub', function () {
    return response()->json([
        'ok' => true,
        'data' => StudyMaterial::with('user')->where('status', 'approved')->latest()->get()->map(fn ($material) => publicStudyMaterial($material))->all(),
    ]);
});

Route::post('/v1/study-hub', function (Request $request) {
    $user = requireStudent($request);
    $validated = $request->validate([
        'courseCode' => ['required', 'string', 'min:2'],
        'title' => ['required', 'string', 'min:2'],
        'type' => ['required', 'string', 'min:2'],
        'description' => ['nullable', 'string'],
    ]);

    StudyMaterial::create([
        'user_id' => $user->id,
        'public_id' => 'material-' . Str::lower(Str::random(8)),
        'course_code' => Str::upper($validated['courseCode']),
        'title' => $validated['title'],
        'type' => $validated['type'],
        'description' => $validated['description'] ?? '',
        'status' => 'pending',
    ]);

    $admin = User::where('role', 'admin')->first();
    if ($admin) {
        notifyUser($admin->id, 'Study material submitted', "{$user->name} submitted study material for approval.", 'info');
    }

    return response()->json([
        'ok' => true,
        'message' => 'Study material submitted for admin approval.',
    ], 201);
});

Route::get('/v1/flashcards', function (Request $request) {
    $courseCode = Str::upper((string) $request->query('courseCode', ''));
    $query = Flashcard::where('status', 'approved');
    if ($courseCode !== '') {
        $query->where('course_code', $courseCode);
    }

    return response()->json([
        'ok' => true,
        'data' => $query->latest()->get()->map(fn ($flashcard) => publicFlashcard($flashcard))->all(),
    ]);
});

Route::post('/v1/flashcards', function (Request $request) {
    $user = requireStudent($request);
    $validated = $request->validate([
        'courseCode' => ['required', 'string', 'min:2'],
        'front' => ['required', 'string', 'min:2'],
        'back' => ['required', 'string', 'min:2'],
    ]);

    Flashcard::create([
        'user_id' => $user->id,
        'public_id' => 'card-' . Str::lower(Str::random(8)),
        'course_code' => Str::upper($validated['courseCode']),
        'front' => $validated['front'],
        'back' => $validated['back'],
        'status' => 'pending',
    ]);

    $admin = User::where('role', 'admin')->first();
    if ($admin) {
        notifyUser($admin->id, 'Flashcard submitted', "{$user->name} submitted a flashcard for approval.", 'info');
    }

    return response()->json([
        'ok' => true,
        'message' => 'Flashcard submitted for admin approval.',
    ], 201);
});

Route::get('/v1/live-classes', function () {
    return response()->json([
        'ok' => true,
        'data' => LiveClass::with('user')->where('status', 'approved')->orderBy('starts_at')->get()->map(fn ($liveClass) => publicLiveClass($liveClass))->all(),
    ]);
});

Route::post('/v1/live-classes', function (Request $request) {
    $user = requireStudent($request);
    $validated = $request->validate([
        'courseCode' => ['required', 'string', 'min:2'],
        'title' => ['required', 'string', 'min:2'],
        'description' => ['nullable', 'string'],
        'accessType' => ['required', 'string'],
        'price' => ['nullable', 'numeric'],
        'startsAt' => ['required', 'date'],
    ]);

    LiveClass::create([
        'user_id' => $user->id,
        'public_id' => 'class-' . Str::lower(Str::random(8)),
        'course_code' => Str::upper($validated['courseCode']),
        'title' => $validated['title'],
        'description' => $validated['description'] ?? '',
        'access_type' => Str::lower($validated['accessType']),
        'price' => $validated['price'] ?? 0,
        'starts_at' => $validated['startsAt'],
        'status' => 'pending',
    ]);

    $admin = User::where('role', 'admin')->first();
    if ($admin) {
        notifyUser($admin->id, 'Live class submitted', "{$user->name} submitted a live class for approval.", 'info');
    }

    return response()->json([
        'ok' => true,
        'message' => 'Live class submitted for admin approval.',
    ], 201);
});

Route::get('/v1/learning/courses/{courseCode}', function (string $courseCode) {
    $normalizedCode = Str::upper($courseCode);

    return response()->json([
        'ok' => true,
        'data' => [
            'materials' => StudyMaterial::with('user')->where('status', 'approved')->where('course_code', $normalizedCode)->latest()->get()->map(fn ($material) => publicStudyMaterial($material))->all(),
            'flashcards' => Flashcard::where('status', 'approved')->where('course_code', $normalizedCode)->latest()->get()->map(fn ($flashcard) => publicFlashcard($flashcard))->all(),
            'liveClasses' => LiveClass::with('user')->where('status', 'approved')->where('course_code', $normalizedCode)->orderBy('starts_at')->get()->map(fn ($liveClass) => publicLiveClass($liveClass))->all(),
        ],
    ]);
});

Route::get('/v1/ai/tutor', function (Request $request) {
    $user = requireStudent($request);

    return response()->json([
        'ok' => true,
        'data' => $user->tutorMessages()->latest()->take(20)->get()->reverse()->values()->map(fn ($message) => publicTutorMessage($message))->all(),
    ]);
});

Route::post('/v1/ai/tutor', function (Request $request) {
    $user = requireStudent($request);
    $validated = $request->validate([
        'prompt' => ['required', 'string', 'min:2'],
    ]);

    $userMessage = TutorMessage::create([
        'user_id' => $user->id,
        'role' => 'user',
        'content' => $validated['prompt'],
    ]);

    $assistantMessage = TutorMessage::create([
        'user_id' => $user->id,
        'role' => 'assistant',
        'content' => tutorReply($validated['prompt']),
    ]);

    return response()->json([
        'ok' => true,
        'message' => 'AI Tutor response ready.',
        'data' => [
            publicTutorMessage($userMessage),
            publicTutorMessage($assistantMessage),
        ],
    ], 201);
});

Route::get('/v1/users', function (Request $request) {
    $viewer = authUser($request);

    $query = User::where('role', 'user');
    if ($viewer) {
        $query->where('id', '!=', $viewer->id);
    }

    return response()->json([
        'ok' => true,
        'data' => $query->latest()->get()->map(fn ($user) => publicUser($user, $viewer))->all(),
    ]);
});

Route::get('/v1/chats/direct', function (Request $request) {
    $viewer = requireAuth($request);
    $conversations = $viewer->conversations()->where('type', 'private')->with(['users', 'messages.user'])->get();

    return response()->json([
        'ok' => true,
        'data' => $conversations->sortByDesc(fn ($conversation) => optional($conversation->messages->sortByDesc('created_at')->first())->created_at ?? $conversation->updated_at)
            ->values()
            ->map(fn ($conversation) => publicConversation($conversation, $viewer))
            ->all(),
    ]);
});

Route::post('/v1/chats/direct', function (Request $request) {
    $viewer = requireAuth($request);
    $validated = $request->validate([
        'userId' => ['required', 'integer', 'exists:users,id'],
    ]);

    $targetId = (int) $validated['userId'];
    abort_if($targetId === $viewer->id, 422, 'You cannot start a chat with yourself.');

    $conversation = Conversation::where('type', 'private')
        ->whereHas('users', fn ($query) => $query->where('users.id', $viewer->id))
        ->whereHas('users', fn ($query) => $query->where('users.id', $targetId))
        ->withCount('users')
        ->get()
        ->first(fn ($item) => $item->users_count === 2);

    if (! $conversation) {
        $conversation = Conversation::create([
            'public_id' => 'chat-' . Str::lower(Str::random(8)),
            'type' => 'private',
        ]);
        $conversation->users()->sync([$viewer->id, $targetId]);
    }

    return response()->json([
        'ok' => true,
        'message' => 'Private chat ready.',
        'data' => publicConversation($conversation->fresh(['users', 'messages.user']), $viewer),
    ], 201);
});

Route::get('/v1/chats/groups', function (Request $request) {
    $viewer = requireAuth($request);
    $conversations = $viewer->conversations()->where('type', 'group')->with(['users', 'messages.user'])->get();

    return response()->json([
        'ok' => true,
        'data' => $conversations->sortByDesc(fn ($conversation) => optional($conversation->messages->sortByDesc('created_at')->first())->created_at ?? $conversation->updated_at)
            ->values()
            ->map(fn ($conversation) => publicConversation($conversation, $viewer))
            ->all(),
    ]);
});

Route::post('/v1/chats/groups', function (Request $request) {
    $viewer = requireAuth($request);
    $validated = $request->validate([
        'name' => ['required', 'string', 'min:2'],
        'memberIds' => ['array'],
    ]);

    $memberIds = collect($validated['memberIds'] ?? [])
        ->map(fn ($id) => (int) $id)
        ->filter(fn ($id) => $id !== $viewer->id)
        ->unique()
        ->values()
        ->all();

    $conversation = Conversation::create([
        'public_id' => 'group-' . Str::lower(Str::random(8)),
        'type' => 'group',
        'name' => $validated['name'],
    ]);

    $conversation->users()->sync(array_values(array_unique([$viewer->id, ...$memberIds])));

    return response()->json([
        'ok' => true,
        'message' => 'Group chat created successfully.',
        'data' => publicConversation($conversation->fresh(['users', 'messages.user']), $viewer),
    ], 201);
});

Route::get('/v1/chats/conversations/{publicId}', function (Request $request, string $publicId) {
    $viewer = requireAuth($request);
    $conversation = Conversation::where('public_id', $publicId)->with(['users', 'messages.user'])->firstOrFail();
    abort_if(! $conversation->users->contains('id', $viewer->id), 403, 'You do not belong to this conversation.');

    return response()->json([
        'ok' => true,
        'data' => [
            'conversation' => publicConversation($conversation, $viewer),
            'messages' => $conversation->messages->sortBy('created_at')->values()->map(fn ($message) => publicMessage($message, $viewer))->all(),
        ],
    ]);
});

Route::post('/v1/chats/conversations/{publicId}/messages', function (Request $request, string $publicId) {
    $viewer = requireAuth($request);
    $validated = $request->validate([
        'body' => ['required', 'string', 'min:1'],
    ]);

    $conversation = Conversation::where('public_id', $publicId)->with('users')->firstOrFail();
    abort_if(! $conversation->users->contains('id', $viewer->id), 403, 'You do not belong to this conversation.');

    $message = Message::create([
        'conversation_id' => $conversation->id,
        'user_id' => $viewer->id,
        'body' => $validated['body'],
    ]);

    $conversation->users
        ->where('id', '!=', $viewer->id)
        ->each(fn ($user) => notifyUser($user->id, 'New message', "{$viewer->name} sent you a new message.", 'info'));

    return response()->json([
        'ok' => true,
        'message' => 'Message sent successfully.',
        'data' => publicMessage($message->load('user'), $viewer),
    ], 201);
});

Route::post('/v1/social/follow', function (Request $request) {
    $viewer = requireStudent($request);

    $validated = $request->validate([
        'userId' => ['required', 'integer', 'exists:users,id'],
        'action' => ['required', 'string'],
    ]);

    $target = User::findOrFail($validated['userId']);

    if ($validated['action'] === 'follow') {
        $viewer->following()->syncWithoutDetaching([$target->id]);
        notifyUser($target->id, 'New follower', "{$viewer->name} started following you.", 'success');
    } else {
        $viewer->following()->detach($target->id);
    }

    return response()->json([
        'ok' => true,
        'message' => $validated['action'] === 'follow' ? 'User followed successfully.' : 'User unfollowed successfully.',
        'data' => $validated,
    ]);
});

Route::post('/v1/social/friend-request', function (Request $request) {
    $viewer = requireStudent($request);

    $validated = $request->validate([
        'userId' => ['required', 'integer', 'exists:users,id'],
    ]);

    $viewer->friends()->syncWithoutDetaching([$validated['userId']]);
    $target = User::findOrFail($validated['userId']);
    $target->friends()->syncWithoutDetaching([$viewer->id]);
    notifyUser($target->id, 'New friend', "{$viewer->name} added you as a friend.", 'success');

    return response()->json([
        'ok' => true,
        'message' => 'Friend request accepted for this local build.',
        'data' => $validated,
    ]);
});

Route::get('/v1/jobs', fn () => response()->json(['ok' => true, 'data' => config('universe.jobs')]));
Route::get('/v1/notifications', function (Request $request) {
    $user = requireAuth($request);

    return response()->json([
        'ok' => true,
        'data' => $user->appNotifications()->latest()->take(50)->get()->map(fn ($notification) => publicNotification($notification))->all(),
    ]);
});

Route::get('/v1/wallet', function () {
    return response()->json([
        'ok' => true,
        'data' => [
            'summary' => config('universe.walletSummary'),
            'transactions' => config('universe.transactions'),
            'paymentMethods' => config('universe.paymentMethods'),
            'paymentAccountDetails' => config('universe.paymentAccountDetails'),
        ],
    ]);
});

Route::get('/v1/admin/overview', function (Request $request) {
    requireAdmin($request);

    return response()->json([
        'ok' => true,
        'data' => [
            'summary' => [
                'totalUsers' => User::where('role', 'user')->count(),
                'verifiedUsers' => User::where('role', 'user')->where('verified', true)->count(),
                'verificationRequests' => User::where('role', 'user')->where('verification_request_status', 'requested')->count(),
                'approvedPosts' => Post::where('status', 'approved')->count(),
                'pendingPosts' => Post::where('status', 'pending')->count(),
                'pendingStories' => Story::where('status', 'pending')->count(),
                'pendingReels' => Reel::where('status', 'pending')->count(),
                'pendingEvents' => CampusEvent::where('status', 'pending')->count(),
                'pendingConfessions' => Confession::where('status', 'pending')->count(),
                'pendingMaterials' => StudyMaterial::where('status', 'pending')->count(),
                'pendingFlashcards' => Flashcard::where('status', 'pending')->count(),
                'pendingLiveClasses' => LiveClass::where('status', 'pending')->count(),
            ],
            'users' => User::where('role', 'user')->latest()->get()->map(fn ($user) => publicUser($user))->all(),
            'verificationRequests' => User::where('role', 'user')->where('verification_request_status', 'requested')->latest('verification_requested_at')->get()->map(fn ($user) => publicUser($user))->all(),
            'pendingPosts' => Post::with('user')->where('status', 'pending')->latest()->get()->map(fn ($post) => publicPost($post))->all(),
            'pendingStories' => Story::with('user')->where('status', 'pending')->latest()->get()->map(fn ($story) => publicStory($story))->all(),
            'pendingReels' => Reel::with('user')->where('status', 'pending')->latest()->get()->map(fn ($reel) => publicReel($reel))->all(),
            'pendingEvents' => CampusEvent::with('user')->where('status', 'pending')->latest()->get()->map(fn ($event) => publicEvent($event))->all(),
            'pendingConfessions' => Confession::with('user')->where('status', 'pending')->latest()->get()->map(fn ($confession) => publicConfession($confession))->all(),
            'pendingMaterials' => StudyMaterial::with('user')->where('status', 'pending')->latest()->get()->map(fn ($material) => publicStudyMaterial($material))->all(),
            'pendingFlashcards' => Flashcard::where('status', 'pending')->latest()->get()->map(fn ($flashcard) => publicFlashcard($flashcard))->all(),
            'pendingLiveClasses' => LiveClass::with('user')->where('status', 'pending')->latest()->get()->map(fn ($liveClass) => publicLiveClass($liveClass))->all(),
            'posts' => Post::with(['user', 'postLikes', 'postComments.user'])->where('status', 'approved')->latest()->get()->map(fn ($post) => publicPost($post))->all(),
        ],
    ]);
});

Route::post('/v1/admin/users/{userId}/verify', function (Request $request, int $userId) {
    requireAdmin($request);
    $user = User::where('role', 'user')->findOrFail($userId);
    $user->update([
        'verified' => true,
        'verification_request_status' => 'approved',
    ]);
    notifyUser($user->id, 'Account verified', 'Your Universe account has been verified by the admin.', 'success');

    return response()->json(['ok' => true, 'message' => 'User verified successfully.']);
});

Route::post('/v1/admin/users/{userId}/unverify', function (Request $request, int $userId) {
    requireAdmin($request);
    $user = User::where('role', 'user')->findOrFail($userId);
    $user->update([
        'verified' => false,
        'verification_request_status' => 'none',
        'verification_requested_at' => null,
    ]);
    notifyUser($user->id, 'Verification removed', 'Your account verification was removed by the admin.', 'warning');

    return response()->json(['ok' => true, 'message' => 'User unverified successfully.']);
});

Route::post('/v1/admin/posts/{publicId}/approve', function (Request $request, string $publicId) {
    requireAdmin($request);
    $post = Post::where('public_id', $publicId)->firstOrFail();
    approveContent($post);
    notifyUser($post->user_id, 'Post approved', 'Your post is now live in the public feed.', 'success');

    return response()->json(['ok' => true, 'message' => 'Post approved successfully.']);
});

Route::post('/v1/admin/posts/{publicId}/reject', function (Request $request, string $publicId) {
    requireAdmin($request);
    $post = Post::where('public_id', $publicId)->firstOrFail();
    rejectContent($post);
    notifyUser($post->user_id, 'Post rejected', 'Your post was reviewed and not published.', 'warning');

    return response()->json(['ok' => true, 'message' => 'Post rejected successfully.']);
});

Route::post('/v1/admin/posts/{publicId}/ban', function (Request $request, string $publicId) {
    requireAdmin($request);
    $post = Post::where('public_id', $publicId)->where('status', 'approved')->firstOrFail();
    $post->update([
        'status' => 'banned',
        'approved_at' => null,
    ]);
    notifyUser($post->user_id, 'Post banned', 'Your post was removed by the admin for violating community guidelines.', 'warning');

    return response()->json(['ok' => true, 'message' => 'Post banned successfully.']);
});

Route::delete('/v1/admin/posts/{publicId}', function (Request $request, string $publicId) {
    requireAdmin($request);
    $post = Post::where('public_id', $publicId)->firstOrFail();
    $ownerId = $post->user_id;
    $post->delete();
    notifyUser($ownerId, 'Post deleted', 'Your post was permanently deleted by the admin.', 'warning');

    return response()->json(['ok' => true, 'message' => 'Post deleted successfully.']);
});

Route::post('/v1/admin/stories/{publicId}/approve', function (Request $request, string $publicId) {
    requireAdmin($request);
    $story = Story::where('public_id', $publicId)->firstOrFail();
    approveContent($story);
    notifyUser($story->user_id, 'Story approved', 'Your story is now live.', 'success');

    return response()->json(['ok' => true, 'message' => 'Story approved successfully.']);
});

Route::post('/v1/admin/stories/{publicId}/reject', function (Request $request, string $publicId) {
    requireAdmin($request);
    $story = Story::where('public_id', $publicId)->firstOrFail();
    rejectContent($story);
    notifyUser($story->user_id, 'Story rejected', 'Your story was reviewed and not published.', 'warning');

    return response()->json(['ok' => true, 'message' => 'Story rejected successfully.']);
});

Route::post('/v1/admin/reels/{publicId}/approve', function (Request $request, string $publicId) {
    requireAdmin($request);
    $reel = Reel::where('public_id', $publicId)->firstOrFail();
    approveContent($reel);
    notifyUser($reel->user_id, 'Reel approved', 'Your reel is now public.', 'success');

    return response()->json(['ok' => true, 'message' => 'Reel approved successfully.']);
});

Route::post('/v1/admin/reels/{publicId}/reject', function (Request $request, string $publicId) {
    requireAdmin($request);
    $reel = Reel::where('public_id', $publicId)->firstOrFail();
    rejectContent($reel);
    notifyUser($reel->user_id, 'Reel rejected', 'Your reel was reviewed and not published.', 'warning');

    return response()->json(['ok' => true, 'message' => 'Reel rejected successfully.']);
});

Route::post('/v1/admin/events/{publicId}/approve', function (Request $request, string $publicId) {
    requireAdmin($request);
    $event = CampusEvent::where('public_id', $publicId)->firstOrFail();
    approveContent($event);
    notifyUser($event->user_id, 'Event approved', 'Your event is now public.', 'success');

    return response()->json(['ok' => true, 'message' => 'Event approved successfully.']);
});

Route::post('/v1/admin/events/{publicId}/reject', function (Request $request, string $publicId) {
    requireAdmin($request);
    $event = CampusEvent::where('public_id', $publicId)->firstOrFail();
    rejectContent($event);
    notifyUser($event->user_id, 'Event rejected', 'Your event was reviewed and not published.', 'warning');

    return response()->json(['ok' => true, 'message' => 'Event rejected successfully.']);
});

Route::post('/v1/admin/confessions/{publicId}/approve', function (Request $request, string $publicId) {
    requireAdmin($request);
    $confession = Confession::where('public_id', $publicId)->firstOrFail();
    approveContent($confession);
    notifyUser($confession->user_id, 'Confession approved', 'Your anonymous confession is now public.', 'success');

    return response()->json(['ok' => true, 'message' => 'Confession approved successfully.']);
});

Route::post('/v1/admin/confessions/{publicId}/reject', function (Request $request, string $publicId) {
    requireAdmin($request);
    $confession = Confession::where('public_id', $publicId)->firstOrFail();
    rejectContent($confession);
    notifyUser($confession->user_id, 'Confession rejected', 'Your confession was reviewed and not published.', 'warning');

    return response()->json(['ok' => true, 'message' => 'Confession rejected successfully.']);
});

Route::post('/v1/admin/study-materials/{publicId}/approve', function (Request $request, string $publicId) {
    requireAdmin($request);
    $material = StudyMaterial::where('public_id', $publicId)->firstOrFail();
    approveContent($material);
    notifyUser($material->user_id, 'Study material approved', 'Your study material is now visible in the study hub.', 'success');

    return response()->json(['ok' => true, 'message' => 'Study material approved successfully.']);
});

Route::post('/v1/admin/study-materials/{publicId}/reject', function (Request $request, string $publicId) {
    requireAdmin($request);
    $material = StudyMaterial::where('public_id', $publicId)->firstOrFail();
    rejectContent($material);
    notifyUser($material->user_id, 'Study material rejected', 'Your study material was reviewed and not published.', 'warning');

    return response()->json(['ok' => true, 'message' => 'Study material rejected successfully.']);
});

Route::post('/v1/admin/flashcards/{publicId}/approve', function (Request $request, string $publicId) {
    requireAdmin($request);
    $flashcard = Flashcard::where('public_id', $publicId)->firstOrFail();
    approveContent($flashcard);
    notifyUser($flashcard->user_id, 'Flashcard approved', 'Your flashcard is now available publicly.', 'success');

    return response()->json(['ok' => true, 'message' => 'Flashcard approved successfully.']);
});

Route::post('/v1/admin/flashcards/{publicId}/reject', function (Request $request, string $publicId) {
    requireAdmin($request);
    $flashcard = Flashcard::where('public_id', $publicId)->firstOrFail();
    rejectContent($flashcard);
    notifyUser($flashcard->user_id, 'Flashcard rejected', 'Your flashcard was reviewed and not published.', 'warning');

    return response()->json(['ok' => true, 'message' => 'Flashcard rejected successfully.']);
});

Route::post('/v1/admin/live-classes/{publicId}/approve', function (Request $request, string $publicId) {
    requireAdmin($request);
    $liveClass = LiveClass::where('public_id', $publicId)->firstOrFail();
    approveContent($liveClass);
    notifyUser($liveClass->user_id, 'Live class approved', 'Your live class is now visible publicly.', 'success');

    return response()->json(['ok' => true, 'message' => 'Live class approved successfully.']);
});

Route::post('/v1/admin/live-classes/{publicId}/reject', function (Request $request, string $publicId) {
    requireAdmin($request);
    $liveClass = LiveClass::where('public_id', $publicId)->firstOrFail();
    rejectContent($liveClass);
    notifyUser($liveClass->user_id, 'Live class rejected', 'Your live class was reviewed and not published.', 'warning');

    return response()->json(['ok' => true, 'message' => 'Live class rejected successfully.']);
});
