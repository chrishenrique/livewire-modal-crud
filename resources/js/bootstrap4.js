// Bootstrap 4
document.addEventListener("DOMContentLoaded", setupModal);
document.addEventListener("livewire:navigated", setupModal);
document.addEventListener('livewire:navigated', () => {
    const modals = document.querySelectorAll('#modal-crud-base');
    modals.forEach((modal, i) => {
        if (i > 0) modal.remove();
    });
})

function setupModal() {
    const $modal = $('#modal-crud-base');

    if ($modal.data("initialized")) return;
    $modal.data("initialized", true);

    window.addEventListener('openModalInBrowser', () => {
        $modal.modal('show');
    });

    window.addEventListener('closeModalInBrowser', () => {
        $modal.modal('hide');
    });

    $modal.on('hidden.bs.modal', function () {
        Livewire.dispatch('destroyComponent');
        Livewire.dispatch('closeModal');
    });
}