<!DOCTYPE html>
<html>
<head>
    <title>Password Reset OTP</title>
</head>
<body>
    <h2>Password Reset Request</h2>
    <p>Your OTP for password reset is:</p>
    
    <h1 style="font-size: 32px; letter-spacing: 10px; text-align: center;">
        {{ $otp }}
    </h1>
    
    <p>This OTP will expire in 15 minutes.</p>
    <p>If you didn't request this, please ignore this email.</p>
</body>
</html>