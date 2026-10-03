<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account Deactivated</title>
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
    .footer {
      text-align: center;
      font-size: 11px;
      font-weight: 600;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      margin-top: 30px;
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
      <p class="greeting">Hello, {{ $user->name }}</p>
      
      <!-- Body Text -->
      <p class="paragraph">
        We would like to inform you that your account has been automatically deactivated as your active period has expired.
      </p>

      <p class="paragraph" style="margin-bottom: 0;">
        If you would like to extend your account access or if you have any questions, please contact your administrator.
      </p>
    </div>

    <!-- Platform Footer -->
    <div class="footer">
      &copy; {{ date('Y') }} Index Fit Lab. All rights reserved.
    </div>
  </div>
</body>
</html>