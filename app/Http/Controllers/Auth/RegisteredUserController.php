<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Affiche la vue d'inscription.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Gère une demande d'inscription entrante.
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. Validation des données
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'string', 'in:investor,project_owner'],
            'terms' => ['accepted'], // Vérifie que les conditions sont cochées
        ]);

        // 2. Création de l'utilisateur
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        // 3. Déclenchement de l'événement de succès et connexion automatique
        event(new Registered($user));
        $user->notify(new WelcomeNotification($user));
        Auth::login($user);

        // 4. Préparation du message personnalisé pour la page de succès
        $roleName = ($user->role === 'investor') ? 'Investisseur' : 'Coopérative';

        // 5. Redirection vers la page de succès (définie dans web.php)
        return redirect()->route('auth.success')->with([
            'type'    => 'registration',
            'role'    => $user->role,
            'title'   => 'Identité Créée !',
            'message' => "Félicitations {$user->name}, votre profil {$roleName} a été généré et ancré au protocole AgroTrace BTC."
        ]);
    }
}
