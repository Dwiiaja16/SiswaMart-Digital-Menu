<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Kata Sandi SiswaMart</title>
</head>
<body style="margin:0; padding:24px; background:#f7f5ef; color:#111827; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px; background:#ffffff; border:4px solid #111827; border-radius:24px; overflow:hidden; box-shadow:8px 8px 0 #111827;">
                    <tr>
                        <td style="padding:30px; background:#fbbf24; border-bottom:4px solid #111827;">
                            <div style="font-size:30px; font-weight:900; letter-spacing:-1px; text-transform:uppercase;">Siswa<span style="color:#f97316;">Mart</span></div>
                            <div style="margin-top:6px; font-size:11px; font-weight:800; letter-spacing:1.5px; text-transform:uppercase;">Portal Wirausaha SMKN 11</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 30px;">
                            <div style="display:inline-block; padding:7px 12px; background:#f97316; border:2px solid #111827; border-radius:999px; font-size:11px; font-weight:900; letter-spacing:1px; text-transform:uppercase;">Keamanan Akun</div>
                            <h1 style="margin:20px 0 12px; font-size:27px; line-height:1.15;">Reset kata sandi Anda</h1>
                            <p style="margin:0; font-size:15px; line-height:1.65;">Halo {{ $user->username }}, kami menerima permintaan untuk membuat kata sandi baru pada akun SiswaMart Anda.</p>
                            <p style="margin:14px 0 26px; font-size:15px; line-height:1.65;">Klik tombol di bawah untuk melanjutkan. Jika Anda tidak meminta reset ini, abaikan email ini.</p>
                            <a href="{{ $resetUrl }}" style="display:inline-block; padding:15px 20px; background:#f97316; color:#ffffff; border:3px solid #111827; border-radius:12px; box-shadow:4px 4px 0 #111827; font-size:13px; font-weight:900; letter-spacing:.5px; text-decoration:none; text-transform:uppercase;">Reset Kata Sandi Sekarang</a>
                            <p style="margin:30px 0 0; padding-top:20px; border-top:2px solid #111827; color:#4b5563; font-size:12px; line-height:1.6;">Demi keamanan akun, jangan teruskan tautan ini kepada siapa pun.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
