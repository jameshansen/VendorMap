<!DOCTYPE html>
<html lang="en">
<body style="font-family: Arial, sans-serif; color:#1b2430; line-height:1.5;">
    <h2>Update on your application</h2>
    <p>Hi {{ $vendor->contact_name ?: $vendor->business_name }},</p>
    <p>Thank you for your interest. Unfortunately we're unable to approve your
       VendorMap account at this time.</p>
    @if ($vendor->admin_notes)
        <p>{{ $vendor->admin_notes }}</p>
    @endif
    <p>If you think this was a mistake, please reply to this email.</p>
</body>
</html>
