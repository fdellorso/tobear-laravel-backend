<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use App\Notifications\NewContactMessage;
use Illuminate\Support\Facades\Notification;

class ContactController extends Controller
{
    public function store(StoreContactMessageRequest $request)
    {
        $contactMessage = ContactMessage::create($request->validated());

        $adminEmail = config('app.contact_notification_email');

        if ($adminEmail) {
            Notification::route('mail', $adminEmail)
                ->notify(new NewContactMessage($contactMessage));
        }

        return response()->json([
            'message' => 'Message received. We will get back to you as soon as possible.',
        ], 201);
    }
}
