<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Project;
use App\Models\Milestone;
use App\Models\Investment;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/about', function () {
    return view('about');
});

// Public Project Routes
Route::get('/projects', function () {
    return view('projects', ['projects' => Project::with('user')->get()]);
});
Route::get('/verification', function () {
    return view('verification', ['milestones' => Milestone::with('project')->where('status', 'validated')->get()]);
});
Route::get('/impact-map', function () {
    return view('impact-map', [
        'projects' => Project::with(['user', 'investments'])
            ->whereIn('status', ['validated', 'awaiting_funding', 'funded', 'in_progress', 'completed'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
    ]);
});
Route::get('/terms', function () {
    return view('terms');
})->name('terms');

// LNbits Webhook Route
Route::post('/lnbits/webhook', function (\Illuminate\Http\Request $request) {
    $paymentHash = $request->input('payment_hash');
    if (!$paymentHash) return response()->json(['error' => 'Missing payment_hash'], 400);

    // Webhook logic
    $repayment = App\Models\Repayment::where('payment_hash', $paymentHash)->first();
    if ($repayment) {
        if ($repayment->status === 'paid') return response()->json(['status' => 'already_paid']);
        
        try {
            $lnbits = new \App\Services\LNbitsService();
            $isPaid = $lnbits->checkPaymentStatus($paymentHash);
            
            if ($isPaid) {
                $repayment->update(['status' => 'paid']);
                
                // If all 3 are paid, mark project as completed
                if ($repayment->project->repayments()->where('status', 'pending')->count() == 0) {
                    $repayment->project->update(['status' => 'completed']);
                }

                // Distribution of funds to investors
                $project = $repayment->project;
                $totalInvested = $project->investments()->where('status', 'paid')->sum('amount_fcfa');
                
                if ($totalInvested > 0) {
                    foreach ($project->investments()->where('status', 'paid')->get() as $investment) {
                        $share = $investment->amount_fcfa / $totalInvested;
                        $investorShareFcfa = intval($repayment->amount_fcfa * $share);
                        $investorShareSats = intval($investorShareFcfa * 6); // Mock rate
                        
                        // Add to investor's virtual balance
                        $user = $investment->user;
                        $user->increment('balance_sats', $investorShareSats);
                        
                        // Envoi de l'email
                        try {
                            \Illuminate\Support\Facades\Mail::raw("Bonjour, votre part du remboursement (Tranche de {$repayment->amount_fcfa} FCFA) a été créditée : {$investorShareSats} SATS (env. {$investorShareFcfa} FCFA) ont été ajoutés à votre solde AgroTrace.", function ($message) use ($user, $project) {
                                $message->to($user->email)->subject("💰 Retour sur Investissement - Projet " . $project->title);
                            });
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error("Mail sending failed: " . $e->getMessage());
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("LNbits webhook repayment error: " . $e->getMessage());
        }
        return response()->json(['status' => 'processed']);
    }

    $investment = Investment::where('payment_hash', $paymentHash)->first();
    if (!$investment) return response()->json(['error' => 'Investment not found'], 404);

    if ($investment->status === 'paid') return response()->json(['status' => 'already_paid']);

    try {
        $lnbits = new \App\Services\LNbitsService();
        $isPaid = $lnbits->checkPaymentStatus($paymentHash);
        
        if ($isPaid) {
            $investment->update(['status' => 'paid']);
            \Illuminate\Support\Facades\Mail::to($investment->user->email)->queue(new \App\Mail\InvestmentSuccessMail($investment));
            
            $project = $investment->project;
            $totalInvested = $project->investments()->where('status', 'paid')->sum('amount_fcfa');
            if ($totalInvested >= $project->target_amount_fcfa) {
                $project->update(['status' => 'funded']);
            }
            
            try {
                \Illuminate\Support\Facades\Mail::to($investment->user->email)->send(new \App\Mail\InvestmentConfirmed($investment));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Mail sending failed: " . $e->getMessage());
            }
        }
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error("LNbits webhook error: " . $e->getMessage());
    }

    return response()->json(['status' => 'processed']);
});

// Dashboard Route (Dynamic)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        $user = Auth::user();
        if ($user->role === 'admin') {
            return view('admin.dashboard', [
                'projects' => Project::all(), 
                'milestones' => Milestone::with('project')->get(),
                'totalInvested' => Investment::sum('amount_fcfa'),
                'totalFeesSats' => Investment::sum('fee_sats')
            ]);
        } elseif ($user->role === 'project_owner') {
            return view('owner.dashboard', ['projects' => $user->projects()->with('milestones')->get()]);
        } else {
            // Investor
            return view('dashboard', [
                'investments' => $user->investments()->with('project')->latest()->paginate(5)
            ]);
        }
    })->name('dashboard');

    Route::get('/invoices', function () {
        if (Auth::user()->role === 'project_owner') abort(403);
        $investments = Auth::user()->investments()->with('project')->where('status', 'paid')->orderByDesc('created_at')->get();
        return view('investor.invoices', ['investments' => $investments]);
    })->name('invoices.index');

    Route::post('/invest/{project_id}', function ($project_id) {
        $amountFcfa = (int) request('amount_fcfa');
        
        $project = Project::findOrFail($project_id);
        
        // Règles d'investissement strictes
        $remaining = $project->remaining_amount;
        $minInvestment = max(1, intval($project->target_amount_fcfa / 4));
        
        // Si le montant restant est inférieur au minimum théorique (25%), 
        // alors le minimum autorisé devient le montant restant (pour clôturer le projet).
        if ($remaining < $minInvestment) {
            $minInvestment = $remaining;
        }

        if ($amountFcfa < $minInvestment) {
            return back()->with('error', 'Le montant minimum pour investir dans ce projet est de ' . number_format($minInvestment) . ' FCFA.');
        }

        if ($amountFcfa > $remaining) {
            return back()->with('error', 'Vous ne pouvez pas investir plus que le montant restant (' . number_format($remaining) . ' FCFA).');
        }

        $amountSats = $amountFcfa * 6; // Taux fictif: 1 FCFA ≈ 6 SATS
        $feeSats = $amountSats * 0.02; // Frais de 2%

        try {
            $lnbits = new \App\Services\LNbitsService();
            $invoice = $lnbits->createInvoice($amountSats + $feeSats, "AgroTrace Inv. Projet #{$project_id}", url('/lnbits/webhook'));
            
            $investment = Investment::create([
                'project_id' => $project_id,
                'user_id' => Auth::id(),
                'amount_fcfa' => $amountFcfa,
                'amount_sats' => $amountSats,
                'fee_sats' => $feeSats,
                'payment_request' => $invoice['payment_request'],
                'payment_hash' => $invoice['payment_hash'],
                'status' => 'pending'
            ]);

            return view('invest.pay', ['investment' => $investment]);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur API LNbits: ' . $e->getMessage());
        }
    });

    Route::get('/invest/{hash}/status', function ($hash) {
        $investment = Investment::where('payment_hash', $hash)->firstOrFail();
        
        if ($investment->status === 'paid') {
            return response()->json(['paid' => true]);
        }

        try {
            $lnbits = new \App\Services\LNbitsService();
            $isPaid = $lnbits->checkPaymentStatus($hash);
            
            if ($isPaid) {
                $investment->update(['status' => 'paid']);
                \Illuminate\Support\Facades\Mail::to($investment->user->email)->queue(new \App\Mail\InvestmentSuccessMail($investment));
                
                // Mettre à jour le statut du projet si nécessaire (ex: financé)
                $project = $investment->project;
                $totalInvested = $project->investments()->where('status', 'paid')->sum('amount_fcfa');
                if ($totalInvested >= $project->target_amount_fcfa) {
                    $project->update(['status' => 'funded']);
                }
                
                // Send Email to the investor
                try {
                    \Illuminate\Support\Facades\Mail::to($investment->user->email)->send(new \App\Mail\InvestmentConfirmed($investment));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Mail sending failed: " . $e->getMessage());
                }

                return response()->json(['paid' => true]);
            }
        } catch (\Exception $e) {
            // Fail silently during polling
        }

        return response()->json(['paid' => false]);
    });

    // Geocode a project location with OpenStreetMap Nominatim (cached to limit requests).
    Route::get('/geocode-location', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate(['q' => ['required', 'string', 'min:3', 'max:180']]);
        $query = trim($validated['q']) . ', Bénin';
        $cacheKey = 'agrotrace_geocode_' . sha1(mb_strtolower($query));
        $result = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addDays(30), function () use ($query) {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'User-Agent' => 'AgroTraceBTC/1.0 (project location geocoding)',
                'Accept-Language' => 'fr',
            ])->timeout(8)->get('https://nominatim.openstreetmap.org/search', [
                'q' => $query,
                'format' => 'jsonv2',
                'limit' => 1,
                'countrycodes' => 'bj',
                'addressdetails' => 1,
            ]);
            if (!$response->successful() || empty($response->json()[0])) return null;
            $place = $response->json()[0];
            return [
                'latitude' => (float) $place['lat'],
                'longitude' => (float) $place['lon'],
                'display_name' => $place['display_name'] ?? $query,
            ];
        });
        if (!$result) return response()->json(['message' => 'Localité introuvable. Précisez le village, la commune ou le département.'], 404);
        return response()->json($result);
    })->middleware('throttle:10,1');

    // Project Owner Actions
    Route::post('/projects', function () {
        if (Auth::user()->role !== 'project_owner') abort(403);

        $validated = request()->validate([
            'title' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:180'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'budget_fcfa' => ['required', 'numeric', 'min:1'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'description' => ['required', 'string'],
            'milestones' => ['required', 'array', 'min:1'],
        ]);

        $latitude = request('latitude');
        $longitude = request('longitude');
        if (($latitude === null || $latitude === '') !== ($longitude === null || $longitude === '')) {
            return back()->withErrors(['location' => 'Les coordonnées GPS sont incomplètes. Recherchez la localité ou choisissez un point sur la carte.'])->withInput();
        }

        if ($latitude === null || $latitude === '') {
            $query = trim(request('location')) . ', Bénin';
            $place = \Illuminate\Support\Facades\Cache::remember('agrotrace_geocode_' . sha1(mb_strtolower($query)), now()->addDays(30), function () use ($query) {
                $geoResponse = \Illuminate\Support\Facades\Http::withHeaders([
                    'User-Agent' => 'AgroTraceBTC/1.0 (project location geocoding)',
                    'Accept-Language' => 'fr',
                ])->timeout(8)->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query, 'format' => 'jsonv2', 'limit' => 1, 'countrycodes' => 'bj',
                ]);
                return $geoResponse->successful() ? ($geoResponse->json()[0] ?? null) : null;
            });
            if (!$place) {
                return back()->withErrors(['location' => 'Localité non reconnue. Précisez le village ou la commune.'])->withInput();
            }
            $latitude = (float) $place['lat'];
            $longitude = (float) $place['lon'];
        }

        $coordinates = ['latitude' => (float) $latitude, 'longitude' => (float) $longitude];
        
        $regCertPath = request()->file('registration_certificate') ? request()->file('registration_certificate')->store('documents', 'public') : null;
        $sigIdPath = request()->file('signatories_id') ? request()->file('signatories_id')->store('documents', 'public') : null;
        $bankProofPath = request()->file('bank_account_proof') ? request()->file('bank_account_proof')->store('documents', 'public') : null;

        $project = Project::create([
            'user_id' => Auth::id(),
            'title' => request('title'),
            'description' => request('description'),
            'region' => request('location'),
            'latitude' => $coordinates['latitude'],
            'longitude' => $coordinates['longitude'],
            'target_amount_fcfa' => request('budget_fcfa'),
            'registration_certificate' => $regCertPath,
            'signatories_id' => $sigIdPath,
            'bank_account_proof' => $bankProofPath,
            'status' => 'submitted',
            'start_date' => request('start_date'),
            'end_date' => request('end_date')
        ]);

        $milestones = request('milestones', []);
        
        foreach ($milestones as $m) {
            if (!empty($m['title'])) {
                Milestone::create([
                    'project_id' => $project->id,
                    'title' => $m['title'],
                    'description' => $m['desc'] ?? '',
                    'amount_fcfa' => $m['amount'] ?? 0,
                    'status' => 'pending'
                ]);
            }
        }

        return redirect('/dashboard')->with('success', 'Projet créé avec succès et soumis pour étude.');
    });

    Route::post('/milestones/{id}/proof', function ($id) {
        if (Auth::user()->role !== 'project_owner') abort(403);
        
        $milestone = Milestone::findOrFail($id);
        if ($milestone->project->user_id !== Auth::id()) abort(403);

        $paths = [];
        if (request()->hasFile('proof_images')) {
            foreach (request()->file('proof_images') as $file) {
                $paths[] = $file->store('proofs', 'public');
            }
        }

        // Backward compatibility with single upload
        $singlePath = request()->file('proof_image') ? request()->file('proof_image')->store('proofs', 'public') : null;

        if ($singlePath && count($paths) === 0) {
            $paths[] = $singlePath;
        }

        $milestone->update([
            'status' => 'submitted',
            'proof_image' => $singlePath ?? ($paths[0] ?? null), // keep first image in old column for fallback
            'proof_images' => count($paths) > 0 ? $paths : null,
            'proof_notes' => request('proof_notes'),
        ]);

        return redirect('/dashboard')->with('success', 'Preuve soumise avec succès.');
    });

    Route::get('/projects/{id}/contract', function ($id) {
        $project = Project::findOrFail($id);
        
        $hasInvested = false;
        if (Auth::user()->role === 'investor') {
            $hasInvested = Auth::user()->investments()->where('project_id', $project->id)->exists();
        }

        if (Auth::user()->role !== 'admin' && $project->user_id !== Auth::id() && !$hasInvested) {
            abort(403, 'Accès non autorisé au contrat.');
        }
        return view('owner.contract', ['project' => $project]);
    })->name('projects.contract');

    Route::get('/investments/{id}/contract', function ($id) {
        $investment = Investment::with(['project.user', 'user'])->findOrFail($id);
        
        if (Auth::user()->role !== 'admin' && $investment->user_id !== Auth::id()) {
            abort(403, 'Accès non autorisé à ce contrat nominatif.');
        }
        
        return view('investor.contract', ['investment' => $investment]);
    })->name('investments.contract');

    Route::get('/investments/{id}/invoice', function ($id) {
        $investment = Investment::with(['project.user', 'user'])->findOrFail($id);
        
        if (Auth::user()->role !== 'admin' && $investment->user_id !== Auth::id()) {
            abort(403, 'Accès non autorisé à cette facture.');
        }
        
        if ($investment->status !== 'paid') {
            abort(403, 'Facture non disponible car le paiement n\'est pas confirmé.');
        }
        
        return view('investor.invoice', ['investment' => $investment]);
    })->name('investments.invoice');

    Route::post('/projects/{id}/generate-tranches', function ($id) {
        $project = Project::findOrFail($id);
        if ($project->user_id !== Auth::id()) abort(403);
        
        $totalToRepay = $project->target_amount_fcfa * 1.08;
        $trancheAmount = intval($totalToRepay / 3);
        
        for ($i = 1; $i <= 3; $i++) {
            App\Models\Repayment::create([
                'project_id' => $project->id,
                'amount_fcfa' => $trancheAmount,
                'amount_sats' => 0, // Mock for hackathon
                'due_date' => now()->addMonths($i),
                'status' => 'pending'
            ]);
        }
        
        return back()->with('status', 'Échéancier généré avec succès !');
    });

    Route::post('/repayments/{id}/pay', function (\Illuminate\Http\Request $request, $id) {
        $repayment = App\Models\Repayment::findOrFail($id);
        if ($repayment->project->user_id !== Auth::id()) abort(403);
        
        $amountFcfa = $repayment->amount_fcfa;
        $amountSats = $amountFcfa * 6; // Taux fictif
        
        try {
            $lnbits = new \App\Services\LNbitsService();
            // create invoice for cooperative to pay
            $invoice = $lnbits->createInvoice($amountSats, "AgroTrace Remb. Projet #" . $repayment->project_id, url('/lnbits/webhook'));
            
            $repayment->update([
                'amount_sats' => $amountSats,
                'payment_request' => $invoice['payment_request'],
                'payment_hash' => $invoice['payment_hash'],
            ]);

            return view('owner.pay-repayment', ['repayment' => $repayment]);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur de paiement Lightning : ' . $e->getMessage());
        }
    });

    Route::get('/repayments/{hash}/status', function ($hash) {
        $repayment = App\Models\Repayment::where('payment_hash', $hash)->firstOrFail();
        if ($repayment->status === 'paid') return response()->json(['paid' => true]);
        
        try {
            $lnbits = new \App\Services\LNbitsService();
            if ($lnbits->checkPaymentStatus($hash)) {
                $repayment->update(['status' => 'paid']);
                // distribution handles via webhook for safety
                return response()->json(['paid' => true]);
            }
        } catch (\Exception $e) {}
        return response()->json(['paid' => false]);
    });

    // Investor Withdraw
    Route::post('/withdraw', function () {
        $user = Auth::user();
        if ($user->balance_sats < 100) {
            return back()->with('error', 'Solde insuffisant pour retirer (min. 100 SATS).');
        }

        try {
            $lnbits = new \App\Services\LNbitsService();
            $withdrawData = $lnbits->createWithdrawLink('Retrait AgroTrace', $user->balance_sats, $user->balance_sats);
            
            // To be robust, we'd deduct after LNURLw triggers webhook, but LNbits LNURLw handles deducting its own funds. 
            // In a real system, we must track the LNURLw uses to decrement user's balance accurately via webhook.
            // For now, let's decrement immediately and assume success for UX/hackathon.
            $withdrawn = $user->balance_sats;
            $user->update(['balance_sats' => 0]);
            
            return view('investor.withdraw', [
                'lnurl' => $withdrawData['lnurl'], 
                'amount_sats' => $withdrawn
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur LNURL : ' . $e->getMessage());
        }
    });

    // Admin Actions
    Route::post('/admin/projects/{id}/validate', function ($id) {
        if (Auth::user()->role !== 'admin') abort(403);
        
        $project = \App\Models\Project::findOrFail($id);
        $project->update(['status' => 'validated']);
        
        return back()->with('success', 'Le projet a été approuvé avec succès !');
    });

    Route::post('/projects/{id}/status', function ($id) {
        if (Auth::user()->role !== 'admin') abort(403);
        
        $project = Project::findOrFail($id);
        $status = request('status');
        $validStatuses = ['submitted', 'under_review', 'validated', 'awaiting_funding', 'funded', 'in_progress', 'completed'];
        
        if (in_array($status, $validStatuses)) {
            $project->update(['status' => $status]);
        }

        return redirect('/dashboard')->with('success', 'Statut du projet mis à jour avec succès.');
    });

    Route::post('/milestones/{id}/validate', function ($id) {
        if (Auth::user()->role !== 'admin') abort(403);
        
        $milestone = Milestone::findOrFail($id);
        
        // Simuler un enregistrement immuable sur la blockchain
        $txId = hash('sha256', $milestone->id . $milestone->status . time() . 'AgroTraceBTC');

        $milestone->update([
            'status' => 'validated',
            'blockchain_tx_id' => $txId
        ]);

        return redirect('/dashboard')->with('success', 'Jalon validé et ancré sur la blockchain avec succès ! (TX ID: ' . substr($txId, 0, 16) . '...)');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('/admin/withdraw', function (Illuminate\Http\Request $request) {
    if (Auth::user()->role !== 'admin') abort(403);
    
    // We use the admin's balance_sats to track how much they have ALREADY withdrawn
    $totalFeesSats = \App\Models\Investment::sum('fee_sats'); // Assuming all fees are earned
    $admin = Auth::user();
    $alreadyWithdrawn = $admin->balance_sats;
    
    $availableToWithdraw = $totalFeesSats - $alreadyWithdrawn;
    
    if ($availableToWithdraw < 100) {
        return back()->with('error', 'Frais insuffisants pour retirer (min. 100 SATS).');
    }
    
    try {
        $lnbits = new \App\Services\LNbitsService();
        $withdrawData = $lnbits->createWithdrawLink('Retrait Commissions AgroTrace', $availableToWithdraw, $availableToWithdraw);
        
        $admin->update(['balance_sats' => $alreadyWithdrawn + $availableToWithdraw]);
        
        return view('investor.withdraw', [
            'lnurl' => $withdrawData['lnurl'], 
            'amount_sats' => $availableToWithdraw
        ]);
    } catch (\Exception $e) {
        return back()->with('error', 'Erreur LNURL: ' . $e->getMessage());
    }
});

require __DIR__.'/auth.php';
