@component('emails.layout', [
    'subject' => $subject,
    'heading' => $heading,
    'logoUrl' => $logoUrl,
    'greeting' => $greeting ?? null,
])
    <p style="margin:0 0 16px;">{{ $messageLine }}</p>
    <p style="margin:0;color:#666666;font-size:13px;">You can now log in to the Piyari Family app and continue using your account.</p>
@endcomponent
