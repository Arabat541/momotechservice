<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 520px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; }
        .header { background: #1a1a2e; padding: 24px 32px; }
        .header h1 { color: #fff; margin: 0; font-size: 20px; }
        .body { padding: 32px; color: #333; }
        .code { font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #1a1a2e;
                background: #f0f0f0; padding: 16px 24px; border-radius: 6px;
                text-align: center; margin: 24px 0; }
        .footer { padding: 16px 32px; background: #f9f9f9; font-size: 12px; color: #888; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>{{ config('app.name') }}</h1>
    </div>
    <div class="body">
        <p>Bonjour {{ $nom }},</p>
        <p>Vous avez demandé la réinitialisation de votre mot de passe. Voici votre code :</p>
        <div class="code">{{ $code }}</div>
        <p>Ce code est valable <strong>15 minutes</strong>. Si vous n'avez pas fait cette demande, ignorez cet email.</p>
    </div>
    <div class="footer">
        Cet email a été envoyé automatiquement, merci de ne pas y répondre.
    </div>
</div>
</body>
</html>
