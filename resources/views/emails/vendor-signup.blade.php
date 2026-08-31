<!DOCTYPE html>
<html lang="en">
<body style="font-family: Arial, sans-serif; color:#1b2430; line-height:1.5;">
    <h2>New vendor application</h2>
    <p>A new vendor has signed up and is awaiting approval.</p>
    <table cellpadding="6" style="border-collapse:collapse;">
        <tr><td valign="top"><strong>Business</strong></td><td>{{ $vendor->business_name ?: '—' }}</td></tr>
        <tr><td valign="top"><strong>Contact</strong></td><td>{{ $vendor->contact_name ?: '—' }}</td></tr>
        <tr><td valign="top"><strong>Email</strong></td><td>{{ $vendor->email ?: '—' }}</td></tr>
        @if ($vendor->user && $vendor->user->email !== $vendor->email)
            <tr><td valign="top"><strong>Login account</strong></td><td>{{ $vendor->user->email }}</td></tr>
        @endif
        <tr><td valign="top"><strong>Phone</strong></td><td>{{ $vendor->phone ?: '—' }}</td></tr>
        <tr><td valign="top"><strong>Address</strong></td><td>{{ $vendor->address ?: '—' }}</td></tr>
        <tr><td valign="top"><strong>Website</strong></td><td>{{ $vendor->website ?: '—' }}</td></tr>
        @foreach (\App\Support\VendorProfile::SOCIALS as $key => $label)
            @if (! empty($vendor->socials[$key]))
                <tr><td valign="top"><strong>{{ $label }}</strong></td><td>{{ $vendor->socials[$key] }}</td></tr>
            @endif
        @endforeach
        <tr><td valign="top"><strong>Categories</strong></td><td>{{ ! empty($vendor->categories) ? implode(', ', $vendor->categories) : '—' }}</td></tr>
        <tr><td valign="top"><strong>Note</strong></td><td>{{ $vendor->application_note ?: '—' }}</td></tr>
        <tr><td valign="top"><strong>Status</strong></td><td>{{ ucfirst($vendor->status) }}</td></tr>
        <tr><td valign="top"><strong>Submitted</strong></td><td>{{ $vendor->created_at?->format('D j M Y, g:ia') ?? '—' }}</td></tr>
    </table>
    @if (!empty($guidance))
        <div style="margin:20px 0;padding:14px 18px;background:#f4f7ff;border-left:4px solid #2f6df0;border-radius:6px;">
            <p style="margin:0 0 6px;font-weight:bold;">✨ AI Vendor Guidance</p>
            <p style="margin:0;">{{ $guidance['text'] }}</p>
        </div>
    @endif
    <p style="margin:24px 0;">
        <a href="{{ $approveUrl }}" style="display:inline-block;background:#1f9d55;color:#fff;
           text-decoration:none;padding:12px 22px;border-radius:6px;font-weight:bold;">
            ✓ Approve this vendor
        </a>
    </p>
    <p style="color:#5a6472;font-size:13px;">One click approves them instantly — no admin login needed.
        Prefer to review first? Open the
        <a href="{{ route('admin.vendors.index') }}">admin panel</a>.
    </p>
</body>
</html>
