<x-app-layout>
    <x-slot name="header">
        Dashboard
    </x-slot>

    {{-- Inject custom styles for animations & gradients --}}
    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 15px rgba(99,102,241,0.15); }
            50% { box-shadow: 0 0 30px rgba(99,102,241,0.25); }
        }
        @keyframes countUp {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }
        .animate-fade-in { animation: fadeInUp 0.5s ease-out both; }
        .animate-fade-in-1 { animation-delay: 0.05s; }
        .animate-fade-in-2 { animation-delay: 0.1s; }
        .animate-fade-in-3 { animation-delay: 0.15s; }
        .animate-fade-in-4 { animation-delay: 0.2s; }
        .animate-count { animation: countUp 0.6s ease-out both; animation-delay: 0.3s; }
        .card-hover { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(0,0,0,0.08); }
        .glass { background: rgba(255,255,255,0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
        .oee-ring { transition: stroke-dashoffset 1s ease-out; }
        .dept-card { transition: all 0.3s ease; }
        .dept-card:hover { transform: scale(1.02); }
        .gradient-text {
            background: linear-gradient(135deg, #6366f1, #8b5cf6, #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>

    <div class="py-6">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- ══════════════════════════════════════════════════════
                 HERO HEADER
                 ══════════════════════════════════════════════════════ --}}
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-indigo-950 to-purple-950 p-6 sm:p-8 shadow-2xl">
                <div class="absolute inset-0 opacity-10">
                    <svg class="absolute -right-20 -top-20 w-96 h-96 text-indigo-400" fill="currentColor" viewBox="0 0 200 200"><path d="M44.7,-76.4C59.1,-69.3,72.8,-58.8,79.9,-45.1C87.1,-31.3,87.7,-14.3,84.5,1.8C81.3,18,74.3,33.2,64.4,45.5C54.5,57.8,41.6,67.2,27.2,73.6C12.8,79.9,-3,83.2,-18.6,81C-34.2,78.8,-49.5,71.1,-60.3,59.5C-71.1,47.8,-77.3,32.3,-79.9,16.2C-82.5,0.1,-81.4,-16.6,-75.5,-31.1C-69.6,-45.6,-58.9,-57.9,-45.6,-65.6C-32.3,-73.2,-16.1,-76.2,-0.4,-75.5C15.4,-74.8,30.3,-83.5,44.7,-76.4Z" transform="translate(100 100)"/></svg>
                    <svg class="absolute -left-10 -bottom-10 w-72 h-72 text-purple-400" fill="currentColor" viewBox="0 0 200 200"><path d="M39.5,-67.1C52.9,-60.5,66.8,-52.8,74.3,-40.8C81.8,-28.9,82.9,-12.7,80.1,2.2C77.4,17,70.8,30.4,62.1,42.3C53.4,54.2,42.6,64.7,29.6,70.6C16.7,76.5,1.6,77.9,-13.1,75.5C-27.7,73,-41.9,66.8,-53.6,57.3C-65.3,47.9,-74.6,35.2,-78.2,21.1C-81.7,7,-79.6,-8.6,-74.2,-22.4C-68.8,-36.2,-60.1,-48.2,-48.4,-55.7C-36.8,-63.2,-22.1,-66.1,-7.5,-55.6C7.1,-45,26.2,-73.7,39.5,-67.1Z" transform="translate(100 100)"/></svg>
                </div>
                <div class="relative flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-indigo-300 backdrop-blur-sm ring-1 ring-white/10">
                            <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            Live Dashboard
                        </div>
                        <h1 class="mt-3 text-2xl sm:text-3xl font-black text-white tracking-tight">
                            GCI Smart Dashboard
                        </h1>
                        <p class="mt-2 text-sm text-indigo-200/70 max-w-lg">
                            Overview lengkap — Incoming Material, Plant Performance KPI, dan departemen monitoring dalam satu tampilan.
                        </p>
                    </div>

                    {{-- Date Filter --}}
                    <form method="GET" class="flex flex-wrap items-end gap-3">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-indigo-300/70 mb-1">Date From</label>
                            <input type="date" name="date_from" value="{{ $dateFrom }}"
                                class="rounded-xl border-0 bg-white/10 text-white text-sm px-3 py-2 backdrop-blur-sm ring-1 ring-white/20 focus:ring-2 focus:ring-indigo-400 placeholder-white/50">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-indigo-300/70 mb-1">Date To</label>
                            <input type="date" name="date_to" value="{{ $dateTo }}"
                                class="rounded-xl border-0 bg-white/10 text-white text-sm px-3 py-2 backdrop-blur-sm ring-1 ring-white/20 focus:ring-2 focus:ring-indigo-400 placeholder-white/50">
                        </div>
                        <button type="submit"
                            class="rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 hover:from-indigo-400 hover:to-purple-400 transition-all duration-300">
                            <svg class="inline-block w-4 h-4 mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                            Refresh
                        </button>
                    </form>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════════
                 INCOMING MATERIAL SUMMARY CARDS
                 ══════════════════════════════════════════════════════ --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {{-- Total Departures --}}
                <div class="animate-fade-in animate-fade-in-1 card-hover rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 -mt-6 -mr-6 rounded-full bg-gradient-to-br from-blue-500/10 to-blue-500/5"></div>
                    <div class="flex items-center justify-between relative">
                        <div>
                            <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold">Total Departures</div>
                            <div class="mt-2 text-3xl font-black text-slate-900 animate-count">
                                {{ number_format($incomingSummary['total_departures']) }}</div>
                            <div class="text-xs text-slate-400 mt-1">All shipments</div>
                        </div>
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-500/30 rotate-3 hover:rotate-0 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Total Receives --}}
                <div class="animate-fade-in animate-fade-in-2 card-hover rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 -mt-6 -mr-6 rounded-full bg-gradient-to-br from-emerald-500/10 to-emerald-500/5"></div>
                    <div class="flex items-center justify-between relative">
                        <div>
                            <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold">Total Receives</div>
                            <div class="mt-2 text-3xl font-black text-emerald-600 animate-count">
                                {{ number_format($incomingSummary['total_receives']) }}</div>
                            <div class="text-xs text-slate-400 mt-1">Processed items</div>
                        </div>
                        <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-500/30 rotate-3 hover:rotate-0 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Pending Items --}}
                <div class="animate-fade-in animate-fade-in-3 card-hover rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 -mt-6 -mr-6 rounded-full bg-gradient-to-br from-amber-500/10 to-amber-500/5"></div>
                    <div class="flex items-center justify-between relative">
                        <div>
                            <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold">Pending Items</div>
                            <div class="mt-2 text-3xl font-black text-amber-600 animate-count">
                                {{ number_format($incomingSummary['pending_items']) }}</div>
                            <div class="text-xs text-slate-400 mt-1">Need processing</div>
                        </div>
                        <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-500 rounded-2xl flex items-center justify-center shadow-lg shadow-amber-500/30 rotate-3 hover:rotate-0 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Today Receives --}}
                <div class="animate-fade-in animate-fade-in-4 card-hover rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 -mt-6 -mr-6 rounded-full bg-gradient-to-br from-violet-500/10 to-violet-500/5"></div>
                    <div class="flex items-center justify-between relative">
                        <div>
                            <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold">Today Receives</div>
                            <div class="mt-2 text-3xl font-black text-violet-600 animate-count">
                                {{ number_format($incomingSummary['today_receives']) }}</div>
                            <div class="text-xs text-slate-400 mt-1">Processed today</div>
                        </div>
                        <div class="w-12 h-12 bg-gradient-to-br from-violet-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg shadow-violet-500/30 rotate-3 hover:rotate-0 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════════
                 PLANT PERFORMANCE — OEE & PRODUCTION SUMMARY
                 ══════════════════════════════════════════════════════ --}}
            <div class="grid gap-4 lg:grid-cols-3">
                {{-- OEE Gauge --}}
                <div class="card-hover rounded-2xl border border-slate-200/60 bg-white p-6 shadow-sm flex flex-col items-center justify-center">
                    <div class="text-xs uppercase tracking-wider text-slate-400 font-bold mb-2">Overall Equipment Effectiveness</div>
                    <div class="relative w-40 h-40">
                        <svg viewBox="0 0 120 120" class="w-full h-full -rotate-90">
                            <circle cx="60" cy="60" r="52" fill="none" stroke="#e2e8f0" stroke-width="10"/>
                            @php
                                $circumference = 2 * M_PI * 52;
                                $oeeOffset = $circumference - ($oee / 100) * $circumference;
                                $oeeColor = $oee >= 85 ? '#10b981' : ($oee >= 60 ? '#f59e0b' : '#ef4444');
                            @endphp
                            <circle cx="60" cy="60" r="52" fill="none" stroke="{{ $oeeColor }}" stroke-width="10"
                                stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $oeeOffset }}"
                                stroke-linecap="round" class="oee-ring"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-3xl font-black text-slate-900">{{ number_format($oee, 1) }}%</span>
                            <span class="text-xs text-slate-400 font-semibold">OEE</span>
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-3 gap-3 w-full">
                        <div class="text-center">
                            <div class="text-lg font-bold text-indigo-600">{{ number_format($availability, 1) }}%</div>
                            <div class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Availability</div>
                        </div>
                        <div class="text-center">
                            <div class="text-lg font-bold text-violet-600">{{ number_format($performance, 1) }}%</div>
                            <div class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Performance</div>
                        </div>
                        <div class="text-center">
                            <div class="text-lg font-bold text-emerald-600">{{ number_format($quality, 1) }}%</div>
                            <div class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Quality</div>
                        </div>
                    </div>
                </div>

                {{-- Production Summary --}}
                <div class="lg:col-span-2 grid gap-4 sm:grid-cols-2">
                    <div class="card-hover rounded-2xl border border-slate-200/60 bg-gradient-to-br from-indigo-50/80 to-white p-5 shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-indigo-400 font-bold">Planned Qty</div>
                        <div class="mt-2 text-3xl font-black text-slate-900">{{ number_format($plantSummary['planned_qty'], 0) }}</div>
                        <div class="mt-1 text-sm text-slate-500">{{ number_format($plantSummary['orders_count']) }} WO in range</div>
                    </div>
                    <div class="card-hover rounded-2xl border border-slate-200/60 bg-gradient-to-br from-emerald-50/80 to-white p-5 shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-emerald-400 font-bold">Actual Qty</div>
                        <div class="mt-2 text-3xl font-black text-emerald-600">{{ number_format($plantSummary['actual_qty'], 0) }}</div>
                        <div class="mt-1 text-sm text-slate-500">
                            Good <span class="font-semibold text-emerald-600">{{ number_format($plantSummary['good_qty'], 0) }}</span>
                            / NG <span class="font-semibold text-red-500">{{ number_format($plantSummary['ng_qty'], 0) }}</span>
                        </div>
                    </div>
                    <div class="card-hover rounded-2xl border border-slate-200/60 bg-gradient-to-br from-violet-50/80 to-white p-5 shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-violet-400 font-bold">Production Achievement</div>
                        <div class="mt-2 text-3xl font-black text-violet-600">{{ number_format($productionAchievement, 1) }}%</div>
                        <div class="w-full bg-slate-200 rounded-full h-2 mt-3 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700 {{ $productionAchievement >= 95 ? 'bg-emerald-500' : ($productionAchievement >= 80 ? 'bg-indigo-500' : 'bg-amber-500') }}"
                                 style="width: {{ min($productionAchievement, 100) }}%"></div>
                        </div>
                    </div>
                    <div class="card-hover rounded-2xl border border-slate-200/60 bg-gradient-to-br from-amber-50/80 to-white p-5 shadow-sm">
                        <div class="text-xs uppercase tracking-wider text-amber-500 font-bold">Support Data</div>
                        <div class="mt-3 space-y-2 text-sm text-slate-600">
                            <div class="flex justify-between">
                                <span>Delivery Notes</span>
                                <span class="font-bold text-slate-900">{{ number_format($plantSummary['delivery_notes_count']) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Stock Opname Lines</span>
                                <span class="font-bold text-slate-900">{{ number_format($plantSummary['stock_opname_lines']) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════════
                 DEPARTMENT KPIs
                 ══════════════════════════════════════════════════════ --}}
            @php
                $deptColors = [
                    'Production' => ['from' => 'from-indigo-500', 'to' => 'to-violet-500', 'bg' => 'bg-indigo-50/50', 'border' => 'border-indigo-200/60', 'text' => 'text-indigo-700', 'badge' => 'bg-indigo-100 text-indigo-700', 'icon' => 'text-indigo-500'],
                    'Material'   => ['from' => 'from-cyan-500', 'to' => 'to-teal-500', 'bg' => 'bg-cyan-50/50', 'border' => 'border-cyan-200/60', 'text' => 'text-cyan-700', 'badge' => 'bg-cyan-100 text-cyan-700', 'icon' => 'text-cyan-500'],
                    'Logistics'  => ['from' => 'from-amber-500', 'to' => 'to-orange-500', 'bg' => 'bg-amber-50/50', 'border' => 'border-amber-200/60', 'text' => 'text-amber-700', 'badge' => 'bg-amber-100 text-amber-700', 'icon' => 'text-amber-500'],
                    'Quality'    => ['from' => 'from-rose-500', 'to' => 'to-pink-500', 'bg' => 'bg-rose-50/50', 'border' => 'border-rose-200/60', 'text' => 'text-rose-700', 'badge' => 'bg-rose-100 text-rose-700', 'icon' => 'text-rose-500'],
                ];
                $deptIcons = [
                    'Production' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
                    'Material' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M7 6V4h10v2"/>',
                    'Logistics' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>',
                    'Quality' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>',
                ];
            @endphp

            <div class="grid gap-4 lg:grid-cols-2">
                @foreach ($departments as $department => $items)
                    @php $colors = $deptColors[$department] ?? $deptColors['Production']; @endphp
                    <div class="card-hover rounded-2xl border {{ $colors['border'] }} {{ $colors['bg'] }} p-5 shadow-sm">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br {{ $colors['from'] }} {{ $colors['to'] }} flex items-center justify-center shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    {!! $deptIcons[$department] ?? '' !!}
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-black text-slate-900">{{ $department }}</h3>
                                <div class="text-xs text-slate-400">KPI Metrics</div>
                            </div>
                        </div>
                        <div class="grid gap-2 {{ count($items) > 4 ? 'sm:grid-cols-2 xl:grid-cols-3' : 'sm:grid-cols-2' }}">
                            @foreach ($items as $item)
                                <div class="dept-card rounded-xl bg-white/80 border border-slate-200/50 p-3 shadow-sm">
                                    <div class="text-[10px] uppercase tracking-wider text-slate-400 font-bold leading-4">{{ $item['name'] }}</div>
                                    <div class="mt-1.5 text-xl font-black {{ $colors['text'] }}">
                                        @if ($item['suffix'] === 'IDR')
                                            Rp {{ number_format($item['value'], 0) }}
                                        @elseif ($item['suffix'] === '%')
                                            {{ number_format($item['value'], 1) }}%
                                        @elseif ($item['suffix'] === 'min')
                                            {{ number_format($item['value'], 0) }} <span class="text-sm font-semibold">min</span>
                                        @elseif ($item['suffix'] === 'h')
                                            {{ number_format($item['value'], 1) }} <span class="text-sm font-semibold">hrs</span>
                                        @else
                                            {{ number_format($item['value'], 1) }} {{ $item['suffix'] }}
                                        @endif
                                    </div>
                                    <div class="mt-1 text-[10px] text-slate-400 leading-3">{{ $item['formula'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- ══════════════════════════════════════════════════════
                 QC STATUS + RECENT RECEIVES
                 ══════════════════════════════════════════════════════ --}}
            <div class="grid lg:grid-cols-3 gap-4">
                {{-- QC Status --}}
                <div class="card-hover rounded-2xl border border-slate-200/60 bg-white p-6 shadow-sm">
                    <div class="pb-4 border-b border-slate-100">
                        <h3 class="text-base font-black text-slate-900">QC Status</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Quality check summary</p>
                    </div>
                    <div class="mt-4 space-y-2.5">
                        @php
                            $statuses = ['pass' => 'Pass', 'fail' => 'Fail', 'hold' => 'Hold'];
                            $qcColors = [
                                'pass' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'ring' => 'ring-emerald-200/60', 'icon' => '✓'],
                                'fail' => ['bg' => 'bg-red-50', 'text' => 'text-red-700', 'ring' => 'ring-red-200/60', 'icon' => '✗'],
                                'hold' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'ring' => 'ring-amber-200/60', 'icon' => '⏸'],
                            ];
                        @endphp
                        @foreach ($statuses as $key => $label)
                            @php $c = $qcColors[$key]; @endphp
                            <div class="flex items-center justify-between p-3 rounded-xl {{ $c['bg'] }} ring-1 {{ $c['ring'] }}">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-lg {{ $c['bg'] }} {{ $c['text'] }} flex items-center justify-center font-bold text-sm">{{ $c['icon'] }}</span>
                                    <span class="text-sm font-semibold text-slate-700">{{ $label }}</span>
                                </div>
                                <span class="px-3 py-1 text-sm font-black rounded-lg {{ $c['bg'] }} {{ $c['text'] }}">
                                    {{ $statusCounts[$key] ?? 0 }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Recent Receives --}}
                <div class="lg:col-span-2 card-hover rounded-2xl border border-slate-200/60 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                        <div>
                            <h3 class="text-base font-black text-slate-900">Recent Receives</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Latest 5 processed items</p>
                        </div>
                        <a href="{{ route('receives.completed') }}"
                            class="px-4 py-2 bg-gradient-to-r from-indigo-500 to-violet-500 hover:from-indigo-400 hover:to-violet-400 text-white text-xs font-bold rounded-xl transition-all shadow-sm shadow-indigo-500/20">
                            View All
                        </a>
                    </div>
                    <div class="mt-4 space-y-2">
                        @forelse ($recentReceives as $receive)
                            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50/80 hover:bg-slate-100 transition-colors ring-1 ring-slate-200/40">
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-slate-900 text-sm truncate">{{ $receive->tag }}</div>
                                    <div class="text-xs text-slate-500 truncate">
                                        {{ $receive->arrivalItem?->part?->part_no ?? 'N/A' }} —
                                        {{ $receive->arrivalItem?->arrival?->vendor?->vendor_name ?? '' }}
                                    </div>
                                </div>
                                <div class="text-right ml-3 shrink-0">
                                    <div class="font-black text-slate-900">{{ number_format($receive->qty) }}</div>
                                    @php
                                        $qcColor = match ($receive->qc_status) {
                                            'pass' => 'bg-emerald-100 text-emerald-700',
                                            'fail' => 'bg-red-100 text-red-700',
                                            default => 'bg-amber-100 text-amber-700',
                                        };
                                    @endphp
                                    <span class="inline-flex px-2 py-0.5 text-xs font-bold rounded-lg {{ $qcColor }}">
                                        {{ ucfirst($receive->qc_status) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-400 text-center py-8">No receives yet</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════════
                 DEPARTURE RECORDS
                 ══════════════════════════════════════════════════════ --}}
            <div class="card-hover rounded-2xl border border-slate-200/60 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-black text-slate-900">Departure Records</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Inbound shipments with pricing breakdowns</p>
                    </div>
                    <a href="{{ route('departures.create') }}"
                        class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-xs rounded-xl transition-all shadow-sm shadow-indigo-600/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        New Departure
                    </a>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($departures as $arrival)
                        @php
                            $totalItems = $arrival->items->count();
                            $totalValue = $arrival->items->sum('total_price');
                            $totalQty = $arrival->items->sum('qty_goods');
                            $totalReceived = $arrival->items->sum(function ($item) {
                                return $item->receives->sum('qty');
                            });
                            $progress = $totalQty > 0 ? round(($totalReceived / $totalQty) * 100) : 0;
                        @endphp
                        <div class="rounded-xl border border-slate-200/60 p-4 hover:shadow-md transition-all bg-gradient-to-r from-white to-slate-50/50 group">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h4 class="text-sm font-black text-slate-900 truncate">
                                            {{ $arrival->invoice_no ?: 'Departure' }}</h4>
                                        <span class="text-[10px] text-slate-400 shrink-0">by {{ $arrival->creator->name ?? 'System' }}</span>
                                    </div>
                                    <div class="grid md:grid-cols-3 gap-1.5 text-xs text-slate-600">
                                        <div>
                                            <span class="text-slate-400">Vendor:</span>
                                            <span class="font-semibold text-slate-900 ml-1">{{ $arrival->vendor->vendor_name }}</span>
                                        </div>
                                        <div>
                                            <span class="text-slate-400">Items:</span>
                                            <span class="font-medium text-slate-900 ml-1">{{ $totalItems }} item{{ $totalItems != 1 ? 's' : '' }}</span>
                                            <span class="text-slate-400">({{ number_format($totalQty) }} pcs)</span>
                                        </div>
                                        <div>
                                            <span class="text-slate-400">Value:</span>
                                            <span class="font-bold text-indigo-600 ml-1">{{ $arrival->currency }} {{ number_format($totalValue, 2) }}</span>
                                        </div>
                                    </div>

                                    {{-- Progress Bar --}}
                                    <div class="mt-2.5">
                                        <div class="flex items-center justify-between text-[10px] mb-1">
                                            <span class="font-semibold text-slate-500 uppercase tracking-wider">Received</span>
                                            <span class="font-bold {{ $progress == 100 ? 'text-emerald-600' : 'text-indigo-600' }}">{{ $totalReceived }} / {{ number_format($totalQty) }}</span>
                                        </div>
                                        <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-500 {{ $progress == 100 ? 'bg-gradient-to-r from-emerald-400 to-emerald-500' : 'bg-gradient-to-r from-indigo-400 to-violet-500' }}"
                                                style="width: {{ $progress }}%"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col items-end gap-2 shrink-0">
                                    <span class="text-[10px] text-slate-400">{{ $arrival->invoice_date->format('d M Y') }}</span>
                                    <a href="{{ route('departures.show', $arrival) }}"
                                        class="px-3 py-1.5 bg-slate-100 hover:bg-indigo-50 text-slate-700 hover:text-indigo-700 text-xs font-bold rounded-lg transition-colors ring-1 ring-slate-200/60">
                                        Details →
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <div class="text-slate-300 mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900 mb-1">No Departures Yet</h3>
                            <p class="text-sm text-slate-400">Start by creating your first departure record.</p>
                        </div>
                    @endforelse
                </div>

                @if($departures->hasPages())
                    <div class="mt-6 pt-4 border-t border-slate-100">
                        {{ $departures->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>