@extends('layouts.app')
@section('title', 'Carte d\'Impact Interactive')
@section('content')

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<div x-data="impactMap()" class="relative h-[calc(100vh-4rem)] bg-slate-100 flex flex-col md:flex-row overflow-hidden">
    
    <!-- Sidebar / Stats Panel -->
    <div class="w-full md:w-96 bg-white shadow-2xl z-[1000] flex flex-col h-1/2 md:h-full overflow-y-auto shrink-0 transition-all duration-300">
        <div class="p-6 bg-[#063b27] text-white">
            <h1 class="text-2xl font-black mb-2"><i class="fa-solid fa-earth-africa text-green-400 mr-2"></i> Impact AgroTrace-BTC</h1>
            <p class="text-green-50 text-sm opacity-90">Visualisez l'impact géographique et financier des investissements sur le territoire béninois.</p>
        </div>
        
        <div class="p-6 flex-1">
            <!-- Global Stats -->
            <div class="grid grid-cols-2 gap-4 mb-8">
                <div class="bg-blue-50 rounded-xl p-4 border border-blue-100 transition-all hover:shadow-md">
                    <p class="text-[10px] font-bold text-blue-500 uppercase tracking-widest mb-1">Projets Filtrés</p>
                    <p class="text-2xl font-black text-slate-900" x-text="filteredProjects.length"></p>
                </div>
                <div class="bg-green-50 rounded-xl p-4 border border-green-100 transition-all hover:shadow-md">
                    <p class="text-[10px] font-bold text-green-600 uppercase tracking-widest mb-1">Familles Impactées</p>
                    <p class="text-2xl font-black text-slate-900" x-text="(filteredProjects.length * 25) + '+'"></p>
                </div>
                <div class="col-span-2 bg-orange-50 rounded-xl p-4 border border-orange-100 transition-all hover:shadow-md">
                    <p class="text-[10px] font-bold text-orange-600 uppercase tracking-widest mb-1">Volume Financé (Sélection)</p>
                    <p class="text-3xl font-black text-slate-900"><span x-text="new Intl.NumberFormat('fr-FR').format(totalInvested)"></span> <span class="text-sm text-slate-500 font-medium">FCFA</span></p>
                </div>
            </div>

            <!-- Filters -->
            <div class="mb-6">
                <label class="block text-sm font-bold text-slate-800 mb-2">Filtrer par Région</label>
                <div class="relative">
                    <select x-model="selectedRegion" @change="filterMap()" class="block appearance-none w-full bg-slate-50 border border-slate-200 text-slate-700 py-3 px-4 pr-8 rounded-xl leading-tight focus:outline-none focus:bg-white focus:border-[#063b27] focus:ring-1 focus:ring-[#063b27] transition-all cursor-pointer font-medium shadow-sm">
                        <option value="all">Toutes les régions (Bénin)</option>
                        <template x-for="region in regions" :key="region">
                            <option :value="region" x-text="region"></option>
                        </template>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                </div>
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-bold text-slate-800 mb-2">Statut du Projet</label>
                <div class="flex flex-wrap gap-2">
                    <button @click="selectedStatus = 'all'; filterMap()" :class="selectedStatus == 'all' ? 'bg-slate-800 text-white shadow-md' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'" class="px-3 py-2 rounded-lg text-xs font-bold transition-all">Tous</button>
                    <button @click="selectedStatus = 'funded'; filterMap()" :class="selectedStatus == 'funded' ? 'bg-green-600 text-white shadow-md' : 'bg-slate-100 text-slate-500 hover:bg-green-50 hover:text-green-600'" class="px-3 py-2 rounded-lg text-xs font-bold transition-all">Financés</button>
                    <button @click="selectedStatus = 'in_progress'; filterMap()" :class="selectedStatus == 'in_progress' ? 'bg-blue-600 text-white shadow-md' : 'bg-slate-100 text-slate-500 hover:bg-blue-50 hover:text-blue-600'" class="px-3 py-2 rounded-lg text-xs font-bold transition-all">En cours</button>
                    <button @click="selectedStatus = 'completed'; filterMap()" :class="selectedStatus == 'completed' ? 'bg-purple-600 text-white shadow-md' : 'bg-slate-100 text-slate-500 hover:bg-purple-50 hover:text-purple-600'" class="px-3 py-2 rounded-lg text-xs font-bold transition-all">Terminés</button>
                </div>
            </div>

            <div class="bg-blue-50 p-4 rounded-xl text-xs text-blue-700 mt-auto border border-blue-100 flex gap-3 items-start">
                <i class="fa-solid fa-circle-info mt-0.5"></i> 
                <p>Les cercles sur la carte représentent la zone d'impact géographique estimée de chaque projet. Le rayon est proportionnel au budget alloué.</p>
            </div>
        </div>
    </div>

    <!-- Map Container -->
    <div id="map" class="flex-1 h-1/2 md:h-full z-0"></div>
</div>

<!-- Inline data for Alpine/JS -->
<script>
    const projectsData = @json($projects);
</script>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('impactMap', () => ({
            map: null,
            markersLayer: null,
            projects: projectsData,
            filteredProjects: [],
            regions: [],
            selectedRegion: 'all',
            selectedStatus: 'all',
            totalInvested: 0,
            
            init() {
                // Initialize Leaflet map centered on Benin
                this.map = L.map('map', {
                    zoomControl: false 
                }).setView([9.3077, 2.3158], 7);
                
                L.control.zoom({
                    position: 'bottomright'
                }).addTo(this.map);

                // Add OpenStreetMap tiles (CartoDB Positron variant for cleaner look if possible, but OSM standard is fine)
               L.tileLayer('https://basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png?key={{ config('services.carto.key') }}', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
}).addTo(this.map);


                this.markersLayer = L.layerGroup().addTo(this.map);

                // Hardcode Benin departments
                this.regions = ['Alibori', 'Atacora', 'Atlantique', 'Borgou', 'Collines', 'Couffo', 'Donga', 'Littoral', 'Mono', 'Ouémé', 'Plateau', 'Zou'];

                this.filteredProjects = this.projects;
                this.calculateStats();
                
                // Small delay to ensure map container has its final size
                setTimeout(() => {
                    this.map.invalidateSize();
                    this.renderMarkers();
                }, 100);
            },

            filterMap() {
                this.filteredProjects = this.projects.filter(p => {
                    const matchRegion = this.selectedRegion === 'all' || (p.region || '').toLocaleLowerCase('fr').includes(this.selectedRegion.toLocaleLowerCase('fr'));
                    const matchStatus = this.selectedStatus === 'all' || p.status === this.selectedStatus;
                    return matchRegion && matchStatus;
                });
                this.calculateStats();
                this.renderMarkers();
            },

            calculateStats() {
                this.totalInvested = this.filteredProjects.reduce((sum, p) => {
                    // Total investment 'paid'
                    let invested = 0;
                    if (p.investments && p.investments.length > 0) {
                        invested = p.investments.filter(i => i.status === 'paid').reduce((acc, curr) => acc + parseFloat(curr.amount_fcfa), 0);
                    }
                    // Fallback to target amount if no real investments yet but status is funded (just for demo purposes if needed, but we stick to real investments if available)
                    if (invested === 0 && (p.status === 'funded' || p.status === 'in_progress' || p.status === 'completed')) {
                        invested = parseFloat(p.target_amount_fcfa);
                    }
                    return sum + invested;
                }, 0);
            },

            renderMarkers() {
                this.markersLayer.clearLayers();
                
                if (this.filteredProjects.length === 0) return;

                const bounds = [];

                this.filteredProjects.forEach(p => {
                    if (!p.latitude || !p.longitude) return;
                    
                    const lat = parseFloat(p.latitude);
                    const lng = parseFloat(p.longitude);
                    bounds.push([lat, lng]);

                    // Color based on status
                    let color = '#16a34a'; // green (funded)
                    let statusLabel = 'Financé';
                    if (p.status === 'in_progress') { color = '#2563eb'; statusLabel = 'En cours de réalisation'; } // blue
                    if (p.status === 'completed') { color = '#9333ea'; statusLabel = 'Récolté & Remboursé'; } // purple
                    if (p.status === 'validated') { color = '#64748b'; statusLabel = 'Validé'; } // slate-500
                    if (p.status === 'awaiting_funding') { color = '#eab308'; statusLabel = 'En attente de fonds'; } // yellow-500

                    // 1. Impact Circle (Radius based on budget, bounded)
                    let radius = Math.min(Math.max(p.target_amount_fcfa / 100, 5000), 40000); // 5km to 40km
                    L.circle([lat, lng], {
                        color: color,
                        fillColor: color,
                        fillOpacity: 0.15,
                        radius: radius,
                        weight: 2,
                        dashArray: '5, 5'
                    }).addTo(this.markersLayer);

                    // 2. Custom Marker HTML
                    const customIcon = L.divIcon({
                        className: 'custom-div-icon',
                        html: `<div style="background-color: ${color}; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 4px 6px rgba(0,0,0,0.3);"></div>`,
                        iconSize: [20, 20],
                        iconAnchor: [10, 10]
                    });

                    // 3. Marker & Popup
                    const marker = L.marker([lat, lng], { icon: customIcon }).addTo(this.markersLayer);
                    
                    const popupContent = `
                        <div class="font-sans min-w-[220px]">
                            <div class="border-b border-slate-100 pb-2 mb-2">
                                <div class="flex justify-between items-start">
                                    <h3 class="font-black text-slate-800 text-sm leading-tight pr-2">${p.title}</h3>
                                    <span class="bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded text-[10px] font-black tracking-widest shrink-0">${p.formatted_id}</span>
                                </div>
                                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider mt-1"><i class="fa-solid fa-location-dot text-slate-300"></i> ${p.region}</p>
                            </div>
                            <div class="bg-blue-50 border border-blue-100 rounded-lg p-2 mb-3">
                                <p class="text-[10px] text-blue-500 font-black uppercase tracking-widest mb-0.5">Porté par</p>
                                <p class="text-xs text-blue-900 font-bold"><i class="fa-solid fa-users text-blue-400 mr-1"></i> ${p.user ? p.user.name : 'Coopérative non définie'}</p>
                            </div>
                            
                            ${p.start_date && p.end_date ? `
                            <p class="text-[10px] text-slate-500 font-bold mb-3"><i class="fa-solid fa-calendar-days text-slate-400 mr-1"></i> ${new Date(p.start_date).toLocaleDateString('fr-FR')} - ${new Date(p.end_date).toLocaleDateString('fr-FR')}</p>
                            ` : ''}
                            
                            <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-100 mb-3">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-[10px] text-slate-500 font-bold uppercase">Financement</span>
                                    <span class="text-xs font-black text-slate-800">${new Intl.NumberFormat('fr-FR').format(p.target_amount_fcfa)} FCFA</span>
                                </div>
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider w-16">Statut</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black text-white" style="background-color: ${color};">${statusLabel}</span>
                                </div>
                            </div>
                            <a href="/projects" class="block w-full text-center text-xs font-bold text-white bg-[#063b27] hover:bg-[#0a4b33] py-2 rounded-lg transition-colors">Découvrir ce projet</a>
                        </div>
                    `;
                    marker.bindPopup(popupContent, {
                        closeButton: true,
                        className: 'custom-impact-popup'
                    });
                    
                    // Add permanent tooltip for status on the map
                    marker.bindTooltip(statusLabel, {
                        permanent: true, 
                        direction: 'right', 
                        className: 'custom-status-tooltip',
                        offset: [10, 0]
                    });
                });

                // Adjust bounds if filtering by region
                if (bounds.length > 0 && this.selectedRegion !== 'all') {
                    this.map.fitBounds(bounds, { padding: [50, 50], maxZoom: 9 });
                } else if (this.selectedRegion === 'all') {
                    if (bounds.length > 0) {
                        this.map.fitBounds(bounds, { padding: [20, 20], maxZoom: 8 });
                    } else {
                        this.map.setView([9.3077, 2.3158], 7);
                    }
                }
            }
        }));
    });
</script>

<style>
    /* Custom Leaflet styling */
    .custom-impact-popup .leaflet-popup-content-wrapper {
        border-radius: 1rem;
        box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        padding: 0;
        border: 1px solid #f1f5f9;
    }
    .custom-impact-popup .leaflet-popup-content {
        margin: 16px;
    }
    .custom-impact-popup .leaflet-popup-tip-container {
        display: none; /* Hide the tip for a cleaner floating box look */
    }
    .custom-impact-popup .leaflet-popup-close-button {
        color: #cbd5e1 !important;
        padding: 8px 8px 0 0 !important;
    }
    .custom-impact-popup .leaflet-popup-close-button:hover {
        color: #64748b !important;
    }
    .leaflet-container {
        font-family: inherit;
        background-color: #f8fafc;
    }
    .leaflet-control-zoom a {
        color: #334155 !important;
        border-radius: 0.5rem !important;
        border: 1px solid #e2e8f0 !important;
    }
    .leaflet-control-zoom {
        border: none !important;
        box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1) !important;
        margin-right: 20px !important;
        margin-bottom: 20px !important;
    }
    .custom-status-tooltip {
        background-color: white;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        font-weight: 800;
        font-size: 10px;
        text-transform: uppercase;
        color: #334155;
        padding: 4px 8px;
    }
</style>
@endsection
