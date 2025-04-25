<?php

namespace ChrisHenrique\ModalCrud\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class ModalComponent extends Component
{
    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('components.layouts.modal');
    }
}
