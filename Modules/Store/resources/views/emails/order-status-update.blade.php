<!DOCTYPE html>
<html>
<head>
    <title>Order Status Update</title>
</head>
<body>
    <h1>Order Status Updated</h1>
    <p>Your order status has been updated:</p>
    <p>Order Number: #{{ $order->order_number }}</p>
    <p>From: {{ ucfirst($oldStatus) }}</p>
    <p>To: {{ ucfirst($newStatus) }}</p>
</body>
</html>