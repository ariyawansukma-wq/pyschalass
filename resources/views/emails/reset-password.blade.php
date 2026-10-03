<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Your Password</title>
  <style>
    body {
      background-color: #f8fafc;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      margin: 0;
      padding: 0;
      width: 100% !important;
      -webkit-text-size-adjust: none;
      -ms-text-size-adjust: none;
    }
    .wrapper {
      padding: 40px 20px;
      max-width: 540px;
      margin: 0 auto;
    }
    .card {
      background-color: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 16px;
      padding: 40px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
    }
    .logo-container {
      margin-bottom: 30px;
      text-align: center;
    }
    .logo-text {
      font-size: 20px;
      font-weight: 800;
      letter-spacing: -0.02em;
      color: #0f172a;
    }
    .logo-highlight {
      color: #2563eb;
    }
    .greeting {
      font-size: 16px;
      font-weight: 700;
      color: #0f172a;
      margin-top: 0;
      margin-bottom: 16px;
    }
    .paragraph {
      font-size: 14px;
      line-height: 1.6;
      color: #475569;
      margin-top: 0;
      margin-bottom: 24px;
    }
    .btn-container {
      text-align: center;
      margin: 30px 0;
    }
    .btn {
      display: inline-block;
      background-color: #2563eb;
      color: #ffffff !important;
      text-decoration: none;
      font-size: 14px;
      font-weight: 700;
      padding: 12px 24px;
      border-radius: 8px;
      box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
    }
    .footer {
      text-align: center;
      font-size: 11px;
      font-weight: 600;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      margin-top: 30px;
    }
    .divider {
      height: 1px;
      background-color: #f1f5f9;
      margin: 30px 0 20px;
    }
    .subtext {
      font-size: 12px;
      line-height: 1.5;
      color: #64748b;
    }
    .link-display {
      word-break: break-all;
      color: #2563eb;
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="card">
      <!-- Logo header -->
      <div class="logo-container">
        <span class="logo-text">Physical<span class="logo-highlight">Score</span></span>
      </div>

      <!-- Greeting -->
      <p class="greeting">Hello, {{ $name }}</p>
      
      <!-- Body Text -->
      <p class="paragraph">
        You are receiving this email because we received a password reset request for your account. Please click the button below to establish a new password.
      </p>

      <!-- Action Button -->
      <div class="btn-container">
        <a href="{{ $resetUrl }}" class="btn" target="_blank">Reset Password</a>
      </div>

      <!-- Expiry Notice -->
      <p class="paragraph">
        This password reset link will expire in <strong>{{ $expire }} minutes</strong>. If you did not request a password reset, no further action is required.
      </p>
      
      <!-- Link Fallback -->
      <div class="divider"></div>
      <p class="subtext">
        If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:
      </p>
      <p class="subtext">
        <a href="{{ $resetUrl }}" class="link-display" target="_blank">{{ $resetUrl }}</a>
      </p>
    </div>

    <!-- Platform Footer -->
    <div class="footer">
      &copy; 2026 Index Fit Lab. All rights reserved.
    </div>
  </div>
</body>
</html>
