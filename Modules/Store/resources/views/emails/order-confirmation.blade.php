<!DOCTYPE html>
<html>
<head>
    <title>Order Confirmation</title>
</head>
<body>
    <h1>Order Confirmation</h1>
    <p>Thank you for your order!</p>
    <p>Order Number: #{{ $order->order_number }}</p>
    <p>Total: {{ $order->total }} {{ $order->currency }}</p>
    <!-- Add more order details -->
</body>
</html>