<x-app-layout>
    <x-slot name="header">
        Smart Application System Portal
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white border border-slate-200 rounded-2xl shadow-lg shadow-blue-500/5 p-6">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Smart Application System</h2>
                    <p class="text-sm text-slate-500">Central hub for departures and receiving activities</p>
                </div>
                <span class="px-3 py-1 text-xs font-semibold uppercase tracking-wide bg-blue-50 text-blue-600 rounded-full">Module Overview</span>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <a href="{{ route('departures.create') }}" class="block border border-blue-100 rounded-xl p-4 bg-gradient-to-br from-white to-blue-50 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-blue-500 font-semibold">Create</p>
                            <h3 class="mt-1 text-lg font-bold text-slate-900">New Departure</h3>
                            <p class="text-sm text-slate-600 mt-1">Set up shipment information and vendor data.</p>
                        </div>
                        <div class="h-10 w-10 rounded-lg bg-blue-500 text-white grid place-items-center shadow-lg shadow-blue-500/40">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        </div>
                    </div>
                </a>

                <a href="{{ route('departures.index') }}" class="block border border-slate-200 rounded-xl p-4 bg-white hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Monitor</p>
                            <h3 class="mt-1 text-lg font-bold text-slate-900">Departure List</h3>
                            <p class="text-sm text-slate-600 mt-1">Track invoices, containers, and pending items.</p>
                        </div>
                        <div class="h-10 w-10 rounded-lg bg-slate-900 text-white grid place-items-center shadow-lg shadow-slate-900/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.25h13.5v13.5H5.25zM9 9h6v6H9z"/></svg>
                        </div>
                    </div>
                </a>

                <a href="{{ route('receives.index') }}" class="block border border-slate-200 rounded-xl p-4 bg-white hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Operate</p>
                            <h3 class="mt-1 text-lg font-bold text-slate-900">Process Receives</h3>
                            <p class="text-sm text-slate-600 mt-1">Scan arrival items and capture QC decisions.</p>
                        </div>
                        <div class="h-10 w-10 rounded-lg bg-green-500 text-white grid place-items-center shadow-lg shadow-green-500/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z"/></svg>
                        </div>
                    </div>
                </a>

                <a href="{{ route('receives.completed') }}" class="block border border-slate-200 rounded-xl p-4 bg-white hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Review</p>
                            <h3 class="mt-1 text-lg font-bold text-slate-900">Completed Receives</h3>
                            <p class="text-sm text-slate-600 mt-1">Audit and print labels for finalized batches.</p>
                        </div>
                        <div class="h-10 w-10 rounded-lg bg-emerald-500 text-white grid place-items-center shadow-lg shadow-emerald-500/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z"/></svg>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
