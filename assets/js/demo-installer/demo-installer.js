document.addEventListener('DOMContentLoaded', () => {
    const btnInstall = document.getElementById('webnova-btn-install');
    const btnUninstall = document.getElementById('webnova-btn-uninstall');
    const logBox = document.getElementById('webnova-installer-log');
    
    if (!btnInstall) return;

    const steps = [
        { id: 'validate', action: 'webnova_installer_validate' },
        { id: 'media', action: 'webnova_installer_media' },
        { id: 'terms', action: 'webnova_installer_terms' },
        { id: 'content', action: 'webnova_installer_content' },
        { id: 'menus', action: 'webnova_installer_menus' },
        { id: 'settings', action: 'webnova_installer_settings' },
        { id: 'finalize', action: 'webnova_installer_finalize' }
    ];

    function logMessage(msg, type = 'info') {
        const p = document.createElement('p');
        p.className = `log-${type}`;
        p.textContent = msg;
        logBox.appendChild(p);
        logBox.scrollTop = logBox.scrollHeight;
    }

    function setStepStatus(stepId, status) {
        const el = document.querySelector(`li[data-step="${stepId}"]`);
        if (el) {
            el.className = status;
        }
    }

    async function runStep(stepIndex) {
        if (stepIndex >= steps.length) {
            logMessage('Instalación completada con éxito.', 'success');
            btnInstall.disabled = false;
            return;
        }

        const step = steps[stepIndex];
        setStepStatus(step.id, 'active');
        logMessage(`Iniciando paso: ${step.id}...`);

        try {
            const formData = new FormData();
            formData.append('action', step.action);
            formData.append('_ajax_nonce', webnovaInstallerSettings.nonce);

            const response = await fetch(webnovaInstallerSettings.ajax_url, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP Error: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                logMessage(`Paso ${step.id} completado: ${data.data.message}`, 'success');
                setStepStatus(step.id, 'completed');
                
                // Si el paso nos devuelve que requiere otra iteración (batching)
                if (data.data.continue) {
                    runStep(stepIndex);
                } else {
                    runStep(stepIndex + 1);
                }
            } else {
                logMessage(`Error en paso ${step.id}: ${data.data || webnovaInstallerSettings.texts.error_generic}`, 'error');
                setStepStatus(step.id, 'failed');
                btnInstall.disabled = false;
            }

        } catch (error) {
            logMessage(`Error de red en paso ${step.id}: ${error.message}`, 'error');
            setStepStatus(step.id, 'failed');
            btnInstall.disabled = false;
        }
    }

    btnInstall.addEventListener('click', () => {
        btnInstall.disabled = true;
        logBox.innerHTML = '';
        
        document.querySelectorAll('#webnova-installer-steps li').forEach(el => {
            el.className = 'pending';
        });

        logMessage('Iniciando proceso de instalación...');
        runStep(0);
    });

    btnUninstall.addEventListener('click', async () => {
        if (!confirm(webnovaInstallerSettings.texts.confirm_uninstall)) {
            return;
        }

        btnUninstall.disabled = true;
        logBox.innerHTML = '';
        logMessage('Iniciando desinstalación...');

        try {
            const formData = new FormData();
            formData.append('action', 'webnova_installer_uninstall');
            formData.append('_ajax_nonce', webnovaInstallerSettings.nonce);

            const response = await fetch(webnovaInstallerSettings.ajax_url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                logMessage(`Desinstalación completada: ${data.data.message}`, 'success');
            } else {
                logMessage(`Error en desinstalación: ${data.data || webnovaInstallerSettings.texts.error_generic}`, 'error');
            }
        } catch (error) {
            logMessage(`Error de red: ${error.message}`, 'error');
        } finally {
            btnUninstall.disabled = false;
        }
    });
});
