<?php

namespace App\Templates;

use App\ReturnData;
use App\ReturnType;
use App\ReturnDirection;

class TemplateModalWindow
{
    public function __construct() {}

    public function getTemplateModalWindow()
    {
        $template = <<<HTML
        <div id = 'modalWindow%s' class = 'modal-window modal-hidden-2'> 
                <div id = 'modalWindowHeader%s' class = 'modal-window-header'>            
                </div>    
                <div id = 'modalWindowBody%s' class = 'modal-window-body'>            
                </div>    
                <div id = 'modalWindowFooter%s' class = 'modal-window-footer'>
                    <button id = 'modalExecute%s' type='button' class='standard-btn'></button>
                    <button id = 'modalCancellation%s' type='button' class='standard-btn'></button>
                    <button id = 'modalAdditional%s' type='button' class='standard-btn'></button>
                </div>    
            </div>
        HTML;
        ReturnData::getInstance()->set($template, ReturnType::DATA, "", ReturnDirection::RETURNTOCLIENT);
    }
}
