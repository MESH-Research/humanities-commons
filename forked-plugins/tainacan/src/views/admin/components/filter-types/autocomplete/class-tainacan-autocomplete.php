<?php
namespace Tainacan\Filter_Types;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Class TainacanFilterType
 */
class Autocomplete extends Filter_Type {

    function __construct(){
        $this->set_name( __('Autocomplete', 'tainacan') );
        $this->set_supported_types(['string','long_string','item']);
        $this->set_component('tainacan-filter-autocomplete');
        $this->set_use_max_options(false);
        $this->set_preview_template('
            <div>
                <div class="taginput control is-expanded has-selected">
                    <div class="taginput-container is-focusable"> 
                        <div class="autocomplete control">
                            <div class="control has-icon-right is-loading is-clearfix">
                                <input type="text" class="input" value="'. __('Item') . ' 9" > 
                            </div> 
                            <div class="dropdown-menu" style="">
                                <div class="dropdown-content">
                                    <a class="dropdown-item is-hovered">
                                        <span>'. __('Collection') . ' 2 <strong>'._('item') . ' 9</strong>9</span>
                                    </a>
                                    <a class="dropdown-item">
                                        <span>'. __('Collection') . ' 3 <strong>'._('item') . ' 9</strong>9</span>
                                    </a>
                                    <a class="dropdown-item">
                                        <span>'. __('Collection') . ' 3 <strong>'._('item') . ' 9</strong>8</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        ');
    }
}