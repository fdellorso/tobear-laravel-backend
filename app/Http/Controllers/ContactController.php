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

        $adminEmail = env('CONTACT_NOTIFICATION_EMAIL');

        if ($adminEmail) {
            Notification::route('mail', $adminEmail)
                ->notify(new NewContactMessage($contactMessage));
        }

        return response()->json([
            'message' => 'Messaggio ricevuto. Ti risponderemo al più presto.',
        ], 201);
    }
}
