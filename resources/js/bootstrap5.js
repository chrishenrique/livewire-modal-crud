// Bootstrap 5
const modal = new bootstrap.Modal(document.getElementById('modal-crud-base'), {

});
function setupmodal(){
    modal.handleUpdate();
    window.addEventListener('openModalInBrowser', event => {
        modal.show();
    })
    window.addEventListener('closeModalInBrowser', event => {
        modal.hide();
    })
    document.getElementById('modal-crud-base').addEventListener('hide.bs.modal', function (event) {
        Livewire.dispatch('destroyComponent');
        Livewire.dispatch('closeModal');
    })
}

window.addEventListener('load', event => {
    setupmodal();
})