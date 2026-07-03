<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vendor approved</title>
    <style>
        body { font-family: Arial, sans-serif; color:#1b2430; background:#f4f6f9;
               display:flex; min-height:100vh; margin:0; align-items:center; justify-content:center; }
        .card { background:#fff; padding:2.5rem; border-radius:12px; max-width:420px; text-align:center;
                box-shadow:0 6px 24px rgba(0,0,0,.08); }
        h1 { margin:.2rem 0 1rem; font-size:1.4rem; }
        .tick { font-size:2.5rem; color:#1f9d55; }
    </style>
</head>
<body>
    <div class="card">
        <div class="tick">✓</div>
        <h1>{{ $already ? 'Already approved' : 'Vendor approved' }}</h1>
        <p><strong>{{ $vendor->business_name }}</strong>
            {{ $already ? 'was already approved.' : 'has been approved and notified by email.' }}</p>
    </div>
</body>
</html>
