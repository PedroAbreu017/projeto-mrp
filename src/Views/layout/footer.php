</div> <!-- Fim do container principal -->

    <!-- Footer -->
    <footer class="footer mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">
                        <i class="fas fa-industry me-2"></i>
                        Sistema MRP - Material Requirements Planning
                    </p>
                    <small class="text-muted">
                        Desenvolvido para gestão eficiente de produção
                    </small>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">
                        <i class="fas fa-code me-1"></i>
                        PHP + MySQL + Bootstrap
                        <br>
                        <i class="fas fa-calendar me-1"></i>
                        <?= date('Y') ?> - Versão 1.0
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript customizado -->
    <script src="assets/js/app.js"></script>
    
    <!-- Scripts específicos da página -->
    <script>
        // Funções utilitárias globais
        
        /**
         * Formata números para exibição
         */
        function formatNumber(num) {
            return new Intl.NumberFormat('pt-BR').format(num);
        }
        
        /**
         * Valida se um valor é numérico positivo
         */
        function isValidPositiveNumber(value) {
            return !isNaN(value) && parseFloat(value) >= 0;
        }
        
        /**
         * Mostra loading em um elemento
         */
        function showLoading(element) {
            element.classList.add('loading');
            element.style.cursor = 'wait';
        }
        
        /**
         * Remove loading de um elemento
         */
        function hideLoading(element) {
            element.classList.remove('loading');
            element.style.cursor = 'default';
        }
        
        /**
         * Mostra toast de notificação
         */
        function showToast(message, type = 'info') {
            // Cria toast dinamicamente
            const toastContainer = document.getElementById('toast-container') || createToastContainer();
            
            const toastId = 'toast-' + Date.now();
            const toastHtml = `
                <div id="${toastId}" class="toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation-triangle' : 'info'}-circle me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            
            const toastElement = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
            
            // Remove o toast após ser fechado
            toastElement.addEventListener('hidden.bs.toast', function() {
                toastElement.remove();
            });
        }
        
        /**
         * Cria container para toasts se não existir
         */
        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
            return container;
        }
        
        /**
         * Confirma ação com modal
         */
        function confirmAction(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }
        
        /**
         * Atualiza URL sem recarregar página (para filtros)
         */
        function updateUrlParameter(param, value) {
            const url = new URL(window.location);
            if (value) {
                url.searchParams.set(param, value);
            } else {
                url.searchParams.delete(param);
            }
            window.history.replaceState({}, '', url);
        }
        
        // Inicialização quando o DOM estiver pronto
        document.addEventListener('DOMContentLoaded', function() {
            
            // Adiciona validação automática em campos numéricos
            const numericInputs = document.querySelectorAll('input[type="number"]');
            numericInputs.forEach(input => {
                input.addEventListener('input', function() {
                    if (this.value < 0) {
                        this.value = 0;
                    }
                });
            });
            
            // Adiciona confirmação em botões de remoção
            const deleteButtons = document.querySelectorAll('.btn-delete, .btn-remove');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm('Tem certeza que deseja remover este item?')) {
                        e.preventDefault();
                        return false;
                    }
                });
            });
            
            // Inicializa tooltips do Bootstrap
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Adiciona máscara básica para campos de quantidade
            const quantityInputs = document.querySelectorAll('.quantity-input');
            quantityInputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.value && !isNaN(this.value)) {
                        this.value = parseInt(this.value) || 0;
                    }
                });
            });
            
        });
        
        // Função para debug (remove em produção)
        function debug(message, data = null) {
            if (console && console.log) {
                console.log('[Sistema MRP Debug]', message, data);
            }
        }
        
    </script>

</body>
</html>