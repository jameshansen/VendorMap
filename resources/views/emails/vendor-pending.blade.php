<!DOCTYPE html>
<html lang="en">
<body style="font-family: Arial, sans-serif; color:#1b2430; line-height:1.5;">
    <h2>Thanks for signing up! 👋</h2>
    <p>Hi {{ $vendor->contact_name ?: $vendor->business_name }},</p>
    <p>We've received your application for <strong>{{ $vendor->business_name }}</strong> and it's now
       pending approval.</p>
    <p style="background:rgba(31,157,104,0.10); border:1px solid #1f9d68; color:#14613f;
       padding:14px 16px; border-radius:8px;">
        <strong>We review every request quickly, usually within 24 hours.</strong>
        You'll get an email the moment you're approved, and then you can book tables for any open event.
    </p>
</body>
</html>
