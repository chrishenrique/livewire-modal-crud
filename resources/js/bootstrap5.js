// Bootstrap 5
let modalInstance = null;
document.addEventListener("DOMContentLoaded", setupModal);
document.addEventListener("livewire:navigated", setupModal);
document.addEventListener('livewire:navigated', () => {
    const modals = document.querySelectorAll('#modal-crud-base');
    modals.forEach((modal, i) => {
        if (i > 0) modal.remove();
    });
})

function setupModal() {
    const modalElement = document.getElementById('modal-crud-base');
    if (!modalElement) return;

    if (modalElement.dataset.initialized) return;
    modalElement.dataset.initialized = "true";

    modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);

    window.addEventListener('openModalInBrowser', () => {
        modalInstance.show();
    });

    window.addEventListener('closeModalInBrowser', () => {
        modalInstance.hide();
    });

    modalElement.addEventListener('hidden.bs.modal', () => {
        Livewire.dispatch('destroyComponent');
        Livewire.dispatch('closeModal');
    });
}