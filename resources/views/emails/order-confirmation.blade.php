<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #111; color: #fff; padding: 20px; text-align: center; }
        .qr { text-align: center; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border-bottom: 1px solid #eee; text-align: left; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Order Confirmed</h1>
        <p>Order #{{ $order->order_number }}</p>
    </div>

    <p>Hi {{ $order->user->name }},</p>
    <p>Your payment has been verified. Here are your order details:</p>

    <table>
        <thead><tr><th>Item</th><th>Qty</th><th>Price</th></tr></thead>
        <tbody>
        @foreach($order->items as $item)
            <tr>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->quantity }}</td>
                <td>₹{{ number_format($item->total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <p><strong>Total: ₹{{ number_format($order->total, 2) }}</strong></p>

    @if($deliveryToken)
        <p><strong>Delivery Token:</strong> {{ substr($deliveryToken, 0, 16) }}...</p>
    @endif

    @if($qrBase64)
        <div class="qr">
            <p>Show this QR code at delivery:</p>
            <img src="data:image/png;base64,{{ $qrBase64 }}" alt="Delivery QR" width="200">
        </div>
    @endif

    <p>Shipping to:<br>
        {{ $order->shipping_address }}<br>
        {{ $order->shipping_city }}, {{ $order->shipping_state }} - {{ $order->shipping_pincode }}
    </p>
</div>
</body>
</html>
