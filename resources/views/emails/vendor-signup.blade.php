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
    <p>Review and approve them in the admin panel:
        <a href="{{ route('admin.vendors.index') }}">{{ route('admin.vendors.index') }}</a>
    </p>
</body>
</html>
