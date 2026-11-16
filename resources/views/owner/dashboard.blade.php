@extends('layouts.app')
@section('title', 'Cooperative Dashboard')
@section('content')

<!-- Header -->
<div class="bg-white border-b border-slate-200 px-8 py-10">
    <div class="flex justify-between items-center">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-[10px] font-black uppercase tracking-widest mb-3">
                <i class="fa-solid fa-tractor"></i> Coopérative Agricole
            </div>
            <h1 class="text-3xl font-black tracking-tight text-slate-900">Espace Coopérative</h1>
            <p class="text-slate-500 mt-2 font-medium">Gérez vos projets agricoles et soumettez des preuves d'avancement.</p>
        </div>
        <button @click="$dispatch('open-create-modal')" class="hidden sm:flex bg-[#063b27] hover:bg-[#0a4b33] text-white font-bold py-3 px-6 rounded-xl transition shadow-md items-center gap-2">
            <i class="fa-solid fa-plus"></i> Nouveau Projet
        </button>
    </div>
</div>

<div class="p-8" x-data="{ createModalOpen: false, proofModalOpen: false, currentMilestoneId: null }">
    <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-3">
        <i class="fa-solid fa-seedling text-slate-400"></i> Mes Projets Actifs
    </h2>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
        @foreach($projects as $project)
        <div class="bg-white rounded-3xl shadow-xl border border-slate-200 overflow-hidden flex flex-col hover:-translate-y-2 hover:shadow-2xl hover:border-orange-300 transition-all duration-300 group relative">
            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-orange-400/5 to-transparent rounded-bl-full pointer-events-none"></div>
            
            <!-- Project Header -->
            <div class="p-6 bg-gradient-to-br from-[#063b27] to-slate-900 relative flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 overflow-hidden">
                <div class="absolute -right-5 -bottom-5 opacity-20 group-hover:scale-110 transition-transform duration-700">
                    <i class="fa-solid fa-tractor text-8xl text-white"></i>
                </div>
                <div class="relative z-10">
                    <h3 class="text-xl font-black text-white mb-2">{{ $project->title }}</h3>
                    <div class="flex flex-wrap gap-2">
                        @if($project->status == 'submitted')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-white/20 backdrop-blur-sm border border-white/10 text-white rounded-full text-[10px] font-black uppercase shadow-sm"><i class="fa-solid fa-file-arrow-up"></i> Soumis</span>
                        @elseif($project->status == 'under_review')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-orange-500/20 backdrop-blur-sm border border-orange-500/30 text-orange-100 rounded-full text-[10px] font-black uppercase shadow-sm"><i class="fa-solid fa-magnifying-glass"></i> En étude</span>
                        @elseif($project->status == 'validated')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-500/20 backdrop-blur-sm border border-blue-500/30 text-blue-100 rounded-full text-[10px] font-black uppercase shadow-sm"><i class="fa-solid fa-check"></i> Validé</span>
                        @elseif($project->status == 'awaiting_funding')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-yellow-500/20 backdrop-blur-sm border border-yellow-500/30 text-yellow-100 rounded-full text-[10px] font-black uppercase shadow-sm"><i class="fa-solid fa-hourglass-half text-yellow-300"></i> En attente de fonds</span>
                        @elseif($project->status == 'funded' || $project->status == 'in_progress')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-green-500/20 backdrop-blur-sm border border-green-500/30 text-green-100 rounded-full text-[10px] font-black uppercase shadow-sm"><i class="fa-solid fa-seedling text-green-300"></i> {{ $project->status == 'funded' ? 'Financé' : 'En cours' }}</span>
                        @elseif($project->status == 'completed')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 text-white rounded-full text-[10px] font-black uppercase shadow-sm"><i class="fa-solid fa-check-double text-green-400"></i> Terminé</span>
                        @endif
                    </div>
                </div>
                <div class="relative z-10 text-left sm:text-right shrink-0 bg-white/10 backdrop-blur-md p-3 rounded-2xl shadow-sm border border-white/10">
                    <p class="text-[9px] font-bold text-white/70 uppercase tracking-widest mb-1">Budget Total</p>
                    <p class="text-xl font-black text-white">{{ number_format($project->target_amount_fcfa) }} <span class="text-[10px] text-white/70 font-bold">FCFA</span></p>
                </div>
            </div>

            <div class="bg-slate-50/50 px-6 py-3 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div class="flex flex-wrap items-center gap-2 sm:gap-4 text-xs font-bold text-slate-500">
                    <div class="flex items-center gap-1.5"><i class="fa-solid fa-calendar text-[#063b27]"></i> {{ \Carbon\Carbon::parse($project->start_date)->translatedFormat('d M Y') }}</div>
                    <div class="hidden sm:flex items-center gap-1.5"><i class="fa-solid fa-arrow-right text-slate-300"></i></div>
                    <div class="flex items-center gap-1.5"><i class="fa-solid fa-flag-checkered text-[#063b27]"></i> {{ \Carbon\Carbon::parse($project->end_date)->translatedFormat('d M Y') }}</div>
                </div>
                <a href="{{ url('/projects/' . $project->id . '/contract') }}" target="_blank" class="text-xs font-bold text-slate-500 hover:text-orange-600 transition inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-file-contract"></i> Consulter le contrat
                </a>
            </div>

            <!-- Échéancier de Remboursement -->
            @if($project->status == 'in_progress')
            <div class="p-6 bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-orange-100 flex items-center justify-center"><i class="fa-solid fa-calendar-check text-orange-600 text-[10px]"></i></span>
                        Échéancier de Remboursement
                    </h4>
                    @if($project->repayments->count() == 0)
                    <form action="{{ url('/projects/'.$project->id.'/generate-tranches') }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-gradient-to-r from-[#063b27] to-[#0a4b33] hover:from-[#084b32] hover:to-[#0c5c3e] text-white font-bold py-1.5 px-4 rounded-xl text-xs transition shadow-md hover:shadow-lg flex items-center gap-2">
                            <i class="fa-solid fa-bolt text-yellow-400"></i> Générer
                        </button>
                    </form>
                    @endif
                </div>

                @if($project->repayments->count() > 0)
                <div class="space-y-4">
                    @php
                        $totalRepayments = $project->repayments->count();
                        $paidRepayments = $project->repayments->where('status', 'paid')->count();
                        $progressPercent = $totalRepayments > 0 ? round(($paidRepayments / $totalRepayments) * 100) : 0;
                    @endphp
                    
                    <!-- Barre de progression -->
                    <div class="mb-4 bg-slate-50 p-3 rounded-2xl border border-slate-100">
                        <div class="flex justify-between text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">
                            <span>Progression des paiements</span>
                            <span class="text-orange-600">{{ $progressPercent }}%</span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden shadow-inner">
                            <div class="bg-gradient-to-r from-orange-400 to-orange-500 h-full rounded-full transition-all duration-1000" style="width: {{ $progressPercent }}%"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        @foreach($project->repayments as $index => $repayment)
                        @php
                            $isLate = $repayment->status == 'pending' && \Carbon\Carbon::parse($repayment->due_date)->isPast();
                        @endphp
                        <div class="bg-white border {{ $isLate ? 'border-red-300 shadow-red-100' : 'border-slate-200 hover:border-slate-300' }} rounded-xl p-3 flex flex-col justify-between transition-all shadow-sm">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h5 class="font-black {{ $isLate ? 'text-red-700' : 'text-slate-800' }} text-xs">Tranche {{ $index + 1 }}</h5>
                                    <p class="text-[10px] {{ $isLate ? 'text-red-500 font-bold' : 'text-slate-400 font-medium' }}"><i class="fa-regular fa-clock"></i> {{ \Carbon\Carbon::parse($repayment->due_date)->format('d/m/Y') }}</p>
                                </div>
                                @if($repayment->status == 'paid')
                                    <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center shadow-inner">
                                        <i class="fa-solid fa-check text-green-500 text-[10px]"></i>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="flex justify-between items-end">
                                <p class="font-black text-slate-900 text-sm">{{ number_format($repayment->amount_fcfa) }} <span class="text-[9px] text-slate-400">FCFA</span></p>
                                @if($repayment->status == 'pending')
                                    <button @click="$dispatch('open-repay-modal', { id: {{ $project->id }}, repayment_id: {{ $repayment->id }}, title: 'Tranche {{ $index + 1 }} - {{ addslashes($project->title) }}', amount: {{ $repayment->amount_fcfa }} })" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-1 px-3 rounded-lg text-xs transition shadow-sm hover:shadow">
                                        Payer
                                    </button>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            @endif

            <!-- Milestones Section -->
            <div class="p-6 border-t border-slate-100 bg-slate-50/30 flex-1">
                <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-blue-100 flex items-center justify-center"><i class="fa-solid fa-list-check text-blue-600 text-[10px]"></i></span>
                    Validation des Jalons
                </h4>

                <div class="space-y-3">
                    @foreach($project->milestones as $milestone)
                    <div class="bg-white border border-slate-200 rounded-xl p-4 flex flex-col sm:flex-row justify-between sm:items-center gap-3 hover:shadow-md hover:border-slate-300 transition-all group/milestone">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                @if($milestone->status == 'pending')
                                <span class="h-2 w-2 rounded-full bg-slate-300 ring-4 ring-slate-100"></span>
                                @elseif($milestone->status == 'submitted')
                                <span class="h-2 w-2 rounded-full bg-orange-500 animate-pulse ring-4 ring-orange-100"></span>
                                @else
                                <span class="h-2 w-2 rounded-full bg-green-500 ring-4 ring-green-100"></span>
                                @endif
                                <h5 class="font-bold text-slate-800 text-sm group-hover/milestone:text-[#063b27] transition-colors">{{ $milestone->title }}</h5>
                            </div>
                            <p class="text-[11px] text-slate-500 line-clamp-1 ml-4">{{ $milestone->description }}</p>
                        </div>

                        <div class="flex-shrink-0 ml-4 sm:ml-0">
                            @if($milestone->status == 'pending')
                            <button @click="currentMilestoneId = {{ $milestone->id }}; proofModalOpen = true" class="bg-white border-2 border-slate-200 text-slate-600 hover:bg-[#063b27] hover:text-white hover:border-[#063b27] font-bold text-xs px-4 py-1.5 rounded-xl transition shadow-sm flex items-center gap-2">
                                <i class="fa-solid fa-camera"></i> Soumettre
                            </button>
                            @elseif($milestone->status == 'submitted')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-orange-50 border border-orange-200 text-orange-700 rounded-full text-xs font-bold shadow-sm">
                                <i class="fa-solid fa-spinner fa-spin text-orange-500"></i> En révision
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-green-50 border border-green-200 text-green-700 rounded-full text-xs font-bold shadow-sm">
                                <i class="fa-solid fa-check-double text-green-500"></i> Validé
                            </span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endforeach

        @if($projects->count() == 0)
        <div class="col-span-full">
            <div class="bg-white border border-slate-100 rounded-[2rem] p-12 text-center shadow-sm">
                <div class="h-16 w-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-400 text-2xl mx-auto mb-4">
                    <i class="fa-solid fa-clipboard-list"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-2">Aucun projet pour le moment</h3>
                <p class="text-slate-500 mb-6 max-w-sm mx-auto">Créez votre premier projet agricole pour commencer à recevoir des investissements.</p>
                <button @click="$dispatch('open-create-modal')" class="bg-[#063b27] text-white font-bold px-8 py-3 rounded-full hover:bg-[#0a4b33] shadow-md transition-all">Créer un Projet</button>
            </div>
        </div>
        @endif
    </div>

    <!-- Create Project Modal -->
    <div x-data="{ createModalOpen: false, milestones: [{title: '', amount: '', desc: ''}] }" @open-create-modal.window="createModalOpen = true" x-show="createModalOpen" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="createModalOpen" x-transition.opacity class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" @click="createModalOpen = false" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="createModalOpen" x-transition.opacity.duration.300ms class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl w-full">
                <div class="bg-white px-6 pt-6 pb-6">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-[#063b27]/10">
                            <i class="fa-solid fa-tractor text-[#063b27] text-xl"></i>
                        </div>
                        <h3 class="text-xl font-black text-slate-900" id="modal-title">Créer un Projet Agricole</h3>
                    </div>
                    <form action="{{ url('/projects') }}" method="POST" id="createProjectForm" enctype="multipart/form-data">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Colonne Gauche : Informations de base -->
                            <div>
                                <div class="mb-4">
                                    <label class="block text-slate-700 text-sm font-bold mb-1" for="title">Titre du Projet</label>
                                    <input class="shadow-sm appearance-none border border-slate-200 rounded-xl w-full py-2 px-4 text-slate-700 leading-tight focus:outline-none focus:border-[#063b27] focus:ring-2 focus:ring-[#063b27]/20 transition-all" id="title" name="title" type="text" required placeholder="ex: Coopérative Maïs Koudougou">
                                </div>
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div class="col-span-2 sm:col-span-1">
                                        <label class="block text-slate-700 text-sm font-bold mb-1" for="location">Localisation</label>
                                        <input class="shadow-sm appearance-none border border-slate-200 rounded-xl w-full py-2 px-4 text-slate-700 leading-tight focus:outline-none focus:border-[#063b27] focus:ring-2 focus:ring-[#063b27]/20 transition-all" id="location" name="location" type="text" required placeholder="ex: Dassa-Zoumè, Collines, Bénin" value="{{ old('location') }}">
                                        <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude') }}">
                                        <input type="hidden" id="longitude" name="longitude" value="{{ old('longitude') }}">
                                        <button type="button" id="locateProjectBtn" class="mt-2 w-full rounded-lg bg-blue-50 text-blue-800 border border-blue-200 py-2 px-3 text-xs font-bold hover:bg-blue-100"> <i class="fa-solid fa-location-crosshairs mr-1"></i> Localiser automatiquement</button>
                                        <p id="locationHelp" class="mt-2 text-[11px] text-slate-500">Saisissez une localité puis localisez-la. Vous pourrez ensuite déplacer le repère jusqu'au terrain exact.</p>
                                        <p id="locationError" class="mt-1 text-[11px] text-red-600" role="alert"></p>
                                        <div id="projectLocationMap" class="mt-3 h-56 rounded-xl border border-slate-200 overflow-hidden bg-slate-100"></div>
                                        <p id="selectedCoordinates" class="mt-1 text-[10px] text-slate-500">Aucun point GPS sélectionné.</p>
                                    </div>
                                    <div class="col-span-2 sm:col-span-1">
                                        <label class="block text-slate-700 text-sm font-bold mb-1" for="budget">Budget (FCFA)</label>
                                        <input class="shadow-sm appearance-none border border-slate-200 rounded-xl w-full py-2 px-4 text-slate-700 leading-tight focus:outline-none focus:border-[#063b27] focus:ring-2 focus:ring-[#063b27]/20 transition-all" id="budget" name="budget_fcfa" type="number" required placeholder="ex: 5000000">
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div class="col-span-2 sm:col-span-1">
                                        <label class="block text-slate-700 text-sm font-bold mb-1" for="start_date">Date de lancement</label>
                                        <input class="shadow-sm appearance-none border border-slate-200 rounded-xl w-full py-2 px-4 text-slate-700 leading-tight focus:outline-none focus:border-[#063b27] focus:ring-2 focus:ring-[#063b27]/20 transition-all" id="start_date" name="start_date" type="date" required>
                                    </div>
                                    <div class="col-span-2 sm:col-span-1">
                                        <label class="block text-slate-700 text-sm font-bold mb-1" for="end_date">Date de fin estimée</label>
                                        <input class="shadow-sm appearance-none border border-slate-200 rounded-xl w-full py-2 px-4 text-slate-700 leading-tight focus:outline-none focus:border-[#063b27] focus:ring-2 focus:ring-[#063b27]/20 transition-all" id="end_date" name="end_date" type="date" required>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-slate-700 text-sm font-bold mb-1" for="description">Description</label>
                                    <textarea class="shadow-sm appearance-none border border-slate-200 rounded-xl w-full py-2 px-4 text-slate-700 leading-tight focus:outline-none focus:border-[#063b27] focus:ring-2 focus:ring-[#063b27]/20 transition-all" id="description" name="description" rows="3" required placeholder="Impact et objectifs..."></textarea>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-slate-700 text-xs font-bold mb-1" for="registration_certificate">1. Attestation d'immatriculation *</label>
                                    <input class="shadow-sm appearance-none border border-slate-200 rounded-xl w-full py-1.5 px-3 text-slate-700 text-xs leading-tight focus:outline-none focus:border-[#063b27] focus:ring-2 focus:ring-[#063b27]/20 transition-all" id="registration_certificate" name="registration_certificate" type="file" accept=".pdf,image/*" required>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-slate-700 text-xs font-bold mb-1" for="signatories_id">2. Pièce d'identité des signataires *</label>
                                    <input class="shadow-sm appearance-none border border-slate-200 rounded-xl w-full py-1.5 px-3 text-slate-700 text-xs leading-tight focus:outline-none focus:border-[#063b27] focus:ring-2 focus:ring-[#063b27]/20 transition-all" id="signatories_id" name="signatories_id" type="file" accept=".pdf,image/*" required>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-slate-700 text-xs font-bold mb-1" for="bank_account_proof">3. Preuve de compte bancaire/mobile *</label>
                                    <input class="shadow-sm appearance-none border border-slate-200 rounded-xl w-full py-1.5 px-3 text-slate-700 text-xs leading-tight focus:outline-none focus:border-[#063b27] focus:ring-2 focus:ring-[#063b27]/20 transition-all" id="bank_account_proof" name="bank_account_proof" type="file" accept=".pdf,image/*" required>
                                </div>
                            </div>
                            
                            <!-- Colonne Droite : Jalons -->
                            <div class="bg-slate-50/50 rounded-xl p-2 border border-slate-100">
                                <div class="flex justify-between items-center mb-3">
                                    <label class="block text-slate-700 text-sm font-bold">Découpage en Jalons</label>
                                    <button type="button" @click="milestones.push({title: '', amount: '', desc: ''})" class="text-xs bg-orange-100 text-orange-600 font-bold py-1 px-3 rounded-lg hover:bg-orange-200 transition"><i class="fa-solid fa-plus"></i> Ajouter</button>
                                </div>

                                <div class="max-h-[350px] overflow-y-auto pr-1">
                                    <template x-for="(milestone, index) in milestones" :key="index">
                                        <div class="bg-white p-3 rounded-xl border border-slate-200 mb-3 relative shadow-sm">
                                            <button type="button" @click="milestones.splice(index, 1)" x-show="milestones.length > 1" class="absolute top-1 right-2 text-red-400 hover:text-red-600"><i class="fa-solid fa-trash"></i></button>

                                            <div class="grid grid-cols-2 gap-2 mb-2 pr-5">
                                                <div>
                                                    <input class="appearance-none border border-slate-200 rounded-lg w-full py-1.5 px-2 text-xs text-slate-700" :name="'milestones['+index+'][title]'" x-model="milestone.title" type="text" required placeholder="Titre (ex: Semis)">
                                                </div>
                                                <div>
                                                    <input class="appearance-none border border-slate-200 rounded-lg w-full py-1.5 px-2 text-xs text-slate-700" :name="'milestones['+index+'][amount]'" x-model="milestone.amount" type="number" required placeholder="Budget FCFA">
                                                </div>
                                            </div>
                                            <input class="appearance-none border border-slate-200 rounded-lg w-full py-1.5 px-2 text-xs text-slate-700" :name="'milestones['+index+'][desc]'" x-model="milestone.desc" type="text" required placeholder="Description de l'étape">
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-slate-100">
                    <button type="button" onclick="document.getElementById('createProjectForm').submit()" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-[#063b27] text-base font-medium text-white hover:bg-[#0a4b33] focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                        Créer le Projet
                    </button>
                    <button type="button" @click="createModalOpen = false" class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Annuler
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit Proof Modal -->
    <div x-show="proofModalOpen" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="proofModalOpen" x-transition.opacity class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" @click="proofModalOpen = false" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="proofModalOpen" x-transition.opacity.duration.300ms class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fa-solid fa-camera text-blue-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-bold text-slate-900" id="modal-title">Soumettre une Preuve de Jalon</h3>
                            <div class="mt-4">
                                <form :action="'{{ url('/milestones') }}/' + currentMilestoneId + '/proof'" method="POST" id="submitProofForm" enctype="multipart/form-data">
                                    @csrf
                                    <div class="flex items-center gap-4 bg-slate-50 border border-slate-200 p-3 rounded-xl">
                                        <label class="flex-shrink-0 cursor-pointer inline-flex items-center gap-2 bg-white border border-slate-300 hover:border-[#063b27] hover:text-[#063b27] text-slate-700 font-bold py-2 px-4 rounded-lg text-xs transition shadow-sm">
                                            <i class="fa-solid fa-paperclip"></i>
                                            <span>Joindre des fichiers</span>
                                            <input type="file" name="proof_images[]" multiple accept="image/*,.pdf" class="hidden" required onchange="document.getElementById('file-count').textContent = this.files.length + ' fichier(s) sélectionné(s)'">
                                        </label>
                                        <div class="text-xs text-slate-500">
                                            <span id="file-count" class="font-bold text-slate-800">Aucun fichier</span>
                                            <p class="text-[9px] mt-0.5 uppercase tracking-wide">PNG, JPG, PDF (Max 5MB)</p>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <label class="block text-slate-700 text-sm font-bold mb-2" for="proof_desc">Détails de la preuve</label>
                                        <textarea class="shadow-sm appearance-none border border-slate-200 rounded-xl w-full py-3 px-4 text-slate-700 leading-tight focus:outline-none focus:border-[#063b27] focus:ring-1 focus:ring-[#063b27]" id="proof_desc" name="proof_notes" rows="2" placeholder="Décrivez brièvement cette preuve..."></textarea>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-slate-100">
                    <button type="button" onclick="document.getElementById('submitProofForm').submit()" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                        Soumettre
                    </button>
                    <button type="button" @click="proofModalOpen = false" class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Annuler
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Repay Project Modal -->
    <div x-data="{ repayModalOpen: false, projectId: null, repaymentId: null, projectTitle: '', repayAmount: 0 }"
        @open-repay-modal.window="repayModalOpen = true; projectId = $event.detail.id; repaymentId = $event.detail.repayment_id; projectTitle = $event.detail.title; repayAmount = $event.detail.amount;"
        x-show="repayModalOpen" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="repayModalOpen" class="fixed inset-0 bg-slate-900 bg-opacity-75" @click="repayModalOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="repayModalOpen" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <div class="bg-white px-6 pt-6 pb-6">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-orange-100">
                            <i class="fa-solid fa-hand-holding-dollar text-orange-500 text-xl"></i>
                        </div>
                        <h3 class="text-xl font-black text-slate-900">Rembourser une Tranche</h3>
                    </div>

                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 mb-5 text-center">
                        <p class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-1" x-text="projectTitle"></p>
                        <p class="text-xs text-slate-400 mb-4">Distribution automatique via Lightning Network</p>

                        <div class="flex justify-between items-center pt-3 border-t border-slate-200 mt-2">
                            <span class="text-sm font-black text-slate-900">Total à reverser :</span>
                            <span class="text-xl font-black text-orange-500" x-text="new Intl.NumberFormat('fr-FR').format(repayAmount) + ' FCFA'"></span>
                        </div>
                    </div>

                    <form :action="'{{ url('/repayments') }}/' + repaymentId + '/pay'" method="POST" id="repayProjectForm">
                        @csrf
                        <div class="mt-4 border-t border-slate-200 pt-4 text-center">
                            <p class="text-slate-600 mb-2">Cliquez ci-dessous pour générer le QR Code de paiement de cette tranche.</p>
                        </div>
                    </form>
                </div>
                <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-slate-100">
                    <button type="button" onclick="document.getElementById('repayProjectForm').submit()" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-orange-500 text-base font-medium text-white hover:bg-orange-600 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                        <i class="fa-solid fa-qrcode mr-2 mt-1"></i> Générer le QR Code
                    </button>
                    <button type="button" @click="repayModalOpen = false" class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Annuler
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"><\/script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('location');
    const latInput = document.getElementById('latitude');
    const lonInput = document.getElementById('longitude');
    const button = document.getElementById('locateProjectBtn');
    const error = document.getElementById('locationError');
    const coordsLabel = document.getElementById('selectedCoordinates');
    const mapElement = document.getElementById('projectLocationMap');
    if (!input || !mapElement || !window.L) return;
    const map = L.map(mapElement).setView([9.3077, 2.3158], 7);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    let marker = null;
    const setPoint = (lat, lon, zoom = 15) => {
        latInput.value = Number(lat).toFixed(8);
        lonInput.value = Number(lon).toFixed(8);
        const point = [Number(lat), Number(lon)];
        if (marker) marker.setLatLng(point);
        else marker = L.marker(point, {draggable: true}).addTo(map);
        marker.dragging.enable();
        marker.off('dragend').on('dragend', event => {
            const p = event.target.getLatLng(); setPoint(p.lat, p.lng, map.getZoom());
        });
        map.setView(point, zoom);
        coordsLabel.textContent = 'GPS : ' + latInput.value + ', ' + lonInput.value + ' — déplacez le repère ou cliquez sur la carte pour préciser le terrain.';
    };
    map.on('click', event => setPoint(event.latlng.lat, event.latlng.lng, map.getZoom()));
    if (latInput.value && lonInput.value) setPoint(latInput.value, lonInput.value, 15);
    button.addEventListener('click', async () => {
        const q = input.value.trim();
        error.textContent = '';
        if (q.length < 3) { error.textContent = 'Saisissez au moins trois caractères.'; return; }
        button.disabled = true; button.textContent = 'Recherche de la localité…';
        try {
            const response = await fetch('/geocode-location?q=' + encodeURIComponent(q), {headers: {'Accept': 'application/json'}});
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Localité introuvable.');
            setPoint(data.latitude, data.longitude, 14);
            document.getElementById('locationHelp').textContent = 'Localité trouvée : ' + data.display_name + '. Le repère peut être déplacé vers le terrain réel.';
            setTimeout(() => map.invalidateSize(), 100);
        } catch (e) { error.textContent = e.message; }
        finally { button.disabled = false; button.innerHTML = '<i class="fa-solid fa-location-crosshairs mr-1"></i> Localiser automatiquement'; }
    });
    document.getElementById('createProjectForm').addEventListener('submit', event => {
        if (!latInput.value || !lonInput.value) {
            event.preventDefault(); error.textContent = 'Localisez le projet et confirmez son point GPS avant de l’enregistrer.';
        }
    });
    document.querySelector('[onclick="document.getElementById(\'createProjectForm\').submit()"]')?.addEventListener('click', () => {
        if (!latInput.value || !lonInput.value) error.textContent = 'Localisez le projet et confirmez son point GPS avant de l’enregistrer.';
    });
});
</script>
@endpush
@endsection