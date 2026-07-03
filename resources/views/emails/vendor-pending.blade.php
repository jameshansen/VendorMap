<!DOCTYPE html>
<html lang="en">
<body style="font-family: Arial, sans-serif; color:#1b2430; line-height:1.5;">
    <h2>Thanks for signing up! 👋</h2>
    <p>Hi {{ $vendor->contact_name ?: $vendor->business_name }},</p>
    <p>We've received your application for <strong>{{ $vendor->business_name }}</strong> and it's now
       pending approval.</p>
    <p style="background:rgba(201,145,42,0.12); border:1px solid #c9912a; color:#7a5708;
       padding:14px 16px; border-radius:8px;">
        <strong>We review every request quickly — usually within 24 hours.</strong>
        You'll get an email the moment you're approved, and then you can book tables for any open event.
    </p>
    <p>In the meantime you can sign in and browse upcoming events.</p>
    <p><a href="{{ route('home') }}" style="display:inline-block;background:#2f6df0;color:#fff;
       text-decoration:none;padding:10px 18px;border-radius:6px;">Browse events</a></p>
</body>
</html>
