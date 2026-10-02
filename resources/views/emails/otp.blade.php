@component('emails.layout', [
    'subject' => $subject,
    'heading' => $heading,
    'logoUrl' => $logoUrl,
    'greeting' => $greeting ?? null,
])
    <p style="margin:0 0 20px;">{{ $messageLine }}</p>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center" style="padding:20px;background-color:#FFF5F5;border:2px solid #F5A623;border-radius:10px;">
                <p style="margin:0 0 8px;color:#666666;font-size:13px;text-transform:uppercase;letter-spacing:1px;">Your Verification Code</p>
                <p style="margin:0;color:#6E0016;font-size:36px;font-weight:700;letter-spacing:8px;">{{ $otp }}</p>
            </td>
        </tr>
    </table>
    <p style="margin:20px 0 0;color:#666666;font-size:13px;">This code expires in {{ $expiryMinutes ?? 5 }} minutes. Please do not share it with anyone.</p>
@endcomponent
