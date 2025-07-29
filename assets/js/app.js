
/**
 * Sistema MRP - JavaScript CORRIGIDO
 */

class MRPSystem {
    constructor() {
        this.init();
        this.bindEvents();
        this.initTooltips();
        console.log('🚀 Sistema MRP Enterprise iniciado');
    }

    init() {
        this.createToastContainer();
        this.setupKeyboardShortcuts();
    }

    bindEvents() {
        this.setupAutoHideAlerts();
        this.setupNumericInputs();
        this.setupButtonLoadingStates();
    }

    // CORREÇÃO: Atalhos de teclado que NÃO conflitam com browser
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // CORREÇÃO: F2 em vez de F1 (F1 é help do browser)
            if (e.key === 'F2') {
                e.preventDefault();
                this.showHelp();
            }
            
            // CORREÇÃO: Ctrl + Shift + E (não Alt+E que conflita)
            if (e.ctrlKey && e.shiftKey && e.key === 'E') {
                e.preventDefault();
                window.location.href = 'estoque.php';
            }
            
            // CORREÇÃO: Ctrl + Shift + M (não Alt+M que conflita)
            if (e.ctrlKey && e.shiftKey && e.key === 'M') {
                e.preventDefault();
                window.location.href = 'mrp.php';
            }
            
            // CORREÇÃO: Ctrl + Shift + H = Home
            if (e.ctrlKey && e.shiftKey && e.key === 'H') {
                e.preventDefault();
                window.location.href = 'index.php';
            }
            
            // Escape = Fechar modais (este funciona)
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.modal.show');
                if (openModal) {
                    const modal = bootstrap.Modal.getInstance(openModal);
                    modal?.hide();
                }
            }
            
            // Ctrl + S = Salvar (funciona)
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                this.saveCurrentForm();
            }
        });
    }

    saveCurrentForm() {
        const activeForm = document.querySelector('form:not([style*="display: none"])');
        if (activeForm) {
            const submitBtn = activeForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.click();
                this.showToast('Salvando...', 'info', 1000);
            }
        }
    }

    // CORREÇÃO: Ajuda com atalhos corretos
    showHelp() {
        const helpContent = `
            <div class="help-content">
                <h5><i class="fas fa-keyboard me-2"></i>Atalhos do Teclado</h5>
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-unstyled">
                            <li><kbd>F2</kbd> - Esta ajuda</li>
                            <li><kbd>Ctrl + S</kbd> - Salvar formulário</li>
                            <li><kbd>Esc</kbd> - Fechar modal</li>
                            <li><kbd>Ctrl + Shift + H</kbd> - Ir para Home</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-unstyled">
                            <li><kbd>Ctrl + Shift + E</kbd> - Ir para Estoque</li>
                            <li><kbd>Ctrl + Shift + M</kbd> - Ir para MRP</li>
                            <li><kbd>Ctrl + N</kbd> - Novo item (se aplicável)</li>
                            <li><kbd>Ctrl + ↑/↓</kbd> - Ajustar números</li>
                        </ul>
                    </div>
                </div>
                <hr>
                <h6><i class="fas fa-lightbulb me-2"></i>Dicas</h6>
                <ul class="small">
                    <li>Use Tab para navegar entre campos</li>
                    <li>Clique duas vezes em valores para editar</li>
                    <li>Alertas desaparecem automaticamente</li>
                </ul>
            </div>
        `;
        
        this.showModal('Ajuda do Sistema - Atalhos Corrigidos', helpContent);
    }

    setupAutoHideAlerts() {
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(alert => {
                try {
                    const bsAlert = new bootstrap.Alert(alert);
                    if (bsAlert) {
                        setTimeout(() => bsAlert.close(), 100);
                    }
                } catch(e) {
                    // Silenciosamente ignora erros de alerts já fechados
                }
            });
        }, 5000);
    }

    setupNumericInputs() {
        const numericInputs = document.querySelectorAll('input[type="number"]');
        numericInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                if (e.target.value < 0) {
                    e.target.value = 0;
                    this.showToast('Valores negativos não são permitidos', 'warning', 2000);
                }
            });
        });
    }

    setupButtonLoadingStates() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.disabled) {
                    this.setButtonLoading(submitBtn, true);
                    
                    // Remove loading após timeout
                    setTimeout(() => {
                        this.setButtonLoading(submitBtn, false);
                    }, 5000);
                }
            });
        });
    }

    setButtonLoading(button, loading) {
        if (loading) {
            button.disabled = true;
            const originalText = button.innerHTML;
            button.setAttribute('data-original-text', originalText);
            button.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                Processando...
            `;
        } else {
            button.disabled = false;
            const originalText = button.getAttribute('data-original-text');
            if (originalText) {
                button.innerHTML = originalText;
            }
        }
    }

    initTooltips() {
        try {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(tooltipTriggerEl => {
                return new bootstrap.Tooltip(tooltipTriggerEl, {
                    delay: { show: 500, hide: 100 },
                    placement: 'top'
                });
            });
        } catch(e) {
            console.log('Tooltips não puderam ser inicializados');
        }
    }

    showToast(message, type = 'info', duration = 4000) {
        const toastContainer = this.getToastContainer();
        const toastId = 'toast-' + Date.now();
        
        const iconMap = {
            success: 'check-circle',
            error: 'exclamation-triangle',
            warning: 'exclamation-circle',
            info: 'info-circle'
        };
        
        const colorMap = {
            success: 'success',
            error: 'danger',
            warning: 'warning',
            info: 'info'
        };
        
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white bg-${colorMap[type]} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-${iconMap[type]} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { delay: duration });
        
        toast.show();
        
        toastElement.addEventListener('hidden.bs.toast', function() {
            toastElement.remove();
        });
        
        return toast;
    }

    getToastContainer() {
        let container = document.getElementById('toast-container');
        
        if (!container) {
            container = this.createToastContainer();
        }
        
        return container;
    }

    createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    }

    showModal(title, content, options = {}) {
        const { size = '', headerClass = 'bg-primary' } = options;
        const modalId = 'generic-modal-' + Date.now();
        
        const modalHtml = `
            <div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog ${size} modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header ${headerClass}">
                            <h5 class="modal-title text-white">${title}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${content}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                                <i class="fas fa-check me-2"></i>
                                Entendi
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        const modalElement = document.getElementById(modalId);
        const modal = new bootstrap.Modal(modalElement);
        
        modalElement.addEventListener('hidden.bs.modal', () => {
            modalElement.remove();
        });
        
        modal.show();
    }
}

// Inicialização quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.MRPSystem = new MRPSystem();
});

// Exporta para uso global
window.MRP = {
    showToast: (message, type, duration) => window.MRPSystem?.showToast(message, type, duration)
};
