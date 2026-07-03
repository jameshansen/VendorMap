<!DOCTYPE html>
<html lang="en">
<body style="font-family: Arial, sans-serif; color:#1b2430; line-height:1.5;">
    <h2>New vendor application</h2>
    <p>A new vendor has signed up and is awaiting approval.</p>
    <table cellpadding="6" style="border-collapse:collapse;">
        <tr><td><strong>Business</strong></td><td>{{ $vendor->business_name }}</td></tr>
        <tr><td><strong>Contact</strong></td><td>{{ $vendor->contact_name }}</td></tr>
        <tr><td><strong>Email</strong></td><td>{{ $vendor->email }}</td></tr>
        <tr><td><strong>Phone</strong></td><td>{{ $vendor->phone }}</td></tr>
        <tr><td><strong>Address</strong></td><td>{{ $vendor->address }}</td></tr>
        <tr><td><strong>Website</strong></td><td>{{ $vendor->website }}</td></tr>
        @if ($vendor->application_note)
            <tr><td valign="top"><strong>Note</strong></td><td>{{ $vendor->application_note }}</td></tr>
        @endif
    </table>
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
