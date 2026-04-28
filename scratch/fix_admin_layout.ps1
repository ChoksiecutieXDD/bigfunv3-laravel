$content = Get-Content 'resources/views/livewire/admin/new-booking.blade.php' -Raw
$pattern = '(?s)<div x-show="isChecked" class="mt-4 pt-3 border-t border-slate-100 w-full" @click\.stop x-cloak>.*?</div>\s+</div>'
$replacement = '</div>\r\n                                         <div x-show="isChecked" class="mt-2 pt-3 border-t border-slate-100 w-full" @click.stop x-cloak>
                                             <label class="text-[9px] font-black text-[#9E6B73] uppercase tracking-widest block mb-1.5 flex items-center gap-1.5">
                                                 <span class="material-symbols-rounded text-xs">edit_note</span>
                                                 Price Override
                                             </label>
                                             <div class="relative">
                                                 <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-slate-400 text-[10px] font-bold">$</span>
                                                 <input type="number" 
                                                        name="manual_prices[{{ $pName }}]"
                                                        step="0.01" 
                                                        class="manual-ride-price w-full bg-white border border-slate-200 rounded-xl py-1.5 pl-5 pr-2 text-[11px] font-black text-slate-700 focus:ring-2 focus:ring-[#9E6B73]/20 focus:border-[#9E6B73] transition-all" 
                                                        placeholder="{{ number_format($product[\x27price\x27] ?? 100, 2) }}"
                                                        value="{{ $isChecked ? ($this->selected_manual_prices[$pName] ?? \x27\x27) : \x27\x27 }}"
                                                        @input.debounce.500ms="$wire.updateManualPrice(\x27{{ $pName }}\x27, $event.target.value); triggerRecalculate()">
                                             </div>
                                         </div>'
$content = [regex]::Replace($content, $pattern, $replacement)
Set-Content 'resources/views/livewire/admin/new-booking.blade.php' $content -NoNewline
