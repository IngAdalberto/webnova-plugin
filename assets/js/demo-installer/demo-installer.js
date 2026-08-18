document.addEventListener('DOMContentLoaded', () => {
    const installButtons = document.querySelectorAll('.webnova-btn-install');
    const logBox = document.getElementById('webnova-installer-log');
    const uiContainer = document.getElementById('webnova-installer-ui');
    
    if (installButtons.length === 0) return;

    let currentTemplateId = null;

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

    function toggleButtons(disabled) {
        installButtons.forEach(btn => btn.disabled = disabled);
    }

    async function runStep(stepIndex) {
        if (stepIndex >= steps.length) {
            logMessage('Instalación completada con éxito.', 'success');
            toggleButtons(false);
            return;
        }

        const step = steps[stepIndex];
        setStepStatus(step.id, 'active');
        logMessage(`Iniciando paso: ${step.id}...`);

        try {
            const formData = new FormData();
            formData.append('action', step.action);
            formData.append('template_id', currentTemplateId);
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
                toggleButtons(false);
            }

        } catch (error) {
            logMessage(`Error de red en paso ${step.id}: ${error.message}`, 'error');
            setStepStatus(step.id, 'failed');
            toggleButtons(false);
        }
    }

    installButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            currentTemplateId = btn.dataset.templateId;
            toggleButtons(true);
            logBox.innerHTML = '';
            
            if (uiContainer) {
                uiContainer.style.display = 'block';
                // Scroll suave al contenedor de UI
                uiContainer.scrollIntoView({ behavior: 'smooth' });
            }
            
            document.querySelectorAll('#webnova-installer-steps li').forEach(el => {
                el.className = 'pending';
            });

            logMessage(`Iniciando proceso de instalación para la plantilla: ${currentTemplateId}...`);
            runStep(0);
        });
    });
});
