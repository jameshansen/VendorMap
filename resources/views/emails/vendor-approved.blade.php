<!DOCTYPE html>
<html lang="en">
<body style="font-family: Arial, sans-serif; color:#1b2430; line-height:1.5;">
    <h2>You're approved! 🎉</h2>
    <p>Hi {{ $vendor->contact_name ?: $vendor->business_name }},</p>
    <p>Your VendorMap account has been approved. You can now sign in and book a table
       for any open event.</p>
    <p><a href="{{ route('home') }}" style="display:inline-block;background:#2f6df0;color:#fff;
       text-decoration:none;padding:10px 18px;border-radius:6px;">Browse events</a></p>
    <p>See you there!</p>
</body>
</html>
