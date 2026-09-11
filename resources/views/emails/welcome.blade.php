<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Merci et bienvenue sur AgroTrace!</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #f9f9f9; padding: 30px; border-radius: 8px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #063b27; margin: 0; }
        .content { background: white; padding: 20px; border-radius: 8px; border: 1px solid #eee; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #888; }
        .btn { display: inline-block; padding: 10px 20px; background: #063b27; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 20px; }
        .steps { margin: 20px 0; padding-left: 20px; }
        .steps li { margin-bottom: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>AgroTrace</h1>
        </div>
        <div class="content">
            <h2>Merci et bienvenue sur AgroTrace!</h2>
            <p>Bonjour {{ $user->name }},</p>
            <p>Nous vous remercions de vous être inscrit sur AgroTrace. Votre compte a bien été créé avec le rôle <strong>{{ $roleName }}</strong>.</p>

            @if ($user->role === 'investor')
                <p>En tant qu'Investisseur, vous pourrez explorer des projets agricoles ancrés au protocole Bitcoin et investir directement dans ceux qui vous intéressent.</p>
                <p><strong>Voici les prochaines étapes :</strong></p>
                <ol class="steps">
                    <li>Compléter votre profil d'investisseur</li>
                    <li>Consulter les projets disponibles</li>
                    <li>Investir dans un projet</li>
                    <li>Suivre vos retours</li>
                </ol>
            @else
                <p>En tant que Coopérative, vous pourrez présenter vos projets agricoles, définir vos jalons et recueillir des investissements pour les mener à bien.</p>
                <p><strong>Voici les prochaines étapes :</strong></p>
                <ol class="steps">
                    <li>Compléter votre profil de coopérative</li>
                    <li>Créer votre premier projet</li>
                    <li>Définir vos jalons (milestones)</li>
                    <li>Recueillir les investissements</li>
                </ol>
            @endif

            <div style="text-align: center;">
                <a href="{{ url('/dashboard') }}" class="btn">Accéder à mon tableau de bord</a>
            </div>
        </div>
        <div class="footer">
            <p>Ceci est un email automatique, merci de ne pas y répondre.</p>
            <p>&copy; {{ date('Y') }} AgroTrace. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
