import './bootstrap';

document.addEventListener('livewire:initialized', () => {
    Livewire.hook('component.init', ({ component, cleanup }) => {
        if (component.name == "modal-component") {
            setupModalComponent();
        }
    });
});

document.addEventListener('livewire:navigated', () => {
    setupModalComponent();
});

// Modal Component
const modalComponent = new bootstrap.Modal(document.getElementById('modal-component'), {

});
function setupModalComponent(){
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
    setupModalComponent();
})