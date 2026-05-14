@auth
    @php
        $user = auth()->user();
        $role = $user->role;
        $isPrivileged = in_array($role, ['Administrator', 'Admin', 'Supervisor', 'Staff', 'Operator', 'Deliverer']);
        
        $currentPath = request()->path();
        $isInPanel = false;
        
        if ($isPrivileged) {
            if (str_starts_with($currentPath, 'admin') || 
                str_starts_with($currentPath, 'supervisor') || 
                str_starts_with($currentPath, 'staff')) {
                $isInPanel = true;
            }
        }
    @endphp

    @if($isPrivileged)
        <div x-data="sessionMonitor({
            isInPanel: {{ $isInPanel ? 'true' : 'false' }},
            logoutUrl: '{{ route('logout') }}',
            csrfToken: '{{ csrf_token() }}'
        })"
        class="fixed bottom-6 right-6 z-[9999]"
        x-show="showCountdown"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-95">
            
            <div class="bg-white/80 backdrop-blur-md border border-red-100 shadow-2xl rounded-2xl p-4 flex items-center gap-4 max-w-sm">
                <div class="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center text-red-500 shrink-0">
                    <span class="material-symbols-rounded text-2xl animate-pulse">timer</span>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-0.5">Inactive Session</p>
                    <p class="text-sm font-semibold text-gray-800">
                        Logging out in <span class="text-red-500 font-black tabular-nums" x-text="timeLeft">60</span>s
                    </p>
                </div>
                <a href="{{ 
                    $role === 'Supervisor' ? '/supervisor/calendar' : 
                    (in_array($role, ['Administrator', 'Admin']) ? '/admin/dashboard' : '/staff/dashboard') 
                }}" 
                class="ml-2 px-4 py-2 bg-[#9E6B73] text-white text-xs font-bold rounded-xl hover:bg-[#86545C] transition-all shadow-md">
                    Return
                </a>
            </div>
        </div>

        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('sessionMonitor', (config) => ({
                    isInPanel: config.isInPanel,
                    timeLeft: 60,
                    showCountdown: false,
                    checkInterval: null,
                    storageKey: 'last_panel_activity',
                    
                    init() {
                        if (this.isInPanel) {
                            // Update activity timestamp frequently
                            localStorage.setItem(this.storageKey, Date.now());
                            setInterval(() => {
                                localStorage.setItem(this.storageKey, Date.now());
                            }, 5000);
                        } else {
                            // Start monitoring
                            this.startMonitoring();
                        }
                    },

                    startMonitoring() {
                        this.checkInterval = setInterval(() => {
                            const lastActivity = parseInt(localStorage.getItem(this.storageKey) || '0');
                            const timeSinceActivity = Date.now() - lastActivity;
                            const timeoutMs = 60000;
                            const remaining = Math.max(0, Math.ceil((timeoutMs - timeSinceActivity) / 1000));

                            this.timeLeft = remaining;

                            // Show countdown when less than 30 seconds left
                            if (remaining <= 45) {
                                this.showCountdown = true;
                            } else {
                                this.showCountdown = false;
                            }

                            if (remaining <= 0) {
                                this.logout();
                            }
                        }, 1000);
                    },

                    logout() {
                        clearInterval(this.checkInterval);
                        
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = config.logoutUrl;
                        
                        const token = document.createElement('input');
                        token.type = 'hidden';
                        token.name = '_token';
                        token.value = config.csrfToken;
                        
                        form.appendChild(token);
                        document.body.appendChild(form);
                        form.submit();
                    }
                }));
            });
        </script>
    @endif
@endauth
