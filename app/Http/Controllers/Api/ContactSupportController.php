<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactSupportController extends Controller
{
    public function email(): JsonResponse
    {
        return response()->json([
            'success' => 200,
            'email' => ContactMessage::supportEmail(),
            'label' => 'Email us',
            'reply_time' => 'Typical reply time: within 24 hours',
        ], 200);
    }

    public function send(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'message' => 'required|string|min:10|max:5000',
            ]);

            $user = $request->user();
            $supportEmail = ContactMessage::supportEmail();

            $contact = ContactMessage::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'message' => $data['message'],
                'is_read' => false,
            ]);

            $emailSent = $this->notifyAdmin($contact, $supportEmail);

            return response()->json([
                'success' => 200,
                'message' => 'Your message has been sent. We will get back to you as soon as possible.',
                'email_sent' => $emailSent,
                'support_email' => $supportEmail,
                'contact_message_id' => $contact->id,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Contact support failed', [
                'user_id' => optional($request->user())->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    private function notifyAdmin(ContactMessage $contact, string $supportEmail): bool
    {
        if ($supportEmail === '') {
            return false;
        }

        try {
            $subject = 'New contact message from ' . ($contact->name ?: 'a user');

            Mail::send('emails.contact-support', [
                'subject' => $subject,
                'heading' => 'New Contact Message',
                'logoUrl' => url('assets/img/piyari_logo.png'),
                'greeting' => 'Hello Admin,',
                'contact' => $contact,
            ], function ($mail) use ($supportEmail, $subject, $contact) {
                $mail->to($supportEmail)
                    ->subject($subject)
                    ->from(config('mail.from.address'), 'Piyari Family');

                if ($contact->email) {
                    $mail->replyTo($contact->email, $contact->name);
                }
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to email admin about contact message.', [
                'contact_message_id' => $contact->id,
                'support_email' => $supportEmail,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
