<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    public function index()
    {
        $messages = ContactMessage::query()->with('user:id,name,email,phone')->latest()->get();
        $supportEmail = ContactMessage::supportEmail();

        return view('admin.contact_messages.index', compact('messages', 'supportEmail'));
    }

    public function show(ContactMessage $contactMessage)
    {
        if (!$contactMessage->is_read) {
            $contactMessage->update(['is_read' => true]);
        }

        $contactMessage->load('user:id,name,email,phone');

        return view('admin.contact_messages.show', compact('contactMessage'));
    }

    public function destroy(ContactMessage $contactMessage)
    {
        $contactMessage->delete();

        return redirect()->route('admin.contact-messages.index')->with([
            'message' => 'Contact message deleted.',
            'alert-type' => 'success',
        ]);
    }

    public function updateEmail(Request $request)
    {
        $data = $request->validate([
            'support_email' => 'required|email|max:150',
        ]);

        SystemSetting::setVal('support_email', strtolower(trim($data['support_email'])));

        return redirect()->route('admin.contact-messages.index')->with([
            'message' => 'Support email updated successfully.',
            'alert-type' => 'success',
        ]);
    }
}
