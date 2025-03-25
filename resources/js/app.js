import './bootstrap';

document.addEventListener('livewire:initialized', () => {
    Livewire.hook('component.init', ({ component, cleanup }) => {
        switch (component.name) {
            case "modal-component":
                setupModals();
                break;

            default:
                break;
        }
    });
});

document.addEventListener('livewire:navigated', () => {
    setupModals();
});

// Modal Component
const modalComponent = new bootstrap.Modal(document.getElementById('modal-component'), {

});
function setupModals(){
    modalComponent.handleUpdate();
    window.addEventListener('openModalInBrowser', event => {
        modalComponent.show();
    })
    window.addEventListener('closeModalInBrowser', event => {
        modalComponent.hide();
    })
    document.getElementById('modal-component').addEventListener('hide.bs.modal', function (event) {
        Livewire.dispatch('closeModal');
    })
}

window.addEventListener('load', event => {
    setupModals();
})