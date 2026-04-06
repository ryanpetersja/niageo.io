<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>NiageoOps — Business Operations Platform</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3.x.x/dist/cdn.min.js"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <style>
            .gradient-hero {
                background: linear-gradient(135deg, #1e1b4b 0%, #312e81 25%, #3730a3 50%, #1e293b 100%);
            }
            .feature-card {
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }
            .feature-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            }
            .stat-number {
                background: linear-gradient(135deg, #6366f1, #8b5cf6);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }
            @keyframes fade-in-up {
                from { opacity: 0; transform: translateY(30px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .animate-fade-in-up {
                animation: fade-in-up 0.6s ease-out forwards;
            }
            .animate-delay-100 { animation-delay: 0.1s; opacity: 0; }
            .animate-delay-200 { animation-delay: 0.2s; opacity: 0; }
            .animate-delay-300 { animation-delay: 0.3s; opacity: 0; }
            .animate-delay-400 { animation-delay: 0.4s; opacity: 0; }
        </style>
    </head>
    <body class="antialiased font-sans text-gray-900 bg-white">

        {{-- Navigation --}}
        <nav class="fixed top-0 left-0 right-0 z-50 transition-all duration-300" x-data="{ scrolled: false }" x-init="window.addEventListener('scroll', () => scrolled = window.scrollY > 20)" :class="scrolled ? 'bg-white/95 backdrop-blur-md shadow-sm' : 'bg-transparent'">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16 lg:h-20">
                    {{-- Logo --}}
                    <a href="/" class="flex items-center gap-2.5 group">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center transition-colors" :class="scrolled ? 'bg-indigo-600' : 'bg-white/15'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                            </svg>
                        </div>
                        <span class="text-xl font-bold tracking-tight transition-colors" :class="scrolled ? 'text-gray-900' : 'text-white'">
                            Niageo<span class="text-indigo-400">Ops</span>
                        </span>
                    </a>

                    {{-- Nav Links --}}
                    <div class="flex items-center gap-3">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                                </svg>
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors" :class="scrolled ? 'text-gray-700 hover:text-indigo-600' : 'text-white/80 hover:text-white'">
                                Log In
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="inline-flex items-center px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-sm">
                                    Get Started
                                </a>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        {{-- Hero Section --}}
        <section class="gradient-hero relative overflow-hidden">
            {{-- Background decoration --}}
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute -top-40 -right-40 w-[500px] h-[500px] rounded-full bg-indigo-500/10 blur-3xl"></div>
                <div class="absolute -bottom-40 -left-40 w-[400px] h-[400px] rounded-full bg-purple-500/10 blur-3xl"></div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] rounded-full bg-indigo-400/5 blur-3xl"></div>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-32 pb-24 lg:pt-44 lg:pb-36">
                <div class="max-w-3xl mx-auto text-center">
                    <div class="animate-fade-in-up">
                        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/10 backdrop-blur-sm border border-white/10 text-indigo-200 text-sm font-medium mb-8">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            Internal Operations Platform
                        </div>
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white leading-tight tracking-tight animate-fade-in-up animate-delay-100">
                        Streamline Your
                        <span class="block text-transparent bg-clip-text bg-gradient-to-r from-indigo-300 via-purple-300 to-indigo-300">Business Operations</span>
                    </h1>

                    <p class="mt-6 text-lg sm:text-xl text-indigo-100/80 leading-relaxed max-w-2xl mx-auto animate-fade-in-up animate-delay-200">
                        Manage clients, invoicing, payments, and revenue tracking from a single platform. No per-user fees. No bloat. Just the tools you need.
                    </p>

                    <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4 animate-fade-in-up animate-delay-300">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-xl text-base font-semibold text-indigo-900 bg-white hover:bg-indigo-50 transition-colors shadow-lg shadow-indigo-900/20">
                                Go to Dashboard
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-xl text-base font-semibold text-indigo-900 bg-white hover:bg-indigo-50 transition-colors shadow-lg shadow-indigo-900/20">
                                Log In
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                            <a href="#features" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 rounded-xl text-base font-medium text-white/90 border border-white/20 hover:bg-white/10 transition-colors">
                                Learn More
                            </a>
                        @endauth
                    </div>
                </div>
            </div>

            {{-- Bottom wave --}}
            <div class="absolute bottom-0 left-0 right-0">
                <svg viewBox="0 0 1440 80" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full">
                    <path d="M0 80V40C240 0 480 0 720 20C960 40 1200 60 1440 40V80H0Z" fill="white"/>
                </svg>
            </div>
        </section>

        {{-- Features Section --}}
        <section id="features" class="py-20 lg:py-28 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 tracking-tight">
                        Everything You Need
                    </h2>
                    <p class="mt-4 text-lg text-gray-500">
                        Purpose-built modules designed for lean business operations, without the bloat of enterprise CRMs.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
                    {{-- Client Management --}}
                    <div class="feature-card group p-6 lg:p-8 bg-white rounded-2xl border border-gray-100 shadow-sm">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center mb-5 group-hover:bg-indigo-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Client Management</h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Track contacts, billing preferences, notes, and pricing presets for every client in one place.
                        </p>
                    </div>

                    {{-- Invoicing & PDF --}}
                    <div class="feature-card group p-6 lg:p-8 bg-white rounded-2xl border border-gray-100 shadow-sm">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center mb-5 group-hover:bg-emerald-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Invoicing & PDF Export</h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Create professional invoices with line items, auto-numbering, and branded PDF generation ready to send.
                        </p>
                    </div>

                    {{-- Revenue Dashboard --}}
                    <div class="feature-card group p-6 lg:p-8 bg-white rounded-2xl border border-gray-100 shadow-sm">
                        <div class="w-12 h-12 rounded-xl bg-violet-50 flex items-center justify-center mb-5 group-hover:bg-violet-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Revenue Dashboard</h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Real-time view of revenue, outstanding receivables, and payment status across all clients.
                        </p>
                    </div>

                    {{-- Payment Tracking --}}
                    <div class="feature-card group p-6 lg:p-8 bg-white rounded-2xl border border-gray-100 shadow-sm">
                        <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center mb-5 group-hover:bg-amber-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Payment Tracking</h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Record payments against invoices, track partial payments, and maintain a complete payment history.
                        </p>
                    </div>

                    {{-- Branding --}}
                    <div class="feature-card group p-6 lg:p-8 bg-white rounded-2xl border border-gray-100 shadow-sm">
                        <div class="w-12 h-12 rounded-xl bg-sky-50 flex items-center justify-center mb-5 group-hover:bg-sky-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">White-Label Branding</h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Customize your invoices and PDFs with your logo, company details, colors, and footer text.
                        </p>
                    </div>

                    {{-- Overdue Alerts --}}
                    <div class="feature-card group p-6 lg:p-8 bg-white rounded-2xl border border-gray-100 shadow-sm">
                        <div class="w-12 h-12 rounded-xl bg-rose-50 flex items-center justify-center mb-5 group-hover:bg-rose-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0M3.124 7.5A8.969 8.969 0 015.292 3m13.416 0a8.969 8.969 0 012.168 4.5" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Overdue Alerts</h3>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Automatic overdue detection marks past-due invoices daily so nothing falls through the cracks.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Stats Section --}}
        <section class="py-16 lg:py-20 bg-gray-50 border-y border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12">
                    <div class="text-center" x-data="{ count: 0 }" x-intersect.once="
                        let target = 100;
                        let step = Math.ceil(target / 40);
                        let interval = setInterval(() => { count += step; if(count >= target) { count = target; clearInterval(interval); } }, 30);
                    ">
                        <div class="text-4xl lg:text-5xl font-extrabold stat-number" x-text="count + '%'">100%</div>
                        <p class="mt-2 text-sm text-gray-500 font-medium">Data Ownership</p>
                    </div>
                    <div class="text-center" x-data="{ count: 0 }" x-intersect.once="
                        let target = 0;
                        let interval = setInterval(() => { count++; if(count >= 0) { clearInterval(interval); } }, 30);
                    ">
                        <div class="text-4xl lg:text-5xl font-extrabold stat-number">$0</div>
                        <p class="mt-2 text-sm text-gray-500 font-medium">Per-User Fees</p>
                    </div>
                    <div class="text-center" x-data="{ count: 0 }" x-intersect.once="
                        let target = 5;
                        let step = 1;
                        let interval = setInterval(() => { count += step; if(count >= target) { count = target; clearInterval(interval); } }, 150);
                    ">
                        <div class="text-4xl lg:text-5xl font-extrabold stat-number" x-text="count">5</div>
                        <p class="mt-2 text-sm text-gray-500 font-medium">Core Modules</p>
                    </div>
                    <div class="text-center" x-data="{ count: 0 }" x-intersect.once="
                        let target = 24;
                        let step = 1;
                        let interval = setInterval(() => { count += step; if(count >= target) { count = target; clearInterval(interval); } }, 60);
                    ">
                        <div class="text-4xl lg:text-5xl font-extrabold stat-number" x-text="count + '/7'">24/7</div>
                        <p class="mt-2 text-sm text-gray-500 font-medium">Self-Hosted Uptime</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- CTA Section --}}
        <section class="py-20 lg:py-28">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="relative rounded-3xl overflow-hidden gradient-hero px-6 py-16 sm:px-16 sm:py-20 text-center">
                    {{-- Background decoration --}}
                    <div class="absolute inset-0 overflow-hidden">
                        <div class="absolute -top-20 -right-20 w-[300px] h-[300px] rounded-full bg-indigo-500/10 blur-3xl"></div>
                        <div class="absolute -bottom-20 -left-20 w-[250px] h-[250px] rounded-full bg-purple-500/10 blur-3xl"></div>
                    </div>

                    <div class="relative">
                        <h2 class="text-3xl sm:text-4xl font-bold text-white tracking-tight">
                            Ready to Take Control?
                        </h2>
                        <p class="mt-4 text-lg text-indigo-100/80 max-w-xl mx-auto">
                            Stop paying per-seat SaaS fees. Run your operations on your own infrastructure with full data ownership.
                        </p>
                        <div class="mt-8">
                            @auth
                                <a href="{{ url('/dashboard') }}" class="inline-flex items-center gap-2 px-8 py-3.5 rounded-xl text-base font-semibold text-indigo-900 bg-white hover:bg-indigo-50 transition-colors shadow-lg">
                                    Open Dashboard
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                    </svg>
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-8 py-3.5 rounded-xl text-base font-semibold text-indigo-900 bg-white hover:bg-indigo-50 transition-colors shadow-lg">
                                    Get Started Now
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                    </svg>
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Footer --}}
        <footer class="border-t border-gray-100 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                            </svg>
                        </div>
                        <span class="text-lg font-bold text-gray-900">Niageo<span class="text-indigo-600">Ops</span></span>
                    </div>
                    <p class="text-sm text-gray-400">
                        &copy; {{ date('Y') }} NiageoOps. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>

    </body>
</html>
