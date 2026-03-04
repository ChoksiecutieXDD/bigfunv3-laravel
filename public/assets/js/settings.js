// public/assets/js/settings.js

// ----------------------------------------------------
// Event Listeners for System Settings
// Note: showAlert() is now globally available via components.js
// ----------------------------------------------------

document.addEventListener('DOMContentLoaded', () => {

    // 1. Export Database Logic
    const btnExportBackup = document.getElementById('btn-export-backup');
    if (btnExportBackup) {
        btnExportBackup.addEventListener('click', function () {
            const textEl = document.getElementById('export-text');
            const originalText = textEl.innerText;

            textEl.innerText = "Generating Backup...";
            textEl.classList.add('text-emerald-400');

            showAlert('Preparing database backup...', 'success');

            window.location.href = '/api/system/maintenance?action=export_db';

            setTimeout(() => {
                textEl.innerText = originalText;
                textEl.classList.remove('text-emerald-400');
            }, 3000);
        });
    }

    // 2. Clear Cache Logic
    const btnClearCache = document.getElementById('btn-clear-cache');
    if (btnClearCache) {
        btnClearCache.addEventListener('click', async function () {
            const btn = this;
            const textEl = document.getElementById('cache-text');
            const originalText = textEl.innerText;

            textEl.innerText = "Clearing...";
            btn.style.pointerEvents = 'none';
            btn.classList.add('opacity-50');

            try {
                const res = await fetch('/api/system/maintenance', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'clear_cache' })
                });

                const contentType = res.headers.get("content-type");
                if (!contentType || !contentType.includes("application/json")) {
                    throw new Error("Server error: Did not return valid JSON.");
                }

                const data = await res.json();

                if (data.success) {
                    textEl.innerText = "Cache Cleared!";
                    textEl.classList.add('text-emerald-400');
                    showAlert('System cache cleared successfully.', 'success');

                    setTimeout(() => {
                        textEl.innerText = originalText;
                        textEl.classList.remove('text-emerald-400');
                    }, 2000);
                } else {
                    showAlert("Error: " + data.message, 'error');
                    textEl.innerText = originalText;
                }
            } catch (err) {
                console.error("Cache Clear Failed:", err);
                showAlert('Connection error. Check console for details.', 'error');
                textEl.innerText = originalText;
            } finally {
                btn.style.pointerEvents = 'auto';
                btn.classList.remove('opacity-50');
            }
        });
    }

    // 3. Maintenance Mode Logic
    const toggleMaintenance = document.getElementById('maintenance-toggle');
    if (toggleMaintenance) {
        toggleMaintenance.addEventListener('change', async function () {
            const isEnabled = this.checked;
            const statusText = document.getElementById('maintenance-status-text');

            try {
                const res = await fetch('/api/system/maintenance', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'toggle_maintenance',
                        status: isEnabled
                    })
                });

                const contentType = res.headers.get("content-type");
                if (!contentType || !contentType.includes("application/json")) {
                    throw new Error("Server error: Did not return valid JSON.");
                }

                const data = await res.json();

                if (data.success) {
                    if (isEnabled) {
                        statusText.innerHTML = '<span class="text-amber-500">Currently Active</span>';
                        showAlert('Maintenance mode turned ON.', 'info');
                    } else {
                        statusText.innerHTML = 'Restricts public access';
                        showAlert('Maintenance mode turned OFF.', 'success');
                    }
                } else {
                    this.checked = !isEnabled;
                    showAlert("Error: " + data.message, 'error');
                }
            } catch (err) {
                this.checked = !isEnabled;
                console.error("Maintenance Toggle Failed:", err);
                showAlert('Connection error. Check console for details.', 'error');
            }
        });
    }

    // 4. Environment Change Logic
    const envSelect = document.getElementById('env-select');
    if (envSelect) {
        envSelect.addEventListener('change', async function () {
            const select = this;
            const newEnv = select.value;

            select.classList.remove('text-amber-400', 'text-blue-400', 'text-emerald-400');
            if (newEnv === 'development') select.classList.add('text-amber-400');
            if (newEnv === 'staging') select.classList.add('text-blue-400');
            if (newEnv === 'production') select.classList.add('text-emerald-400');

            try {
                const res = await fetch('/api/system/maintenance', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'change_environment',
                        environment: newEnv
                    })
                });

                const data = await res.json();
                if (data.success) {
                    showAlert(`Environment set to ${newEnv.toUpperCase()}`, 'success');
                } else {
                    showAlert("Error: " + data.message, 'error');
                }
            } catch (err) {
                console.error("Environment Change Failed:", err);
                showAlert('Failed to save the environment setting.', 'error');
            }
        });
    }

    // 5. Test SMTP Logic
    const btnTestSmtp = document.getElementById('btn-test-smtp');
    if (btnTestSmtp) {
        btnTestSmtp.addEventListener('click', async function () {
            const btn = this;
            const btnText = document.getElementById('smtp-btn-text');

            const host = document.getElementById('smtp_host').value;
            const port = document.getElementById('smtp_port').value;
            const enc = document.getElementById('smtp_enc').value;
            const user = document.getElementById('smtp_user').value;
            const pass = document.getElementById('smtp_pass').value;

            if (!host || !port || !user || !pass) {
                showAlert('Please fill in all SMTP fields before testing.', 'error');
                return;
            }

            btnText.innerText = "Sending Test...";
            btn.disabled = true;

            try {
                const res = await fetch('/api/system/maintenance', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'test_smtp',
                        host: host,
                        port: port,
                        enc: enc,
                        user: user,
                        pass: pass
                    })
                });

                const data = await res.json();

                if (data.success) {
                    showAlert(data.message, 'success');
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (err) {
                console.error("SMTP Test Failed:", err);
                showAlert('Failed to connect to server. Check console for details.', 'error');
            } finally {
                btnText.innerText = "Test Connection";
                btn.disabled = false;
            }
        });
    }

});