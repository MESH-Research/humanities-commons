const { __ } = wp.i18n;

const { IconButton, Button, ToggleControl, Placeholder, PanelBody } = wp.components;

const { InspectorControls, BlockControls, useBlockProps } = (tainacan_blocks.wp_version < '5.2' ? wp.editor : wp.blockEditor );

import TainacanBlocksCompatToolbar from '../../js/compatibility/tainacan-blocks-compat-toolbar.js';
import TermsModal from './terms-modal.js';

export default function({ attributes, setAttributes, className, isSelected }){
    let { 
        selectedTermsObject, 
        selectedTermsHTML, 
        content,  
        showImage,
        showName,
        layout,
        isModalOpen,
        taxonomyId,
    } = attributes;

    // Gets blocks props from hook
    const blockProps = tainacan_blocks.wp_version < '5.6' ? { className: className } : useBlockProps();

    function prepareTerm(term) {
        return (
            <li 
                key={ term.id }
                className="term-list-item">
                { tainacan_blocks.wp_version < '5.4' ?
                    <IconButton
                        onClick={ () => removeTermOfId(term.id) }
                        icon="no-alt"
                        label={__('Remove', 'tainacan')}/>
                        :
                    <button
                            onClick={ () => removeTermOfId(term.id) }
                            type="button"
                            class="components-button has-icon"
                            aria-label={__('Remove', 'tainacan')}>
                        <span class="dashicon dashicons dashicons-no-alt" />
                    </button>
                }         
                <a 
                    id={ isNaN(term.id) ? term.id : 'term-id-' + term.id }
                    href={ term.url }
                    className={ (!showName ? 'term-without-name' : '') + ' ' + (!showImage ? 'term-without-image' : '') }>
                    <img
                        src={ term.header_image && term.header_image[0] && term.header_image[0].src ? term.header_image[0].src : `${tainacan_blocks.base_url}/assets/images/placeholder_square.png`}
                        alt={ term.header_image && term.header_image[0] ? term.header_image[0].alt : term.name }/>
                    <span>{ term.name ? term.name : '' }</span>
                </a>
            </li>
        );
    }

    function setContent(){

        selectedTermsHTML = [];

        for (let i = 0; i < selectedTermsObject.length; i++)
            selectedTermsHTML.push(prepareTerm(selectedTermsObject[i]));

        setAttributes({
            content: (
                <ul className={'terms-list  terms-layout-' + layout}>{ selectedTermsHTML }</ul>
            ),
            selectedTermsHTML: selectedTermsHTML
        });
    }


    function openTermsModal() {
        isModalOpen = true;
        setAttributes( { 
            isModalOpen: isModalOpen, 
        } );
    }

    function removeTermOfId(termId) {

        let existingTermIndex = selectedTermsObject.findIndex((existingTerm) => ((existingTerm.id == 'term-id-' + termId) || (existingTerm.id == termId)));

        if (existingTermIndex >= 0)
            selectedTermsObject.splice(existingTermIndex, 1);

        setContent();
    }

    function updateLayout(newLayout) {
        layout = newLayout;

        if (layout == 'grid' && showImage == false)
            showImage = true;

        if (layout == 'list' && showName == false)
            showName = true;

        setAttributes({ 
            layout: layout, 
            showImage: showImage,
            showName: showName
        });
        setContent();
    }

    // Executed only on the first load of page
    if(content && content.length && content[0].type)
        setContent();

    const layoutControls = [
        {
            icon: 'grid-view',
            title: __( 'Grid View', 'tainacan' ),
            onClick: () => updateLayout('grid'),
            isActive: layout === 'grid',
        },
        {
            icon: 'list-view',
            title: __( 'List View', 'tainacan' ),
            onClick: () => updateLayout('list'),
            isActive: layout === 'list',
        }
    ];

    return content == 'preview' ? 
        <div className={className}>
            <img
                    width="100%"
                    src={ `${tainacan_blocks.base_url}/assets/images/terms-list.png` } />
        </div>
    : (
        <div { ...blockProps }>

            <div>
                <BlockControls>
                    { TainacanBlocksCompatToolbar({ controls: layoutControls }) }
                    { selectedTermsHTML.length ?
                        TainacanBlocksCompatToolbar({
                            label: __( 'Select terms', 'tainacan' ),
                            icon: <svg width="24" height="24" viewBox="0 -3 12 17">
                                    <path
                                        d="M 4.4,2.5 H 0 V 0 h 4.4 l 1.2,1.3 z m -1.9,5 v 3.1 H 5 v 1.2 H 1.3 v -8 H 2.5 V 6.3 H 5 V 7.6 H 2.5 Z m 8.2,0.7 H 6.3 V 5.7 h 4.4 l 1.2,1.2 z M 11.9,11.3 10.7,10 H 6.3 v 2.5 h 4.4 z"/>       
                                </svg>,
                            onClick: openTermsModal
                        })
                    : null }
                </BlockControls>
            </div>

            <div>
                <InspectorControls>
                    <PanelBody
                            title={ __('List settings', 'tainacan') }
                            initialOpen={ true }
                        >
                        { layout == 'list' ? 
                            <ToggleControl
                                label={__('Image', 'tainacan')}
                                help={ showImage ? __('Toggle to show term\'s image', 'tainacan') : __('Do not show term\'s image', 'tainacan')}
                                checked={ showImage }
                                onChange={ ( isChecked ) => {
                                        showImage = isChecked;
                                        setAttributes({ showImage: showImage });
                                        setContent();
                                    } 
                                }
                            /> 
                        : null }
                        { layout == 'grid' ? 
                            <ToggleControl
                                label={__('Name', 'tainacan')}
                                help={ showName ? __('Toggle to show term\'s name', 'tainacan') : __('Do not show term\'s name', 'tainacan')}
                                checked={ showName }
                                onChange={ ( isChecked ) => {
                                        showName = isChecked;
                                        setAttributes({ showName: showName });
                                        setContent();
                                    } 
                                }
                            />
                        : null }
                    </PanelBody>
                </InspectorControls>
            </div>

            { isSelected ? 
                (
                <div>
                    { isModalOpen ? 
                        <TermsModal
                            replaceTermId={ true }
                            existingTaxonomyId={ taxonomyId } 
                            selectedTermsObject={ selectedTermsObject } 
                            onSelectTaxonomy={ (selectedTaxonomyId) => {
                                taxonomyId = selectedTaxonomyId;
                                setAttributes({ taxonomyId: taxonomyId });
                            }}
                            onApplySelection={ (aSelectedTermsObject) =>{
                                selectedTermsObject = aSelectedTermsObject
                                setAttributes({
                                    selectedTermsObject: selectedTermsObject,
                                    isModalOpen: false
                                });
                                setContent();
                            }}
                            onCancelSelection={ () => setAttributes({ isModalOpen: false }) }/> 
                        : null 
                    }
                </div>
                ) : null
            }

            { !selectedTermsHTML.length ? (
                <Placeholder
                    className="tainacan-block-placeholder"
                    icon={(
                        <img
                            width={148}
                            src={ `${tainacan_blocks.base_url}/assets/images/tainacan_logo_header.svg` }
                            alt="Tainacan Logo"/>
                    )}>
                    <p>
                        <svg width="24" height="24" viewBox="0 -2 12 16">
                            <path
                                d="M 4.4,2.5 H 0 V 0 h 4.4 l 1.2,1.3 z m -1.9,5 v 3.1 H 5 v 1.2 H 1.3 v -8 H 2.5 V 6.3 H 5 V 7.6 H 2.5 Z m 8.2,0.7 H 6.3 V 5.7 h 4.4 l 1.2,1.2 z M 11.9,11.3 10.7,10 H 6.3 v 2.5 h 4.4 z"/>       
                        </svg>
                        {__('Expose terms from your Tainacan taxonomies', 'tainacan')}
                    </p>
                    <Button
                        isPrimary
                        type="button"
                        onClick={ () => openTermsModal() }>
                        {__('Select terms', 'tainacan')}
                    </Button>   
                </Placeholder>
                ) : null
            }

            <ul className={'terms-list-edit terms-layout-' + layout}>{ selectedTermsHTML }</ul>
            
        </div>
    );
};