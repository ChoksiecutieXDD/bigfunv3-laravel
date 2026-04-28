$path = 'resources/views/livewire/admin/new-booking.blade.php'
$content = Get-Content $path -Raw
$broken = '</div>\r\n                                         <div x-show="isChecked" class="mt-2 pt-3 border-t border-slate-100 w-full" @click.stop x-cloak>'
$fixed = '</div>' + [char]13 + [char]10 + '                                         <div x-show="isChecked" class="mt-2 pt-3 border-t border-slate-100 w-full" @click.stop x-cloak>'
$content = $content.Replace($broken, $fixed)
$content = $content.Replace('\x27', "'")
Set-Content $path $content -NoNewline
