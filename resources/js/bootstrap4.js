// Bootstrap 4
const $modalComponent = $('#modal-crud-base');
function setupmodal() {
    $modalComponent.modal('handleUpdate');
    window.addEventListener('openModalInBrowser', event => {
        $modalComponent.modal('show');
    });
    window.addEventListener('closeModalInBrowser', event => {
        $modalComponent.modal('hide');
    });
    $modalComponent.on('hidden.bs.modal', function (event) {
        Livewire.dispatch('destroyComponent');
        Livewire.dispatch('closeModal');
    });
}

window.addEventListener('load', event => {
    setupmodal();
})