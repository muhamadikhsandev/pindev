<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Notifikasi PINDEV' }}</title>
    <style>
        body { background-color: #030712; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .wrapper { width: 100%; padding: 40px 0; background-color: #030712; }
        .container { 
            max-width: 450px; margin: 0 auto; 
            background-color: #0f172a; 
            border: 1px solid #1e293b; border-radius: 24px; padding: 40px;
            text-align: center;
        }
        .header { margin-bottom: 25px; }
        .logo-img { width: 48px; height: 48px; margin-bottom: 10px; }
        .logo-text { font-size: 28px; font-weight: 800; color: #6366f1; letter-spacing: -1px; display: block; }
        h1 { color: #ffffff; font-size: 22px; margin-bottom: 15px; font-weight: 700; }
        p { color: #94a3b8; font-size: 15px; line-height: 1.6; margin-bottom: 20px; }
        .btn { 
            display: inline-block; padding: 16px 32px; background-color: #4f46e5; 
            color: #ffffff !important; text-decoration: none; border-radius: 16px; 
            font-weight: bold; font-size: 14px; margin: 20px 0;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4);
        }
        .footer { font-size: 12px; color: #475569; margin-top: 35px; border-top: 1px solid #1e293b; padding-top: 20px; }
        .expiry { color: #6366f1; font-weight: 600; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                {{-- Pastikan favicon.ico bisa diakses publik atau gunakan URL absolut --}}
                <img src="http://localhost:3000/favicon.ico" alt="Logo" class="logo-img">
                <span class="logo-text">PINDEV</span>
            </div>
            
            <h1>{{ $title }}</h1>
            <p>Halo <strong>{{ $name }}</strong>,</p>
            <p>{{ $body }}</p>
            
            <a href="{{ $url }}" class="btn">{{ $button_text }}</a>
            
            <p>Tautan ini akan kedaluwarsa dalam <span class="expiry">60 menit</span>. Jika Anda tidak merasa melakukan permintaan ini, silakan abaikan email ini.</p>
            
            <div class="footer">
                &copy; {{ date('Y') }} PINDEV Tech. Dikirim secara aman ke email Anda.
            </div>
        </div>
    </div>
</body>
</html>