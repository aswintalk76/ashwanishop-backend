<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family:Arial,sans-serif;">
<div style="max-width:600px;margin:0 auto;padding:20px;">
    <h1>Thank You! 🎉</h1>
    <p>Hi {{ $order->user->name }},</p>
    <p>Your order <strong>#{{ $order->order_number }}</strong> has been delivered successfully.</p>
    <p>We hope you love your purchase! Please take a moment to leave us a review:</p>
    <p style="text-align:center;margin:30px 0;">
        <a href="{{ config('app.google_review_url', env('GOOGLE_REVIEW_URL')) }}"
           style="background:#111;color:#fff;padding:12px 24px;text-decoration:none;border-radius:6px;">
            Leave a Google Review
        </a>
    </p>
    <p>Thank you for shopping with Ashwani Shop!</p>
</div>
</body>
</html>
