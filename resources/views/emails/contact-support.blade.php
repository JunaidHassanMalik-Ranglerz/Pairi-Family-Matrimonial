@component('emails.layout', [
    'subject' => $subject,
    'heading' => $heading,
    'logoUrl' => $logoUrl,
    'greeting' => $greeting ?? null,
])
    <p style="margin:0 0 16px;">A user sent a message from the Piyari Family app.</p>
    <p style="margin:0 0 8px;"><strong>Name:</strong> {{ $contact->name ?: '—' }}</p>
    <p style="margin:0 0 8px;"><strong>Email:</strong> {{ $contact->email ?: '—' }}</p>
    <p style="margin:0 0 16px;"><strong>Phone:</strong> {{ $contact->phone ?: '—' }}</p>
    <p style="margin:0 0 8px;"><strong>Message:</strong></p>
    <p style="margin:0;white-space:pre-wrap;">{{ $contact->message }}</p>
@endcomponent
